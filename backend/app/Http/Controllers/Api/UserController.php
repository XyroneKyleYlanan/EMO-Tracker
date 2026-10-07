<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'is_active', 'created_at']);

        return response()->json(['users' => $users]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['admin', 'officer', 'staff'])],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'role', 'is_active']),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'role', 'is_active', 'created_at']),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['sometimes', Rule::in(['admin', 'officer', 'staff'])],
            'is_active' => ['sometimes', 'boolean'],
            'password' => ['sometimes', 'string', 'min:8'],
        ]);

        // Keeps at least one active admin: the acting admin can't lock themselves out.
        if ($user->id === $request->user()->id) {
            if (array_key_exists('is_active', $data) && ! $data['is_active']) {
                return response()->json(['message' => 'You cannot deactivate yourself.'], 422);
            }
            if (array_key_exists('role', $data) && $data['role'] !== 'admin') {
                return response()->json(['message' => 'You cannot remove your own administrator role.'], 422);
            }
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        // A deactivated account, or one given a new password, is signed out
        // everywhere (an admin resetting their own stays signed in here).
        if (! $user->is_active || isset($data['password'])) {
            $user->signOutEverywhere(except: $user->is($request->user()) ? $request->user()->currentAccessToken() : null);
        }

        return response()->json([
            'user' => $user->fresh()->only(['id', 'name', 'email', 'role', 'is_active']),
        ]);
    }

    public function destroy(User $user, Request $request): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot deactivate yourself.'], 422);
        }

        $user->update(['is_active' => false]);
        $user->tokens()->delete();

        return response()->json(['message' => 'User deactivated.']);
    }
}
