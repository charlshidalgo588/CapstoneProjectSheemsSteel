<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Only adjustable items get the "Adjust line" button at the POS.
            $table->boolean('IsAdjustable')->default(false);
        });

        // OPTIONAL: keep "Adjust line" for every product that has it today.
        // Uncomment to switch all existing products to adjustable.
        // \Illuminate\Support\Facades\DB::table('products')->update(['IsAdjustable' => true]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('IsAdjustable');
        });
    }
};