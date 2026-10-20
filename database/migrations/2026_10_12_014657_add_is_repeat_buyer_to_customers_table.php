<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IsRepeatBuyer = true means the client listed this person as a repeat buyer,
 * so they qualify immediately (no 10 purchases needed). Everyone else is
 * tracked from their POS sales and qualifies automatically at 10 purchases.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'IsRepeatBuyer')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->boolean('IsRepeatBuyer')->default(false);
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: never drop columns that may hold real data.
    }
};