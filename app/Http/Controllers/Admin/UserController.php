<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\UserService;
use App\Http\Requests\Admin\UserFilterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\ColumnPreference;

class UserController extends Controller
{
    protected $userService;

    // Constructor injection
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    // Display users list
    public function index(UserFilterRequest $request)
    {
        Session::put('page', 'users');

        $result = $this->userService->users($request->validated());

        // Check for restricted access
        if (isset($result['status']) && $result['status'] === "error") {
            return redirect('admin/dashboard')->with('error_message', $result['message']);
        }

        $users = $result['users'];
        $usersModule = $result['usersModule'] ?? [];

        // Column preferences for current admin
        $columnPrefs = ColumnPreference::where('admin_id', Auth::guard('admin')->id())
            ->where('table_name', 'users')
            ->first();

        $usersSavedOrder = $columnPrefs ? json_decode($columnPrefs->column_order, true) : null;
        $usersHiddenCols = $columnPrefs ? json_decode($columnPrefs->hidden_columns, true) : [];

        return view('admin.users.index')->with(compact(
            'users',
            'usersModule',
            'usersSavedOrder',
            'usersHiddenCols'
        ));
    }

    // Update user status via AJAX
    public function updateUserStatus(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        if ($request->ajax()) {
            $data = $request->all();
            $status = $this->userService->toggleStatus($data);

            return response()->json([
                'status' => $status,
                'user_id' => $data['user_id']
            ]);
        }

        abort(400);
    }
}
