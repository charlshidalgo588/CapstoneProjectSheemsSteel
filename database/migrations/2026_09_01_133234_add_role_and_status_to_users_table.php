<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kept as a plain string rather than a separate roles table —
            // there are only a handful of fixed roles (see ROLE_OPTIONS in
            // Users.vue) and no per-permission customization needed yet.
            // Switch to a roles/permissions table later if that changes;
            // it's an additive migration, not a breaking one.
            $table->string('role')->default('Cashier')->after('email');

            // 'active' | 'disabled'. Disabling (not deleting) keeps past
            // sales/inventory rows correctly attributed to the person who
            // made them — see Users.vue's disable confirmation copy.
            $table->string('status')->default('active')->after('role');

            // True right after an admin creates the account or resets the
            // password. The login/auth flow should check this and force a
            // password-change step before letting them into the app.
            $table->boolean('must_change_password')->default(true)->after('status');

            $table->timestamp('last_active_at')->nullable()->after('must_change_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status', 'must_change_password', 'last_active_at']);
        });
    }
};