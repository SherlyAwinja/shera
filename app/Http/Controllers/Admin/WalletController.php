<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WalletRequest;
use App\Models\ColumnPreference;
use App\Models\Wallet;
use App\Services\Admin\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService)
    {
    }

    public function index(Request $request)
    {
        Session::put('page', 'wallets');
        $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        if ($accessRedirect = $this->redirectIfUnauthorized('view')) {
            return $accessRedirect;
        }

        $result = $this->walletService->wallets($request->only('user_id'));
        $columnPrefs = ColumnPreference::where('admin_id', Auth::guard('admin')->id())
            ->where('table_name', 'wallets')
            ->first();

        $walletsSavedOrder = $columnPrefs ? json_decode($columnPrefs->column_order, true) : null;
        $walletsHiddenCols = $columnPrefs ? json_decode($columnPrefs->hidden_columns, true) : [];
        $selectedUser = $result['users']->firstWhere('id', $result['selectedUserId']);

        return view('admin.wallets.index', [
            'wallets' => $result['wallets'],
            'walletsModule' => $result['walletsModule'],
            'users' => $result['users'],
            'selectedUserId' => $result['selectedUserId'],
            'selectedUser' => $selectedUser,
            'selectedUserBalance' => $result['selectedUserBalance'],
            'walletsSavedOrder' => $walletsSavedOrder,
            'walletsHiddenCols' => $walletsHiddenCols,
        ]);
    }

    public function create(Request $request)
    {
        Session::put('page', 'wallets');
        $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        if ($accessRedirect = $this->redirectIfUnauthorized('edit')) {
            return $accessRedirect;
        }

        $wallet = new Wallet();
        $wallet->user_id = $request->integer('user_id') ?: null;
        $wallet->action = Wallet::ACTION_CREDIT;
        $wallet->status = true;
        $wallet->expiry_date = now()->addYear();

        return view('admin.wallets.add_edit_wallet', [
            'title' => 'Add Wallet Entry',
            'wallet' => $wallet,
            'users' => $this->walletService->walletUsers(),
            'returnUserId' => $request->integer('user_id') ?: null,
        ]);
    }

    public function store(WalletRequest $request)
    {
        if ($accessRedirect = $this->redirectIfUnauthorized('edit')) {
            return $accessRedirect;
        }

        $wallet = $this->walletService->saveWallet($request->validated());

        return redirect()
            ->route('wallets.index', ['user_id' => $wallet->user_id])
            ->with('success_message', 'Wallet entry added successfully.');
    }

    public function edit(string $id)
    {
        Session::put('page', 'wallets');
        if ($accessRedirect = $this->redirectIfUnauthorized('edit')) {
            return $accessRedirect;
        }

        $wallet = Wallet::findOrFail($id);

        return view('admin.wallets.add_edit_wallet', [
            'title' => 'Edit Wallet Entry',
            'wallet' => $wallet,
            'users' => $this->walletService->walletUsers(),
            'returnUserId' => $wallet->user_id,
        ]);
    }

    public function update(WalletRequest $request, string $id)
    {
        if ($accessRedirect = $this->redirectIfUnauthorized('edit')) {
            return $accessRedirect;
        }

        $wallet = $this->walletService->saveWallet($request->validated(), (int) $id);

        return redirect()
            ->route('wallets.index', ['user_id' => $wallet->user_id])
            ->with('success_message', 'Wallet entry updated successfully.');
    }

    public function destroy(string $id)
    {
        if ($accessRedirect = $this->redirectIfUnauthorized('full')) {
            return $accessRedirect;
        }

        $result = $this->walletService->deleteWallet((int) $id);

        return redirect()
            ->route('wallets.index', ['user_id' => $result['user_id']])
            ->with('success_message', $result['message']);
    }

    public function updateWalletStatus(Request $request)
    {
        $request->validate([
            'wallet_id' => ['required', 'exists:wallets,id'],
        ]);

        if ($accessResponse = $this->jsonIfUnauthorized('edit')) {
            return $accessResponse;
        }

        return response()->json(
            $this->walletService->toggleStatus((int) $request->input('wallet_id'))
        );
    }

    public function liveBalance(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'wallet_id' => ['nullable', 'exists:wallets,id'],
        ]);

        if ($accessResponse = $this->jsonIfUnauthorized('view')) {
            return $accessResponse;
        }

        return response()->json(
            $this->walletService->liveBalance(
                (int) $request->input('user_id'),
                $request->filled('wallet_id') ? (int) $request->input('wallet_id') : null
            )
        );
    }

    private function redirectIfUnauthorized(string $level)
    {
        $permissions = $this->walletService->permissions();
        if ($permissions['status'] === 'error' || !$this->hasAccessLevel($permissions['walletsModule'], $level)) {
            return redirect('admin/dashboard')->with('error_message', $permissions['message'] ?? 'You do not have permission to access wallets.');
        }

        return null;
    }

    private function jsonIfUnauthorized(string $level)
    {
        $permissions = $this->walletService->permissions();
        if ($permissions['status'] === 'error' || !$this->hasAccessLevel($permissions['walletsModule'], $level)) {
            return response()->json([
                'message' => $permissions['message'] ?? 'You do not have permission to access wallets.',
            ], 403);
        }

        return null;
    }

    private function hasAccessLevel(array $module, string $level): bool
    {
        if ($level === 'full') {
            return !empty($module['full_access']);
        }

        if ($level === 'edit') {
            return !empty($module['edit_access']) || !empty($module['full_access']);
        }

        return !empty($module['view_access']) || !empty($module['edit_access']) || !empty($module['full_access']);
    }
}
