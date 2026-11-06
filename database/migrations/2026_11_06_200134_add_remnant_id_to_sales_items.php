<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * sales_items.RemnantID — set when a sale line is a leftover (cut-off) piece.
 * Lets a deleted sale put the leftover back instead of adding a whole piece to stock.
 */
return new class extends Migration
{
    private function itemsTable(): ?string
    {
        return collect(['sales_items', 'sale_items', 'salesitems'])->first(fn ($t) => Schema::hasTable($t));
    }

    public function up(): void
    {
        $table = $this->itemsTable();
        if ($table && !Schema::hasColumn($table, 'RemnantID')) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('RemnantID')->nullable();
            });
        }
    }

    public function down(): void
    {
        $table = $this->itemsTable();
        if ($table && Schema::hasColumn($table, 'RemnantID')) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('RemnantID');
            });
        }
    }
};