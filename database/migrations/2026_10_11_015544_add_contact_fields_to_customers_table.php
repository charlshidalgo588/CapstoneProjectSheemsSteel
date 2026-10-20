<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Your `sales` table already has a CustomerID foreign key to `customers`,
 * so that table probably exists. This migration is safe either way:
 *  - table missing  -> it is created
 *  - table exists   -> only the missing columns are added
 *
 * Check what you already have first:  DESCRIBE customers;
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id('CustomerID');
                $table->string('CustomerCode')->unique();
                $table->string('CustomerName');
                $table->string('ContactNumber', 20)->nullable()->unique();
                $table->string('Address')->nullable();
                $table->timestamps();
            });
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'CustomerName')) {
                $table->string('CustomerName')->default('');
            }
            if (! Schema::hasColumn('customers', 'ContactNumber')) {
                // No unique index here: existing rows could contain duplicates.
                $table->string('ContactNumber', 20)->nullable()->index();
            }
            if (! Schema::hasColumn('customers', 'Address')) {
                $table->string('Address')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally empty: never drop a table that may hold real customer data.
    }
};