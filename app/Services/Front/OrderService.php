<?php

namespace App\Services\Front;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderService
{
    /**
     * Get paginated orders for a specific user.
     *
     * @param  \App\Models\User  $user
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function getUserOrders($user, int $perPage = 10): LengthAwarePaginator
    {
        return Order::where('user_id', $user->id)
            ->with(['items.product', 'address']) // Removed payment and shippingAddress
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get details of a specific order for a user.
     *
     * @param  \App\Models\User  $user
     * @param  int  $orderId
     * @return Order|null
     */
    public function getOrderDetails($user, $orderId)
    {
        return Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->with(['items.product', 'address']) // Removed payment and shippingAddress
             ->orderBy('created_at', 'desc')
             ->first();
    }
}
