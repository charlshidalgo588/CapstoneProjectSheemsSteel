<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'sales_items';

    public function up(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            if (!Schema::hasColumn($this->table, 'VariantID')) {
                $table->unsignedBigInteger('VariantID')->nullable()->after('ProductID');
            }
            if (!Schema::hasColumn($this->table, 'VariantLabel')) {
                $table->string('VariantLabel')->nullable()->after('VariantID');
            }
        });
    }

    public function down(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            if (Schema::hasColumn($this->table, 'VariantLabel')) {
                $table->dropColumn('VariantLabel');
            }
            if (Schema::hasColumn($this->table, 'VariantID')) {
                $table->dropColumn('VariantID');
            }
        });
    }
};