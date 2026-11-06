<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SalesItem;
use App\Models\SaleService;
use App\Models\Transaction;
use App\Models\Inventory;
use App\Models\Customer;
use App\Models\Product;
use App\Models\InventoryLog;
use App\Models\Notification;
use App\Services\StockService;
use App\Services\SaleTracker;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    /**
     * PROCESS SALE (POS)  — POST /api/sales/process must point HERE.
     * Deducts stock PER SIZE through StockService, writes the inventory log
     * (with cut-piece details), sells leftover pieces from product_remnants
     * and records any extra services.
     */
    public function processSale(Request $request)
    {
        try {
            $request->validate([
                'total_amount'    => 'required|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'amount_paid'     => 'required|numeric|min:0',
                'customer_name'   => 'required|string|max:255',
                'customer_id'     => 'nullable|integer',
                'idempotency_key' => 'nullable|string|max:100',
                'items'           => 'nullable|array',

                'items.*.product_id' => 'required|exists:products,ProductID',
                'items.*.variant_id' => 'nullable|integer',
                'items.*.remnant_id' => 'nullable|integer',   // set when the line is a leftover piece
                'items.*.quantity'   => 'required|integer|min:1',
                'items.*.price'      => 'required|numeric|min:0',
                'items.*.original_price' => 'nullable|numeric|min:0',   // list price before a cut / custom price

                // Cut pieces: needed so the cost is only the share that was sold.
                'items.*.cut'              => 'nullable|array',
                'items.*.cut.full_length'  => 'required_with:items.*.cut|numeric|gt:0',
                'items.*.cut.sold_length'  => 'required_with:items.*.cut|numeric|gt:0',
                'items.*.cut.unit'         => 'nullable|string|max:10',

                // Extra services (labour)
                'services'                      => 'nullable|array',
                'services.*.description'        => 'required|string|max:255',
                'services.*.fee'                => 'required|numeric|min:0',
                'services.*.worker_name'        => 'nullable|string|max:100',
                'services.*.worker_share_type'  => 'nullable|string|in:percent,amount',
                'services.*.worker_share_value' => 'nullable|numeric|min:0',
                'services.*.worker_amount'      => 'nullable|numeric|min:0',
            ]);

            if (empty($request->items) && empty($request->services)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Add at least one item or service.',
                ], 422);
            }

            if ((float) $request->amount_paid < (float) $request->total_amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Amount paid is less than the total.',
                ], 422);
            }

            $idemKey = $request->idempotency_key ? 'sale_idem_' . $request->idempotency_key : null;
            if ($idemKey && Cache::has($idemKey)) {
                return response()->json(Cache::get($idemKey));
            }

            DB::beginTransaction();

            // 1. Find or create the customer
            $customer = null;
            if ($request->filled('customer_id')) {
                $customer = Customer::find($request->customer_id);
            }
            if (!$customer) {
                $name = trim($request->customer_name);
                $customer = Customer::firstOrCreate(
                    ['CustomerCode' => $name],
                    ['CustomerCode' => $name]
                );
            }

            // 2. Sale header
            $sale = Sale::create([
                'SaleDate'       => now(),
                'CustomerID'     => $customer->CustomerID,
                'TotalAmount'    => $request->total_amount,
                'DiscountAmount' => $request->discount_amount ?? 0,
                'AmountPaid'     => $request->amount_paid,
                'PaymentMethod'  => 'Cash',
                'Status'         => 'Paid',
                'ClerkID'        => Auth::id() ?? 1,
            ]);

            // 3. Items
            foreach ($request->items ?? [] as $item) {
                // A leftover piece: sold from product_remnants. Whole-piece stock is NOT touched.
                if (!empty($item['remnant_id'])) {
                    $this->sellRemnant($sale, $item);
                    continue;
                }

                $product = Product::findOrFail($item['product_id']);

                $variant = StockService::deduct(
                    (int) $item['product_id'],
                    isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                    (int) $item['quantity'],
                    $product->ProductName
                );

                $sale->salesItems()->create([
                    'ProductID'    => $item['product_id'],
                    'VariantID'    => $variant->VariantID,
                    'VariantLabel' => $variant->Label,
                    'Quantity'     => $item['quantity'],
                    'PriceAtSale'  => $item['price'],
                    // Cost snapshot (cut pieces only carry their share of the cost).
                    'CostAtSale'   => SalesItem::costFor(
                        (float) ($variant->CostPrice ?? $product->CostPrice ?? 0),
                        $item['cut'] ?? null
                    ),
                ]);

                Transaction::create([
                    'ProductID'       => $item['product_id'],
                    'TransactionType' => 'SALE',
                    'QuantityChange'  => -$item['quantity'],
                    'UnitPrice'       => $item['price'],
                    'TotalAmount'     => $item['quantity'] * $item['price'],
                    'ReferenceID'     => $sale->SaleID,
                    'TransactionDate' => now(),
                ]);

                // Inventory log (+ offcut when the line was a cut piece)
                SaleTracker::logItem($sale->SaleID, $item, $variant, $product, Auth::id());
            }

            SaleService::record($sale->SaleID, $request->services ?? []);

            $this->notifySale($sale, $customer);

            DB::commit();

            $response = [
                'success'        => true,
                'message'        => 'Sale processed successfully',
                'sale_id'        => $sale->SaleID,
                'receipt_number' => 'SR-' . str_pad($sale->SaleID, 5, '0', STR_PAD_LEFT),
            ];

            if ($idemKey) {
                Cache::put($idemKey, $response, now()->addMinutes(10));
            }

            return response()->json($response);

        } catch (ValidationException $e) {
            if (DB::transactionLevel() > 0) DB::rollBack();
            throw $e;

        } catch (\RuntimeException $e) {
            if (DB::transactionLevel() > 0) DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) DB::rollBack();
            Log::error('POS Sale Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function notifySale(Sale $sale, Customer $customer): void
    {
        Notification::create([
            'type'            => 'sale',
            'title'           => 'New sale',
            'description'     => "Sale #{$sale->SaleID} for {$customer->CustomerCode} — ₱" . number_format($sale->TotalAmount, 2),
            'notifiable_type' => Sale::class,
            'notifiable_id'   => $sale->SaleID,
            'user_id'         => null,
        ]);
    }

    /**
     * Sells (part of) a leftover piece. Whole-piece stock is NOT touched: it already
     * left stock when the cut was sold. Runs inside processSale()'s transaction,
     * so any failure rolls the whole sale back.
     */
    private function sellRemnant(Sale $sale, array $item): void
    {
        $rem = DB::table('product_remnants')
            ->where('RemnantID', $item['remnant_id'])
            ->lockForUpdate()
            ->first();

        if (!$rem || $rem->Status !== 'available') {
            throw new \RuntimeException('A leftover piece in this sale is no longer available. Please refresh the POS.');
        }
        if ((int) $rem->ProductID !== (int) $item['product_id']) {
            throw new \RuntimeException('Leftover piece does not match the product.');
        }

        $have = max((int) $rem->Quantity, 1);   // older rows may store 0 = one piece
        $qty  = (int) $item['quantity'];
        if ($qty > $have) {
            throw new \RuntimeException("Only {$have} of that leftover piece is available.");
        }

        $left = $have - $qty;
        DB::table('product_remnants')->where('RemnantID', $rem->RemnantID)->update([
            'Quantity'   => $left,
            'Status'     => $left > 0 ? 'available' : 'used',
            'updated_at' => now(),
        ]);

        $product      = Product::findOrFail($rem->ProductID);
        $variantTable = $this->variantTable();
        $variant      = ($rem->VariantID && $variantTable)
            ? DB::table($variantTable)->where('VariantID', $rem->VariantID)->first()
            : null;

        // Cost = the share of the piece this leftover represents. The cut sale only booked
        // the cost of the part sold, so the two add up to one full piece.
        $unitCost = (float) ($variant->CostPrice ?? $product->CostPrice ?? 0);
        $fullLen  = (float) ($rem->FullLength ?? 0);
        $cost = $fullLen > 0
            ? SalesItem::costFor($unitCost, ['full_length' => $fullLen, 'sold_length' => (float) $rem->Length])
            : 0;

        $line = $sale->salesItems()->make([
            'ProductID'    => $rem->ProductID,
            'VariantID'    => $rem->VariantID,
            'VariantLabel' => $rem->VariantLabel ?? ($variant->Label ?? null),
            'Quantity'     => $qty,
            'PriceAtSale'  => $item['price'],
            'CostAtSale'   => $cost,
        ]);

        // Remember which leftover this line sold (needs the RemnantID migration).
        if (Schema::hasColumn($line->getTable(), 'RemnantID')) {
            $line->forceFill(['RemnantID' => $rem->RemnantID]);
        }
        $line->save();

        // History row; the note starts with "POS sale" so it isn't a manual adjustment.
        try {
            $len = rtrim(rtrim(number_format((float) $rem->Length, 3, '.', ''), '0'), '.');
            InventoryLog::create([
                'ProductID'            => $rem->ProductID,
                'SaleID'               => $rem->SaleID,   // the sale the leftover came from
                'VariantID'            => $rem->VariantID,
                'VariantLabel'         => $rem->VariantLabel,
                'type'                 => 'remnant_used',
                'quantity'             => 0,
                'is_cut'               => 0,
                'cut_remaining_length' => $rem->Length,
                'cut_unit'             => $rem->Unit,
                'notes'                => "POS sale #{$sale->SaleID} – leftover {$len} {$rem->Unit} sold (x{$qty})",
                'created_by'           => Auth::id() ?? 1,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Leftover sale history row skipped: ' . $e->getMessage());
        }
    }

    /** Name of the sizes table, if the project has one. */
    private function variantTable(): ?string
    {
        return collect(['product_variants', 'variants'])->first(fn ($t) => Schema::hasTable($t));
    }

    /**
     * Undo one sold line when a sale is deleted.
     * - Leftover line: the leftover goes back to available (no whole-piece stock change).
     * - Normal line: whole-piece stock is restored and a stock_in row is logged.
     */
    private function rollbackItem(Sale $sale, $item, string $note, ?int $userId): void
    {
        if (!empty($item->RemnantID)) {
            $rem = DB::table('product_remnants')->where('RemnantID', $item->RemnantID)->lockForUpdate()->first();
            if ($rem) {
                DB::table('product_remnants')->where('RemnantID', $rem->RemnantID)->update([
                    'Quantity'   => (int) $rem->Quantity + (int) $item->Quantity,
                    'Status'     => 'available',
                    'updated_at' => now(),
                ]);
            }
            return;
        }

        $this->restoreStock($item->ProductID, $item->VariantID ?? null, (int) $item->Quantity);

        InventoryLog::create([
            'ProductID'    => $item->ProductID,
            'SaleID'       => $sale->SaleID,
            'VariantID'    => $item->VariantID ?? null,
            'VariantLabel' => $item->VariantLabel ?? null,
            'type'         => 'stock_in',
            'quantity'     => $item->Quantity,
            'notes'        => $note . $sale->SaleID,
            'created_by'   => $userId,
        ]);
    }

    /** Puts stock back when a sale is deleted. */
    private function restoreStock(int $productId, ?int $variantId, int $qty): void
    {
        if ($variantId) {
            try {
                StockService::add($productId, $variantId, $qty);
                return;
            } catch (\RuntimeException $e) {
                Log::warning("restoreStock: size {$variantId} not found for product {$productId}");
            }
        }

        $inventory = Inventory::where('ProductID', $productId)->first();
        if ($inventory) {
            $inventory->QuantityOnHand += $qty;
            $inventory->save();
        }
    }

    /** API: LIST SALES (for Vue). WorkerPayout = pass-through money that is not shop revenue. */
    public function apiList()
    {
        $sales   = Sale::with(['customer', 'clerk'])->orderBy('SaleDate', 'desc')->get();
        $payouts = SaleTracker::workerPayouts($sales->pluck('SaleID')->all());

        return response()->json(
            $sales->map(function ($sale) use ($payouts) {
                return [
                    'SaleID'        => $sale->SaleID,
                    'SaleDate'      => $sale->SaleDate->format('M d, Y'),
                    'CustomerName'  => $sale->customer->CustomerCode ?? 'Walk-in',
                    'TotalAmount'   => $sale->TotalAmount,
                    'WorkerPayout'  => round($payouts[$sale->SaleID] ?? 0, 2),
                    'PaymentMethod' => $sale->PaymentMethod ?? 'Cash',
                    'Status'        => $sale->Status ?? 'Paid',
                    'ClerkName'     => $sale->clerk->name ?? 'Admin',
                ];
            })->values()
        );
    }

    /**
     * API: SHOW A SINGLE SALE.
     * `services` and `cuts` are STAFF-ONLY data (worker, percentages, cut details).
     * The receipt views must only print Description + Fee.
     */
    public function apiShow($id)
    {
        $sale = Sale::with(['customer', 'clerk', 'salesItems.product'])->findOrFail($id);
        $data = $sale->toArray();

        $data['services'] = SaleTracker::servicesFor($sale->SaleID);

        $data['cuts'] = DB::table('inventory_logs')
            ->join('products', 'inventory_logs.ProductID', '=', 'products.ProductID')
            ->where('inventory_logs.SaleID', $sale->SaleID)
            ->where('inventory_logs.is_cut', 1)
            ->select(
                'products.ProductName',
                'inventory_logs.VariantLabel',
                'inventory_logs.quantity',
                'inventory_logs.cut_full_length',
                'inventory_logs.cut_sold_length',
                'inventory_logs.cut_remaining_length',
                'inventory_logs.cut_unit'
            )
            ->get();

        return response()->json($data);
    }

    /** PUBLIC: BEST SELLERS (login page) */
    public function bestSellers()
    {
        $products = Cache::remember('public_best_sellers', now()->addMinutes(10), function () {
            $sale       = new Sale;
            $relation   = $sale->salesItems();
            $itemsTable = $relation->getRelated()->getTable();
            $salesTable = $sale->getTable();

            $topIds = DB::table($itemsTable)
                ->join($salesTable, $relation->getQualifiedParentKeyName(), '=', $relation->getQualifiedForeignKeyName())
                ->where("$salesTable.SaleDate", '>=', now()->subDays(90))
                ->where("$salesTable.Status", 'Paid')
                ->groupBy("$itemsTable.ProductID")
                ->orderByRaw("SUM($itemsTable.Quantity) DESC")
                ->limit(10)
                ->pluck("$itemsTable.ProductID");

            if ($topIds->isEmpty()) {
                return collect();
            }

            $found = Product::with('inventory')
                ->whereIn('ProductID', $topIds)
                ->get()
                ->keyBy('ProductID');

            return $topIds->map(fn ($id) => $found->get($id))->filter()->values();
        });

        return response()->json($products);
    }

    /** BULK DELETE + RESTORE STOCK (per size) */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'saleIds'        => 'required|array',
            'admin_password' => 'required|string',
        ]);

        $user = $request->user() ?? Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated. Please log in again.'], 401);
        }

        if (!Hash::check($request->admin_password, $user->password)) {
            return response()->json(['message' => 'Invalid admin password'], 403);
        }

        DB::beginTransaction();

        try {
            $sales = Sale::with(['salesItems'])
                ->whereIn('SaleID', $request->saleIds)
                ->get();

            foreach ($sales as $sale) {
                foreach ($sale->salesItems as $item) {
                    $this->rollbackItem($sale, $item, 'Rollback of sale #', $user->id);
                }

                SaleTracker::rollback($sale->SaleID);   // remove this sale's offcuts
                SaleService::where('SaleID', $sale->SaleID)->delete();
                $sale->salesItems()->delete();
                $sale->delete();
            }

            DB::commit();
            return response()->json(['message' => 'Sales deleted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk delete error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /** DELETE SINGLE SALE (BACKEND PAGE) */
    public function destroy(Sale $sale)
    {
        try {
            DB::beginTransaction();

            foreach ($sale->salesItems as $item) {
                $this->rollbackItem($sale, $item, 'Cancelled sale #', Auth::id());
            }

            SaleTracker::rollback($sale->SaleID);
            SaleService::where('SaleID', $sale->SaleID)->delete();
            $sale->salesItems()->delete();
            $sale->delete();

            DB::commit();
            return redirect()->route('sales.index')
                ->with('success', 'Sale deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}