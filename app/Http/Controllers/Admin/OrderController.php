<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\Admin\OrderService;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService)
    {
    }

    public function index(Request $request)
    {
        Session::put('page', 'orders');
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'order_status' => ['nullable', Rule::in(array_keys(Order::orderStatusOptions()))],
            'payment_status' => ['nullable', Rule::in(array_keys(Order::paymentStatusOptions()))],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', Rule::in([10, 25, 50, 100])],
        ]);

        if ($accessRedirect = $this->redirectIfUnauthorized('view')) {
            return $accessRedirect;
        }

        $result = $this->orderService->orders(
            $request->only('q', 'order_status', 'payment_status', 'payment_method', 'per_page')
        );

        if ($result['status'] === 'error') {
            return redirect('admin/dashboard')->with('error_message', $result['message']);
        }

        return view('admin.orders.index', [
            'orders' => $result['orders'],
            'ordersModule' => $result['ordersModule'],
            'filters' => $result['filters'],
            'orderStatusOptions' => $result['orderStatusOptions'],
            'paymentStatusOptions' => $result['paymentStatusOptions'],
            'paymentMethods' => $result['paymentMethods'],
        ]);
    }

    public function show($id)
    {
        Session::put('page', 'orders');

        if ($accessRedirect = $this->redirectIfUnauthorized('view')) {
            return $accessRedirect;
        }

        $result = $this->orderService->getOrderDetails((int) $id);

        if ($result['status'] === 'error') {
            return redirect()
                ->route('orders.index')
                ->with('error_message', $result['message']);
        }

        $statuses = $this->orderService->getAllOrderStatuses();

        $logs = $result['order']->logs()
            ->with(['status', 'updatedByAdmin'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.orders.show', [
            'order' => $result['order'],
            'orderModule' => $result['orderModule'],
            'ordersModule' => $result['ordersModule'],
            'orderStatusOptions' => $result['orderStatusOptions'],
            'paymentStatusOptions' => $result['paymentStatusOptions'],
            'statuses' => $statuses,
            'logs' => $logs
        ]);
    }

    public function update(Request $request, $id)
    {
        if ($accessRedirect = $this->redirectIfUnauthorized('edit')) {
            return $accessRedirect;
        }

        $validated = $request->validate([
            'order_status' => ['nullable', Rule::in(array_keys(Order::orderStatusOptions()))],
            'payment_status' => ['nullable', Rule::in(array_keys(Order::paymentStatusOptions()))],
        ]);

        $result = $this->orderService->updateOrder((int) $id, $validated);

        return redirect()->back()->with(
            $result['status'] === 'success' ? 'success_message' : 'error_message',
            $result['message']
        );
    }

    public function updateStatus(Request $request, $id)
    {
        if ($accessRedirect = $this->redirectIfUnauthorized('edit')) {
            return $accessRedirect;
        }

        $request->validate([
            'order_status_id'   => 'required|exists:order_statuses,id',
            'tracking_number'   => 'nullable|string|max:255',
            'shipping_partner'  => 'nullable|string|max:255',
            'tracking_link'    => 'nullable|url|max:1000',
            'remarks'           => 'nullable|string|max:1000',
        ]);

        $data = $request->only([
            'order_status_id',
            'tracking_number',
            'tracking_link',
            'shipping_partner',
            'remarks'
        ]);

        $result = $this->orderService->updateOrderStatus($id, $data);

        return redirect()->back()->with(
            $result['status'] === 'success' ? 'success_message' : 'error_message',
            $result['message']
        );
    }

    private function redirectIfUnauthorized(string $level)
    {
        $permissions = $this->orderService->permissions();
        if ($permissions['status'] === 'error' || !$this->hasAccessLevel($permissions['ordersModule'], $level)) {
            return redirect('admin/dashboard')->with('error_message', $permissions['message'] ?? 'You do not have permission to access orders.');
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
