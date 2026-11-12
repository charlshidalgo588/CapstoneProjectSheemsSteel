<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Single source of truth for allowed roles — mirror any change in
    // Settings.vue's ROLE_OPTIONS and in src/utils/permissions.js.
    private const ROLES = ['Administrator', 'Cashier', 'Inventory Staff'];

    public function index(Request $request)
    {
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

        // Plaintext, once, on this response only.
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

        $isSelf = $user->id === $request->user()->id;

        if ($isSelf && ($data['status'] ?? null) === 'disabled') {
            return response()->json(['message' => "You can\u{2019}t disable your own account."], 422);
        }

        if ($isSelf && isset($data['role']) && $data['role'] !== 'Administrator') {
            return response()->json(['message' => "You can\u{2019}t remove your own administrator access."], 422);
        }

        // Never leave the system without an active administrator.
        $losesAdmin =
            $user->role === 'Administrator' && $user->status === 'active' && (
                (isset($data['role']) && $data['role'] !== 'Administrator') ||
                (($data['status'] ?? null) === 'disabled')
            );

        if ($losesAdmin) {
            $otherAdmins = User::where('role', 'Administrator')
                ->where('status', 'active')
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherAdmins === 0) {
                return response()->json(['message' => 'At least one active administrator is required.'], 422);
            }
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
        $groups = collect(range(1, 3))->map(fn () => Str::lower(Str::random(4)));

        return $groups->implode('-');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'Administrator', 403, 'Administrators only.');
    }
}