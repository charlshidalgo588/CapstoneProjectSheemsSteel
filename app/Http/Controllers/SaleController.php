<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Transaction;
use App\Models\Inventory;
use App\Models\Customer;
use App\Models\Product;
use App\Models\InventoryLog;
use App\Models\Notification;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class SaleController extends Controller
{
    /**
     * Stock level at/below which a product is considered "low stock".
     * Matches the badge cutoff used in the POS product grid (POS.vue).
     */
    private const LOW_STOCK_THRESHOLD = 5;

    /**
     * ============================================
     *  PROCESS SALE (POS)
     * ============================================
     */
    public function processSale(Request $request)
    {
        try {
            Log::info('POS Sale Request:', $request->all());

            $request->validate([
                'total_amount'     => 'required|numeric|min:0',
                'discount_amount'  => 'nullable|numeric|min:0',
                'amount_paid'      => 'required|numeric|min:0',
                'customer_name'    => 'required|string|max:255',
                'items'            => 'required|array|min:1',

                'items.*.product_id' => 'required|exists:products,ProductID',
                'items.*.quantity'   => 'required|integer|min:1',
                'items.*.price'      => 'required|numeric|min:0',
            ]);

            DB::beginTransaction();

            // 1. Create or find customer
            $customer = Customer::firstOrCreate(
                ['CustomerCode' => $request->customer_name],
                ['CustomerCode' => $request->customer_name]
            );

            // 2. Create sale header
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

            // 3. Process sale items
            foreach ($request->items as $item) {
                // Sale Items
                $sale->salesItems()->create([
                    'ProductID'    => $item['product_id'],
                    'Quantity'     => $item['quantity'],
                    'PriceAtSale'  => $item['price'],
                    'Subtotal'     => $item['quantity'] * $item['price'],
                ]);

                // Transaction record
                Transaction::create([
                    'ProductID'       => $item['product_id'],
                    'TransactionType' => 'SALE',
                    'QuantityChange'  => -$item['quantity'],
                    'UnitPrice'       => $item['price'],
                    'TotalAmount'     => $item['quantity'] * $item['price'],
                    'ReferenceID'     => $sale->SaleID,
                    'TransactionDate' => now(),
                ]);

                // Deduct inventory
                $inventory = Inventory::where('ProductID', $item['product_id'])->first();
                if ($inventory) {
                    $inventory->QuantityOnHand -= $item['quantity'];
                    $inventory->save();

                    // Fire a low-stock / out-of-stock notification if this
                    // sale just pushed the product into that territory.
                    $product = Product::with('inventory')->find($item['product_id']);
                    if ($product) {
                        $this->checkStockNotification($product);
                    }
                }

                // Inventory log
                InventoryLog::create([
                    'ProductID'  => $item['product_id'],
                    'type'       => 'stock_out',
                    'quantity'   => $item['quantity'],
                    'notes'      => 'POS sale #' . $sale->SaleID,
                    'created_by' => Auth::id() ?? 1,
                ]);
            }

            // Notify that a sale just happened — this is what the frontend's
            // notification bell / NOTIF_ROUTES['sale'] expects to receive.
            $this->notifySale($sale, $customer);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale processed successfully',
                'sale_id' => $sale->SaleID,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS Sale Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Creates a "sale" notification for every completed checkout.
     * Unlike stock notifications, sales are never deduplicated — each
     * transaction is its own event and should show up in the bell.
     */
    private function notifySale(Sale $sale, Customer $customer): void
    {
        Notification::create([
            'type'            => 'sale',
            'title'           => 'New sale',
            'description'     => "Sale #{$sale->SaleID} for {$customer->CustomerCode} — ₱" . number_format($sale->TotalAmount, 2),
            'notifiable_type' => Sale::class,
            'notifiable_id'   => $sale->SaleID,
            'user_id'         => null, // visible to all users — matches NotificationController::index()
        ]);
    }

    /**
     * Creates a low-stock/out-of-stock notification for a product whose
     * stock just changed after a sale. Reuses an existing UNREAD
     * notification for the same product+type instead of inserting a new
     * row every single sale, so repeat purchases of an already-low item
     * don't spam duplicate alerts — the existing row is just "touched"
     * so it stays near the top of the latest() list in
     * NotificationController::index().
     */
    private function checkStockNotification(Product $product): void
    {
        $qty = $product->inventory->QuantityOnHand;

        if ($qty > self::LOW_STOCK_THRESHOLD) {
            return; // healthy stock, nothing to notify
        }

        $type = $qty <= 0 ? 'out_of_stock' : 'low_stock';

        $title = $qty <= 0 ? 'Out of stock' : 'Low stock';

        $description = $qty <= 0
            ? "{$product->ProductName} is now out of stock."
            : "{$product->ProductName} has only {$qty} unit(s) left.";

        $existing = Notification::where('type', $type)
            ->where('notifiable_type', Product::class)
            ->where('notifiable_id', $product->ProductID)
            ->whereNull('read_at')
            ->first();

        if ($existing) {
            // Bump it back to the top of the "latest()" list instead of
            // creating a duplicate row for the same still-low product.
            $existing->touch();
            return;
        }

        Notification::create([
            'type'            => $type,
            'title'           => $title,
            'description'     => $description,
            'notifiable_type' => Product::class,
            'notifiable_id'   => $product->ProductID,
            'user_id'         => null, // visible to all users — matches NotificationController::index()
        ]);
    }

    /**
     * ============================================
     *  API: LIST SALES (for Vue)
     * ============================================
     */
    public function apiList()
    {
        $sales = Sale::with(['customer', 'clerk'])
            ->orderBy('SaleDate', 'desc')
            ->get()
            ->map(function ($sale) {
                return [
                    'SaleID'        => $sale->SaleID,
                    'SaleDate'      => $sale->SaleDate->format('M d, Y'),

                    // Customer name = CustomerCode
                    'CustomerName'  => $sale->customer->CustomerCode ?? 'Walk-in',

                    'TotalAmount'   => $sale->TotalAmount,
                    'PaymentMethod' => $sale->PaymentMethod ?? 'Cash',

                    // Status always returned
                    'Status'        => $sale->Status ?? 'Paid',

                    // Clerk name from users table
                    'ClerkName'     => $sale->clerk->name ?? 'Admin',
                ];
            });

        return response()->json($sales);
    }

    /**
     * ============================================
     *  API: SHOW A SINGLE SALE
     * ============================================
     */
    public function apiShow($id)
    {
        $sale = Sale::with(['customer', 'clerk', 'salesItems.product'])
            ->findOrFail($id);

        return response()->json($sale);
    }

    /**
     * ============================================
     *  BULK DELETE + RESTORE STOCK
     * ============================================
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'saleIds'       => 'required|array',
            'admin_password'=> 'required|string',
        ]);

        if (!Hash::check($request->admin_password, auth()->user()->password)) {
            return response()->json(['message' => 'Invalid admin password'], 403);
        }

        DB::beginTransaction();

        try {
            $sales = Sale::with(['salesItems'])
                ->whereIn('SaleID', $request->saleIds)
                ->get();

            foreach ($sales as $sale) {

                foreach ($sale->salesItems as $item) {

                    // Restore Inventory
                    $inventory = Inventory::where('ProductID', $item->ProductID)->first();
                    if ($inventory) {
                        $inventory->QuantityOnHand += $item->Quantity;
                        $inventory->save();
                    }

                    InventoryLog::create([
                        'ProductID'  => $item->ProductID,
                        'type'       => 'stock_in',
                        'quantity'   => $item->Quantity,
                        'notes'      => 'Rollback of sale #' . $sale->SaleID,
                        'created_by' => Auth::id(),
                    ]);
                }

                $sale->salesItems()->delete();
                $sale->delete();
            }

            DB::commit();
            return response()->json(['message' => 'Sales deleted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * ============================================
     *  DELETE SINGLE SALE (BACKEND PAGE)
     * ============================================
     */
    public function destroy(Sale $sale)
    {
        try {
            DB::beginTransaction();

            foreach ($sale->salesItems as $item) {

                $inventory = Inventory::where('ProductID', $item->ProductID)->first();
                if ($inventory) {
                    $inventory->QuantityOnHand += $item->Quantity;
                    $inventory->save();
                }

                InventoryLog::create([
                    'ProductID'  => $item->ProductID,
                    'type'       => 'stock_in',
                    'quantity'   => $item->Quantity,
                    'notes'      => 'Cancelled sale #' . $sale->SaleID,
                    'created_by' => Auth::id(),
                ]);
            }

            $sale->salesItems()->delete();
            $sale->delete();

            DB::commit();
            return redirect()->route('sales.index')
                ->with('success', 'Sale deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}