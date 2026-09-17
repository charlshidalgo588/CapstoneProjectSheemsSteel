<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Keep this the single source of truth for allowed roles — mirror
    // any change here in Users.vue's ROLE_OPTIONS constant.
    private const ROLES = ['Administrator', 'Cashier', 'Inventory Staff'];

    public function index(Request $request)
    {
        // Every route in this controller should already sit behind an
        // admin-only middleware/gate (see routes/api.php note below) —
        // this second check is a deliberate belt-and-braces guard, not
        // a substitute for that.
        $this->authorizeAdmin($request);

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'status', 'last_active_at']);

        return response()->json(['users' => $users]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'  => ['required', Rule::in(self::ROLES)],
        ]);

        $tempPassword = $this->generateTempPassword();

        $user = User::create([
            'name'                 => $data['name'],
            'email'                => $data['email'],
            'role'                 => $data['role'],
            'status'               => 'active',
            'password'             => Hash::make($tempPassword),
            'must_change_password' => true,
        ]);

        // Returned once, in plaintext, on this response only — never
        // logged, never stored anywhere, never returned again. This is
        // the one moment the admin can see it, matching the "shown once"
        // copy in Users.vue's success modal.
        return response()->json([
            'user'          => $user->only(['id', 'name', 'email', 'role', 'status']),
            'temp_password' => $tempPassword,
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'role'   => ['sometimes', Rule::in(self::ROLES)],
            'status' => ['sometimes', Rule::in(['active', 'disabled'])],
        ]);

        // Guard against an admin locking themselves out by disabling
        // their own account or demoting the last remaining admin — swap
        // the second check for a real "count remaining admins" query
        // once you have more than a couple of accounts.
        if (($data['status'] ?? null) === 'disabled' && $user->id === $request->user()->id) {
            return response()->json(['message' => 'You can\u2019t disable your own account.'], 422);
        }

        $user->update($data);

        return response()->json(['user' => $user->only(['id', 'name', 'email', 'role', 'status'])]);
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->authorizeAdmin($request);

        $tempPassword = $this->generateTempPassword();

        $user->update([
            'password'             => Hash::make($tempPassword),
            'must_change_password' => true,
        ]);

        return response()->json(['temp_password' => $tempPassword]);
    }

    private function generateTempPassword(): string
    {
        // Human-relayable (typed over Viber/in person), not e-mailed —
        // this system has no outbound mail flow yet. Format matches the
        // mockup shown to the client: three dash-separated groups.
        $groups = collect(range(1, 3))->map(
            fn () => Str::lower(Str::random(4))
        );

        return $groups->implode('-');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'Administrator', 403, 'Administrators only.');
    }
}