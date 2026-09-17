<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class POSController extends Controller
{
    /**
     * Stock level at/below which a product is considered "low stock".
     * Matches the badge cutoff used in the POS product grid on the
     * frontend (stockStatus() in POS.vue).
     */
    private const LOW_STOCK_THRESHOLD = 5;

    public function index()
    {
        $categories = Category::all();
        $products = Product::with(['category', 'inventory'])
            ->whereHas('inventory', function ($query) {
                $query->where('QuantityOnHand', '>', 0);
            })
            ->get();

        return view('sales.point-of-sale', compact('categories', 'products'));
    }

    /**
     * PROCESS POS CHECKOUT
     * Saves sale, sale items & deducts stock.
     */
    public function checkout(Request $request)
    {
        DB::beginTransaction();

        try {
            // 1. Create the sale entry
            $sale = Sale::create([
                'SaleDate'       => now(),
                'CustomerID'     => null, // walk-in sale; wire up a real customer lookup if/when you add one
                'TotalAmount'    => $request->total,
                'DiscountAmount' => $request->discount ?? 0,
                'AmountPaid'     => $request->amount_received,
                'PaymentMethod'  => $request->payment_method,
                'ClerkID'        => auth()->id(),
            ]);

            // 2. Loop through cart items
            foreach ($request->items as $item) {

                // Save item details
                SalesItem::create([
                    'SaleID'      => $sale->SaleID,
                    'ProductID'   => $item['product_id'],
                    'Quantity'    => $item['quantity'],
                    'PriceAtSale' => $item['price'],
                ]);

                // Deduct from inventory
                $product = Product::with('inventory')->find($item['product_id']);

                if ($product && $product->inventory) {
                    $product->inventory->QuantityOnHand -= $item['quantity'];
                    $product->inventory->save();

                    // Fire a low-stock / out-of-stock notification if this
                    // sale just pushed the product into that territory.
                    $this->checkStockNotification($product);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Sale saved successfully!',
                'sale_id' => $sale->SaleID,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error saving sale.',
                'error'   => $e->getMessage(),
            ], 500);
        }
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
}