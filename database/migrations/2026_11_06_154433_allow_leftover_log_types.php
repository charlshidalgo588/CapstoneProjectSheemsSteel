<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Only needed if `inventory_logs.type` is an ENUM (stock_in / stock_out).
 * Turns it into a plain string so values like "remnant_used" can be saved.
 * Existing rows keep their values.
 *
 * Check first:  SHOW COLUMNS FROM inventory_logs LIKE 'type';
 * If the Type column says varchar(...), you do NOT need this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('inventory_logs', 'type')) {
            return;
        }

        $col = collect(DB::select("SHOW COLUMNS FROM inventory_logs LIKE 'type'"))->first();
        $isEnum = $col && str_starts_with(strtolower($col->Type ?? ''), 'enum');

        if ($isEnum) {
            DB::statement("ALTER TABLE inventory_logs MODIFY `type` VARCHAR(30) NOT NULL");
        }
    }

    public function down(): void
    {
        // Left as VARCHAR on purpose: shrinking it back to an ENUM could reject saved rows.
    }
};