<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Signed on purpose: old data may already contain negative stock.
            $table->integer('QuantityOnHand')->default(0)->after('CostPrice');
            $table->unsignedInteger('ReorderLevel')->default(0)->after('QuantityOnHand');
        });

        // Logs can now say WHICH size was changed (snapshot of the label too).
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('VariantID')->nullable()->after('ProductID');
            $table->string('VariantLabel', 150)->nullable()->after('VariantID');
        });

        // Backfill: copy each product's current stock to its FIRST size only,
        // so totals are not duplicated when a product already has several sizes.
        $done = [];
        DB::table('product_variants')->orderBy('VariantID')->chunk(200, function ($variants) use (&$done) {
            foreach ($variants as $v) {
                if (isset($done[$v->ProductID])) {
                    continue;
                }
                $done[$v->ProductID] = true;

                $inv = DB::table('inventories')->where('ProductID', $v->ProductID)->first();
                if (!$inv) {
                    continue;
                }

                DB::table('product_variants')->where('VariantID', $v->VariantID)->update([
                    'QuantityOnHand' => (int) $inv->QuantityOnHand,
                    'ReorderLevel'   => max(0, (int) ($inv->ReorderLevel ?? 0)),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->dropColumn(['VariantID', 'VariantLabel']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['QuantityOnHand', 'ReorderLevel']);
        });
    }
};