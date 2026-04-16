<?php

namespace App\Services\Admin;

use App\Mail\OrderStatusUpdated;
use App\Models\AdminsRole;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderService
{
    public function permissions(): array
    {
        return $this->resolveModulePermissions();
    }

    public function orders(array $filters = []): array
    {
        $permissions = $this->permissions();
        if ($permissions['status'] === 'error') {
            return $permissions;
        }

        $search = trim((string) ($filters['q'] ?? ''));
        $orderStatus = $this->sanitizeEnumFilter($filters['order_status'] ?? null, Order::orderStatusOptions());
        $paymentStatus = $this->sanitizeEnumFilter($filters['payment_status'] ?? null, Order::paymentStatusOptions());
        $paymentMethod = filled($filters['payment_method'] ?? null)
            ? trim((string) $filters['payment_method'])
            : null;

        $perPage = (int) ($filters['per_page'] ?? 25);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $orders = Order::query()
            ->with([
                'user:id,name,email',
                'address:id,full_name,phone,address_line1,address_line2,country,county,sub_county,estate,pincode',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';

                $query->where(function ($innerQuery) use ($like) {
                    $innerQuery
                        ->where('order_number', 'like', $like)
                        ->orWhere('order_uuid', 'like', $like)
                        ->orWhere('recipient_name', 'like', $like)
                        ->orWhere('recipient_phone', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('payment_method', 'like', $like)
                        ->orWhere('payment_status', 'like', $like)
                        ->orWhere('order_status', 'like', $like)
                        ->orWhereHas('user', function ($userQuery) use ($like) {
                            $userQuery
                                ->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });
                });
            })
            ->when($orderStatus, fn ($query) => $query->where('order_status', $orderStatus))
            ->when($paymentStatus, fn ($query) => $query->where('payment_status', $paymentStatus))
            ->when($paymentMethod, fn ($query) => $query->where('payment_method', $paymentMethod))
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'status' => 'success',
            'orders' => $orders,
            'ordersModule' => $permissions['ordersModule'],
            'filters' => [
                'q' => $search,
                'order_status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'per_page' => $perPage,
            ],
            'orderStatusOptions' => Order::orderStatusOptions(),
            'paymentStatusOptions' => Order::paymentStatusOptions(),
            'paymentMethods' => $this->paymentMethods(),
        ];
    }

    public function getOrderDetails(int $id): array
    {
        $permissions = $this->permissions();
        if ($permissions['status'] === 'error') {
            return $permissions;
        }

        $order = Order::query()
            ->with([
                'user:id,name,email',
                'address:id,full_name,phone,address_line1,address_line2,country,county,sub_county,estate,pincode,landmark',
                'items.product:id,product_name',
            ])
            ->find($id);

        if (!$order) {
            return [
                'status' => 'error',
                'message' => 'Order not found.',
            ];
        }

        return [
            'status' => 'success',
            'order' => $order,
            'orderModule' => $permissions['ordersModule'],
            'ordersModule' => $permissions['ordersModule'],
            'orderStatusOptions' => Order::orderStatusOptions(),
            'paymentStatusOptions' => Order::paymentStatusOptions(),
        ];
    }

    public function getOrderDetail(int $id): array
    {
        return $this->getOrderDetails($id);
    }

    public function updateOrder(int $id, array $attributes): array
    {
        $permissions = $this->permissions();
        if ($permissions['status'] === 'error') {
            return $permissions;
        }

        $order = Order::find($id);
        if (!$order) {
            return [
                'status' => 'error',
                'message' => 'Order not found.',
            ];
        }

        $payload = [];

        if (array_key_exists('order_status', $attributes)) {
            $payload['order_status'] = $this->sanitizeEnumFilter($attributes['order_status'], Order::orderStatusOptions());
        }

        if (array_key_exists('payment_status', $attributes)) {
            $payload['payment_status'] = $this->sanitizeEnumFilter($attributes['payment_status'], Order::paymentStatusOptions());
        }

        $payload = array_filter($payload, fn ($value) => $value !== null);

        if (empty($payload)) {
            return [
                'status' => 'error',
                'message' => 'No order updates were provided.',
            ];
        }

        $order->fill($payload);

        if (!$order->placed_at && $order->created_at) {
            $order->placed_at = $order->created_at;
        }

        $order->save();

        return [
            'status' => 'success',
            'message' => 'Order updated successfully.',
            'order' => $order->fresh([
                'user:id,name,email',
                'address:id,full_name,phone,address_line1,address_line2,country,county,sub_county,estate,pincode,landmark',
                'items.product:id,product_name',
            ]),
        ];
    }

    private function paymentMethods(): array
    {
        return Order::query()
            ->whereNotNull('payment_method')
            ->where('payment_method', '!=', '')
            ->orderBy('payment_method')
            ->distinct()
            ->pluck('payment_method')
            ->filter()
            ->values()
            ->all();
    }

    private function sanitizeEnumFilter(mixed $value, array $allowed): ?string
    {
        $normalized = trim((string) $value);

        if ($normalized === '' || !array_key_exists($normalized, $allowed)) {
            return null;
        }

        return $normalized;
    }

    private function resolveModulePermissions(): array
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return ['status' => 'error', 'message' => 'Please log in again to access order management.'];
        }

        if (strtolower((string) $admin->role) === 'admin') {
            return [
                'status' => 'success',
                'ordersModule' => [
                    'view_access' => 1,
                    'edit_access' => 1,
                    'full_access' => 1,
                ],
            ];
        }

        $ordersModule = AdminsRole::where([
            'subadmin_id' => $admin->id,
            'module' => 'orders',
        ])->first();

        if (!$ordersModule) {
            return ['status' => 'error', 'message' => 'You do not have permission to access orders.'];
        }

        $ordersModule = $ordersModule->toArray();

        if (
            empty($ordersModule['view_access'])
            && empty($ordersModule['edit_access'])
            && empty($ordersModule['full_access'])
        ) {
            return ['status' => 'error', 'message' => 'You do not have permission to access orders.'];
        }

        return ['status' => 'success', 'ordersModule' => $ordersModule];
    }

    public function getAllOrderStatuses()
    {
        return OrderStatus::where('status', 1)
            ->orderBy('sort')
            ->get();
    }

    public function updateOrderStatus(int $orderId, array $data)
    {
        $permissions = $this->permissions();
        if ($permissions['status'] === 'error') {
            return $permissions;
        }

        // Find order
        $order = Order::find($orderId);
        if (!$order) {
            return [
                'status' => 'error',
                'message' => 'Order not found'
            ];
        }

        // Find status
        $status = OrderStatus::find($data['order_status_id'] ?? null);
        if (!$status) {
            return [
                'status' => 'error',
                'message' => 'Invalid order status'
            ];
        }

        $orderUpdatePayload = [
            'tracking_number' => $data['tracking_number'] ?? null,
            'tracking_link' => $data['tracking_link'] ?? null,
            'shipping_partner' => $data['shipping_partner'] ?? null,
        ];

        $orderStatus = $this->normalizeOrderStatusName($status->name);
        if ($orderStatus !== null) {
            $orderUpdatePayload['order_status'] = $orderStatus;
        }

        $paymentStatus = $this->normalizePaymentStatusName($status->name);
        if ($paymentStatus !== null) {
            $orderUpdatePayload['payment_status'] = $paymentStatus;
        }

        ['order' => $order, 'log' => $log] = DB::transaction(function () use ($order, $orderUpdatePayload, $status, $data) {
            $order->update($orderUpdatePayload);

            $log = OrderLog::create([
                'order_id' => $order->id,
                'order_status_id' => $status->id,
                'tracking_number' => $data['tracking_number'] ?? null,
                'tracking_link' => $data['tracking_link'] ?? null,
                'shipping_partner' => $data['shipping_partner'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'updated_by' => Auth::guard('admin')->id(),
            ]);

            return [
                'order' => $order->fresh([
                    'user:id,name,email',
                    'address:id,full_name,phone,address_line1,address_line2,country,county,sub_county,estate,pincode,landmark',
                ]),
                'log' => $log->fresh(['status']),
            ];
        });

        if (filled($order->user?->email)) {
            try {
                Mail::to($order->user->email)->queue(new OrderStatusUpdated($order, $log));
            } catch (Throwable $e) {
                Log::error('Failed to queue order status update email.', [
                    'order_id' => $order->id,
                    'order_log_id' => $log->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'status' => 'success',
            'message' => 'Order status updated successfully',
            'order' => $order,
            'log' => $log,
        ];
    }

    private function normalizeOrderStatusName(?string $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'placed', 'pending' => 'placed',
            'confirmed' => 'confirmed',
            'processing' => 'processing',
            'shipped' => 'shipped',
            'delivered' => 'delivered',
            'completed', 'complete' => 'completed',
            'cancelled', 'canceled' => 'cancelled',
            default => null,
        };
    }

    private function normalizePaymentStatusName(?string $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'payment captured' => 'paid',
            'payment failed' => 'failed',
            'pending' => 'pending',
            default => null,
        };
    }

}
