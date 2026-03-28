<?php

namespace App\Services\Front;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    /**
     * Register a new user
     *
     * @param array $data
     * @return User
     */
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
            'user_type' => $data['user_type'] ?? 'Customer',
            'status' => 1,
        ]);

        return $user;
    }

    /**
     * Attempt to log in an active user, optionally constrained by user type.
     */
    public function attemptLogin(array $credentials, bool $remember = false): bool
    {
        $user = User::query()
            ->where('email', $credentials['email'])
            ->when(
                !empty($credentials['user_type']),
                fn ($query) => $query->where('user_type', $credentials['user_type'])
            )
            ->first();

        if (!$user || (int) $user->status === 0) {
            return false;
        }

        $attemptCredentials = array_filter([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'user_type' => $credentials['user_type'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');

        if (Auth::attempt($attemptCredentials, $remember)) {
            session()->regenerate();
            return true;
        }

        return false;
    }
}
