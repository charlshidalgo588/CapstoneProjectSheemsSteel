<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_remnants') && !Schema::hasColumn('product_remnants', 'BasePrice')) {
            Schema::table('product_remnants', function (Blueprint $table) {
                // Baseline price of the leftover: list price of the uncut piece minus what the cut sold for.
                $table->decimal('BasePrice', 10, 2)->nullable()->after('Unit');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_remnants', 'BasePrice')) {
            Schema::table('product_remnants', function (Blueprint $table) {
                $table->dropColumn('BasePrice');
            });
        }
    }
};