<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

trait VerifiesAdminCredential
{
    /** True if the secret matches ANY active Administrator's password. */
    protected function adminCredentialIsValid(?string $secret): bool
    {
        if (! $secret) {
            return false;
        }

        return User::query()
            ->where('role', 'Administrator')
            ->where('status', 'active')
            ->get(['id', 'password'])
            ->contains(fn ($admin) => Hash::check($secret, $admin->password));
    }

    /**
     * Call this at the top of SaleController::bulkDelete(), replacing the old
     * "check the logged-in user's own password" block.
     * Works for admins (their own password is an admin password) and for
     * cashiers (they must get an admin to type theirs).
     */
    protected function authorizeDeletion(Request $request): void
    {
        $request->validate(['admin_password' => ['required', 'string']]);

        if (! $this->adminCredentialIsValid($request->input('admin_password'))) {
            abort(422, 'Incorrect administrator password.');
        }
    }
}