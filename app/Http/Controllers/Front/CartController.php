<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\CartRequest;
use App\Services\Front\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class CartController extends Controller
{
    protected CartService $service;

    public function __construct(CartService $service)
    {
        $this->service = $service;
    }

    // GET /cart
    public function index()
    {
        $cart = $this->service->getCart();

        return view('front.cart.index', [
            'cartItems' => $cart['items'],
        ] + $this->service->summaryViewData($cart));
    }

    // POST /cart (Add to Cart)
    public function store(CartRequest $request)
    {
        $data = $request->validated();
        $result = $this->service->addToCart($data);

        return response()->json($result, $result['status'] ? 200:422);
    }

    // GET /cart/refresh (AJAX fragments)
    public function refresh()
    {
        $cart = $this->service->getCart();

        $itemsHtml = View::make('front.cart.ajax_cart_items', [
            'cartItems' => $cart['items'],
        ])->render();

        $summaryHtml = View::make('front.cart.ajax_cart_summary', $this->service->summaryViewData($cart))->render();

        return response()->json([
            'items_html'   => $itemsHtml,
            'summary_html' => $summaryHtml,
            'totalCartItems' => totalCartItems(),
        ]);
    }

    // PATCH /cart/{cartId} (Update Quantity)
    public function update(CartRequest $request, $cartId)
    {
        $data = $request->validated();

        $result = $this->service->updateItem((int) $cartId, $data);

        return response()->json($result, $result['status'] ? 200:422);
    }

    // DELETE /cart/{cartId} (Remove Item)
    public function destroy($cartId)
    {
        $result = $this->service->removeItem((int) $cartId);

        return response()->json($result, $result['status'] ? 200:422);
    }

    public function applyWallet(Request $request)
    {
        $data = $request->validate([
            'wallet_amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $result = $this->service->applyWallet((float) $data['wallet_amount']);

        return response()->json($result, $this->responseStatusCode($result));
    }

    public function removeWallet()
    {
        $result = $this->service->removeWallet();

        return response()->json($result, $this->responseStatusCode($result));
    }

    public function checkoutPreview()
    {
        $result = $this->service->checkoutPreview();

        return response()->json($result, $this->responseStatusCode($result));
    }

    public function completeWalletCheckout()
    {
        $result = $this->service->completeWalletCheckout();

        return response()->json($result, $this->responseStatusCode($result));
    }

    protected function responseStatusCode(array $result): int
    {
        if (!empty($result['status'])) {
            return 200;
        }

        return !empty($result['auth_required']) ? 401 : 422;
    }
}
