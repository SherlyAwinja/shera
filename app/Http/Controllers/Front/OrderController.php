<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Front\OrderService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller implements HasMiddleware
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public static function middleware(): array
    {
        return ['auth'];
    }

    /**
     * Display a listing of the user's orders.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = (int) $request->get('per_page', 10);

        $orders = $this->orderService->getUserOrders($user, $perPage);

        return view('front.orders.index', compact('orders'));
    }

    /**
     * Display the specified order details for the authenticated user.
     */
    public function show($orderId)
    {
        $user = Auth::user();

        $order = $this->orderService->getOrderDetails($user, $orderId);

        if (!$order) {
            abort(Response::HTTP_NOT_FOUND, 'Order not found');
        }

        return view('front.orders.show', compact('order'));
    }
}
