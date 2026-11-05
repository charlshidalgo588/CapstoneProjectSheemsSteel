<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id('VariantID');
            $table->unsignedBigInteger('ProductID');
            $table->string('Label', 150);              // e.g. "8 ft × 3 ft × 0.40 mm" — shown in the POS dropdown
            $table->json('Specs')->nullable();         // [{Type, Value, Unit}, ...]
            $table->decimal('SellingPrice', 12, 2);
            $table->decimal('CostPrice', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('ProductID')
                ->references('ProductID')->on('products')
                ->cascadeOnDelete();
            $table->index('ProductID');
        });

        // Sales keep a snapshot of the size so history survives variant edits/deletes.
        Schema::table('sales_items', function (Blueprint $table) {
            $table->unsignedBigInteger('VariantID')->nullable()->after('ProductID');
            $table->string('VariantLabel', 150)->nullable()->after('VariantID');
        });

        // Backfill: give every existing product one "Standard" variant so
        // old products keep working in the new POS dropdown.
        DB::table('products')->orderBy('ProductID')->chunk(200, function ($products) {
            foreach ($products as $p) {
                DB::table('product_variants')->insert([
                    'ProductID'    => $p->ProductID,
                    'Label'        => 'Standard',
                    'Specs'        => json_encode([]),
                    'SellingPrice' => $p->SellingPrice ?? 0,
                    'CostPrice'    => $p->CostPrice ?? 0,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_items', function (Blueprint $table) {
            $table->dropColumn(['VariantID', 'VariantLabel']);
        });
        Schema::dropIfExists('product_variants');
    }
};