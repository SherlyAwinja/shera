<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\AdminsRole;
use Illuminate\Support\Facades\Auth;

class UserService
{
    // Fetch users with optional filters
    public function users(array $filters = []): array
    {
        $query = User::query()
            ->select('users.*')
            ->withWalletBalance();

        // Apply search filter
        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function($q) use ($s) {
                $q->where('email', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%");
            });
        }

        // Paginate users
        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get current admin
        $admin = Auth::guard('admin')->user();

        $status = "success";
        $message = "";
        $usersModule = [];

        if ($admin) {
            if ($admin->role == "admin") {
                // Full access for main admin
                $usersModule = [
                    'view_access' => 1,
                    'edit_access' => 1,
                    'full_access' => 1
                ];
            } else {
                // Sub-admin access
                $usersModuleCount = AdminsRole::where([
                    'subadmin_id' => $admin->id,
                    'module' => 'users'
                ])->count();

                if ($usersModuleCount == 0) {
                    $status = "error";
                    $message = "This feature is restricted for you!";
                } else {
                    $usersModule = AdminsRole::where([
                        'subadmin_id' => $admin->id,
                        'module' => 'users'
                    ])->first()->toArray();
                }
            }
        }

        return [
            'users' => $users,
            'usersModule' => $usersModule,
            'status' => $status,
            'message' => $message
        ];
    }

    // Toggle user status
    public function toggleStatus($data)
    {
        $user = User::findOrFail($data['user_id']);
        $user->status = $user->status ? 0 : 1;
        $user->save();

        return $user->status;
    }
}
