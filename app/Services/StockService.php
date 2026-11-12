<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Notification;
use App\Models\ProductVariant;

/**
 * All stock rules live here.
 *
 * Stock is stored PER SIZE (product_variants.QuantityOnHand).
 * inventories.QuantityOnHand is kept as the PRODUCT TOTAL (sum of its sizes)
 * so the dashboard, reports and product list keep working unchanged.
 *
 * RULE: nothing should write inventories.QuantityOnHand directly any more.
 * Change a size, then call syncProductInventory().
 *
 * deduct() / add() use lockForUpdate(), so call them INSIDE DB::beginTransaction().
 */
class StockService
{
    /** Used for alerts when a size has no reorder level set (0). */
    public const DEFAULT_LOW_STOCK = 5;

    /** Product total = sum of its sizes. Call after ANY size stock change. */
    public static function syncProductInventory(int $productId): void
    {
        $total   = (int) ProductVariant::where('ProductID', $productId)->sum('QuantityOnHand');
        $reorder = (int) ProductVariant::where('ProductID', $productId)->sum('ReorderLevel');

        $inv = Inventory::where('ProductID', $productId)->first() ?? new Inventory();
        $inv->ProductID      = $productId;   // assigned directly, so $fillable does not matter
        $inv->QuantityOnHand = $total;
        $inv->ReorderLevel   = $reorder;
        $inv->LastUpdated    = now();
        $inv->save();
    }

    /**
     * Find the size being sold/restocked.
     * - variant id given  -> that size (must belong to the product)
     * - no variant id     -> allowed only if the product has exactly ONE size
     */
    public static function resolveVariant(int $productId, ?int $variantId, bool $lock = true): ProductVariant
    {
        $query = ProductVariant::where('ProductID', $productId);
        if ($lock) {
            $query->lockForUpdate();
        }

        if ($variantId) {
            $variant = (clone $query)->where('VariantID', $variantId)->first();
            if (!$variant) {
                throw new \RuntimeException('The selected size was not found for this product.');
            }
            return $variant;
        }

        $all = $query->get();
        if ($all->count() === 1) {
            return $all->first();
        }

        throw new \RuntimeException('Please choose a size for this product.');
    }

    /**
     * Take stock out of ONE size (sale). Refuses to go below zero.
     * Returns the updated size so the caller can save VariantID / VariantLabel.
     */
    public static function deduct(int $productId, ?int $variantId, int $qty, string $productName): ProductVariant
    {
        $variant = self::resolveVariant($productId, $variantId);

        if ($variant->QuantityOnHand < $qty) {
            throw new \RuntimeException(
                "Not enough stock for {$productName} ({$variant->Label}). Available: {$variant->QuantityOnHand}."
            );
        }

        $variant->QuantityOnHand -= $qty;
        $variant->save();

        self::syncProductInventory($productId);
        self::notifyIfLow($variant, $productName);

        return $variant;
    }

    /** Put stock into ONE size (restock, supplier purchase). */
    public static function add(int $productId, ?int $variantId, int $qty): ProductVariant
    {
        $variant = self::resolveVariant($productId, $variantId);

        $variant->QuantityOnHand += $qty;
        $variant->save();

        self::syncProductInventory($productId);

        // Clears stale low/out alerts once the size is healthy again.
        // (Needs a product() relation on ProductVariant — see note below the class.)
        $name = $variant->product->ProductName ?? '';
        self::notifyIfLow($variant, $name);

        return $variant;
    }

    /**
     * Keeps ONE size's stock alerts in sync with its quantity.
     * Call after ANY stock or reorder-level change.
     *  - healthy        → marks unread low/out alerts for this size as read
     *  - low            → one unread "Low stock" alert
     *  - out of stock   → one unread "Out of stock" alert (replaces the low one)
     */
    public static function notifyIfLow(ProductVariant $variant, string $productName): void
    {
        $qty       = (int) $variant->QuantityOnHand;
        $threshold = $variant->ReorderLevel > 0 ? (int) $variant->ReorderLevel : self::DEFAULT_LOW_STOCK;

        $unread = fn (string $type) => Notification::where('type', $type)
            ->where('notifiable_type', ProductVariant::class)
            ->where('notifiable_id', $variant->VariantID)
            ->whereNull('read_at');

        if ($qty > $threshold) {
            // Back to healthy: retire any alert that is no longer true.
            $unread('low_stock')->update(['read_at' => now()]);
            $unread('out_of_stock')->update(['read_at' => now()]);
            return;
        }

        $type = $qty <= 0 ? 'out_of_stock' : 'low_stock';

        // Going from low to out: the low alert is superseded.
        if ($type === 'out_of_stock') {
            $unread('low_stock')->update(['read_at' => now()]);
        }
        // Restocked from out to low: the out alert is superseded.
        if ($type === 'low_stock') {
            $unread('out_of_stock')->update(['read_at' => now()]);
        }

        // Wording matters: Layout.vue's extractProductName() parses these
        // patterns to highlight the product on the Product List.
        $name  = "{$productName} ({$variant->Label})";
        $title = $qty <= 0 ? 'Out of stock' : 'Low stock';
        $desc  = $qty <= 0
            ? "{$name} is out of stock."
            : "{$name} has only {$qty} units left.";

        $existing = $unread($type)->first();
        if ($existing) {
            // Same alert type: keep it fresh with the current count.
            $existing->update(['description' => $desc]);
            $existing->touch();
            return;
        }

        Notification::create([
            'type'            => $type,
            'title'           => $title,
            'description'     => $desc,
            'notifiable_type' => ProductVariant::class,
            'notifiable_id'   => $variant->VariantID,
            'user_id'         => null, // visible to everyone
        ]);
    }
}

/*
 * NOTE: add() reads $variant->product. If your ProductVariant model does not
 * have this relation yet, add it there:
 *
 *   public function product()
 *   {
 *       return $this->belongsTo(Product::class, 'ProductID', 'ProductID');
 *   }
 */