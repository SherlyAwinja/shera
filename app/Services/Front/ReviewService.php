<?php

namespace App\Services\Front;

use App\Models\Review;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReviewService
{
    public function addReview(array $data): array
    {
        $userId = $data['user_id'] ?? Auth::id();
        $productId = $data['product_id'] ?? null;
        $rating = $data['rating'] ?? null;
        $reviewText = $data['review'] ?? null;

        if (!$userId || !$productId || !$rating) {
            return ['status' => 'error', 'message' => 'Invalid data provided.'];
        }
        if (Review::where('product_id', $productId)->where('user_id', $userId)->exists()) {
            return ['status' => 'error', 'message' => 'You have already submitted a review for this product.'];
        }
        try {
            Review::create([
                'product_id' => $productId,
                'user_id' => $userId,
                'rating' => $rating,
                'review' => $reviewText,
                'status' => 0, // pending by default
            ]);
        } catch (QueryException $e) {
            Log::error('Failed to add review: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to submit review. Please try again later.'];
        }
        return ['status' => 'success', 'message' => 'Review submitted successfully.'];


    }
}
