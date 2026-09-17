<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE sales MODIFY SaleDate DATETIME NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sales MODIFY SaleDate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }
};