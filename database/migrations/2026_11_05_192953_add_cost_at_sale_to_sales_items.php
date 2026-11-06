<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_items', function (Blueprint $table) {
            $table->decimal('CostAtSale', 12, 2)->nullable()->after('PriceAtSale');
        });

        // Backfill old rows: size cost if the sale had a size, otherwise product cost.
        DB::statement('
            UPDATE sales_items si
            LEFT JOIN product_variants pv ON pv.VariantID = si.VariantID
            JOIN products p ON p.ProductID = si.ProductID
            SET si.CostAtSale = COALESCE(pv.CostPrice, p.CostPrice, 0)
            WHERE si.CostAtSale IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('sales_items', function (Blueprint $table) {
            $table->dropColumn('CostAtSale');
        });
    }
};