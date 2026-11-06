<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $add = function (string $column, callable $define) {
            if (!Schema::hasColumn('inventory_logs', $column)) {
                Schema::table('inventory_logs', fn (Blueprint $t) => $define($t));
            }
        };

        $add('SaleID',               fn ($t) => $t->unsignedBigInteger('SaleID')->nullable()->index());
        $add('VariantID',            fn ($t) => $t->unsignedBigInteger('VariantID')->nullable());
        $add('VariantLabel',         fn ($t) => $t->string('VariantLabel', 100)->nullable());
        $add('is_cut',               fn ($t) => $t->boolean('is_cut')->default(false));
        $add('cut_full_length',      fn ($t) => $t->decimal('cut_full_length', 10, 3)->nullable());
        $add('cut_sold_length',      fn ($t) => $t->decimal('cut_sold_length', 10, 3)->nullable());
        $add('cut_remaining_length', fn ($t) => $t->decimal('cut_remaining_length', 10, 3)->nullable());
        $add('cut_unit',             fn ($t) => $t->string('cut_unit', 10)->nullable());

        if (!Schema::hasTable('product_remnants')) {
            Schema::create('product_remnants', function (Blueprint $t) {
                $t->id('RemnantID');
                $t->unsignedBigInteger('ProductID')->index();
                $t->unsignedBigInteger('VariantID')->nullable();
                $t->string('VariantLabel', 100)->nullable();
                $t->unsignedBigInteger('SaleID')->nullable()->index();
                $t->unsignedInteger('Quantity')->default(1);      // number of offcuts of this length
                $t->decimal('Length', 10, 3);                     // length of EACH offcut
                $t->string('Unit', 10)->nullable();
                $t->string('Status', 20)->default('available');   // available | used
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_remnants');

        // VariantID / VariantLabel are left alone: they may have existed before this migration.
        foreach (['SaleID', 'is_cut', 'cut_full_length', 'cut_sold_length', 'cut_remaining_length', 'cut_unit'] as $col) {
            if (Schema::hasColumn('inventory_logs', $col)) {
                Schema::table('inventory_logs', fn (Blueprint $t) => $t->dropColumn($col));
            }
        }
    }
};