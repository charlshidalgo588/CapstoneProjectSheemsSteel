<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AllocatedCost / AllocatedProfit are LINE TOTALS (all Quantity units of the line),
 * filled only for cut-piece lines and leftover (remnant) lines.
 * NULL = normal line -> reports keep using (PriceAtSale - CostAtSale) * Quantity,
 * so every historical sale keeps its old profit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_items', function (Blueprint $table) {
            $table->decimal('AllocatedCost', 12, 2)->nullable()->after('CostAtSale');
            $table->decimal('AllocatedProfit', 12, 2)->nullable()->after('AllocatedCost');
        });
    }

    public function down(): void
    {
        Schema::table('sales_items', function (Blueprint $table) {
            $table->dropColumn(['AllocatedCost', 'AllocatedProfit']);
        });
    }
};