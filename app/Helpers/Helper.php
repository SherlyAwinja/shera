<?php

use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

if (!function_exists('totalCartItems')) {

    /**
     * Return total cart items quantity for current owner (user or session)
     *
     * @return int
     */
    function totalCartItems(): int
    {
        // If user is logged in
        if (Auth::check()) {
            return (int) Cart::where('user_id', Auth::id())
                ->sum('product_qty');
        }

        // If user is a guest, use session
        $sessionId = Session::get('session_id');

        if (!$sessionId) {
            $sessionId = Session::getId();
            Session::put('session_id', $sessionId);
        }

        return (int) Cart::where('session_id', $sessionId)
            ->sum('product_qty');
    }
}
