<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\ReviewRequest;
use App\Services\Admin\ReviewService;
use App\Models\Review;
use App\Models\Product;
use App\Models\User;
use App\Models\ColumnPreference;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;



class ReviewController extends Controller
{
    protected $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Session::put('page', 'reviews');
        $result = $this->reviewService->reviews();
        if ($result['status'] == "error") {
            return redirect('admin/dashboard')->with('error_message', $result['message']);
        }
        $reviews = $result['reviews'];
        $reviewsModule = $result['reviewsModule'];

        $columnPrefs = ColumnPreference::where('admin_id', Auth::guard('admin')->id())->where('table_name', 'reviews')->first();
        $reviewsSavedOrder = $columnPrefs ? json_decode($columnPrefs->column_order, true) : null;
        $reviewsHiddenColumns = $columnPrefs ? json_decode($columnPrefs->hidden_columns, true) : [];

        return view('admin.reviews.index', compact('reviews', 'reviewsModule', 'reviewsSavedOrder', 'reviewsHiddenColumns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Session::put('page', 'reviews');
        $title = 'Add Review';
        $review = new Review();
        $products = Product::orderBy('product_name')->get(['id', 'product_name']);
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.reviews.add_edit_review', compact('title', 'review', 'products', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ReviewRequest $request)
    {
        $message = $this->reviewService->addEditReview($request);
        return redirect()->route('reviews.index')->with('success_message', $message);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        Session::put('page', 'reviews');
        $title = 'Edit Review';
        $review = Review::findOrFail($id);
        $products = Product::orderBy('product_name')->get(['id', 'product_name']);
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.reviews.add_edit_review', compact('title', 'review', 'products', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ReviewRequest $request, string $id)
    {
        $request->merge(['id' => $id]);
        $message = $this->reviewService->addEditReview($request);
        return redirect()->route('reviews.index')->with('success_message', $message);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $result = $this->reviewService->deleteReview($id);
        return redirect()->back()->with('success_message', $result['message']);
    }

    public function updateReviewStatus(Request $request)
    {
        if($request->ajax()) {
            $data = $request->all();
            $status = $this->reviewService->updateReviewStatus($data);
            return response()->json(['status' => $status, 'review_id' => $data['review_id']]);
        }
    }
}
