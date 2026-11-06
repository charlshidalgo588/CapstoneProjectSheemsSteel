<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * product_remnants.FullLength — length of the ORIGINAL whole piece.
 * Lets the POS suggest a price and lets the sale book the leftover's share of the cost.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_remnants') && !Schema::hasColumn('product_remnants', 'FullLength')) {
            Schema::table('product_remnants', function (Blueprint $t) {
                $t->decimal('FullLength', 10, 3)->nullable()->after('Length');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_remnants', 'FullLength')) {
            Schema::table('product_remnants', function (Blueprint $t) {
                $t->dropColumn('FullLength');
            });
        }
    }
};