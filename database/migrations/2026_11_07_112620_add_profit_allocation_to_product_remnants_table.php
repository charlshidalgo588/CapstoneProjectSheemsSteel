<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A leftover piece now carries the cost and profit that were NOT booked when its
 * parent piece was cut. RemainingCost / RemainingProfit are TOTALS for the whole
 * remnant row (all `Quantity` pieces of it). NULL = legacy row (no allocation known).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_remnants', function (Blueprint $table) {
            $table->decimal('RemainingCost', 12, 2)->nullable()->after('BasePrice');
            $table->decimal('RemainingProfit', 12, 2)->nullable()->after('RemainingCost');
        });

        // Best-effort backfill for leftovers that are still on the shelf, using today's
        // cost / list price: remaining share = Length / FullLength.
        $variantTable = collect(['product_variants', 'variants'])->first(fn ($t) => Schema::hasTable($t));

        $rows = DB::table('product_remnants')
            ->where('Status', 'available')
            ->whereNotNull('FullLength')
            ->where('FullLength', '>', 0)
            ->get();

        foreach ($rows as $r) {
            $product = DB::table('products')->where('ProductID', $r->ProductID)->first();
            $variant = ($variantTable && $r->VariantID)
                ? DB::table($variantTable)->where('VariantID', $r->VariantID)->first()
                : null;

            $cost  = (float) ($variant->CostPrice ?? $product->CostPrice ?? 0);
            $list  = (float) ($variant->SellingPrice ?? $product->SellingPrice ?? 0);
            $qty   = max((int) $r->Quantity, 1);
            $share = min(max((float) $r->Length / (float) $r->FullLength, 0), 1);

            DB::table('product_remnants')->where('RemnantID', $r->RemnantID)->update([
                'RemainingCost'   => round($qty * $cost * $share, 2),
                'RemainingProfit' => round($qty * ($list - $cost) * $share, 2),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('product_remnants', function (Blueprint $table) {
            $table->dropColumn(['RemainingCost', 'RemainingProfit']);
        });
    }
};