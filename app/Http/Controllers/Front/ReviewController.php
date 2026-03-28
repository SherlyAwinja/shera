<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Front\ReviewService;
use App\Http\Requests\Front\ReviewSubmitRequest;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    protected ReviewService $service;

    public function __construct(ReviewService $service)
    {
        $this->service = $service;
    }

    public function store(ReviewSubmitRequest $request)
    {
        return $this->submit($request);
    }

    public function submit(ReviewSubmitRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();
        $result = $this->service->addReview($data);
        // Return JSON (for AJAX) or redirect back for normal submit
        if ($request->expectsJson()  || $request->wantsJson()) {
            return response()->json($result, $result['status'] === 'success' ? 200 : 422);
        }
        return back()->with($result['status'] . '_message', $result['message']);
    }
}
