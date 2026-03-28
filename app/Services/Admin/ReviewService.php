<?php

namespace App\Services\Admin;

use App\Models\Review;
use App\Models\AdminsRole;
use Illuminate\Support\Facades\Auth;

class ReviewService
{
    public function reviews(): array
    {
        $admin = Auth::guard('admin')->user();
        $reviews = Review::with(['product', 'user'])->orderBy('id', 'desc')->get();
        $isAdmin = strtolower((string) $admin->role) === 'admin';

        if ($isAdmin) {
            $reviewsModule = [
                'view_access' => 1,
                'edit_access' => 1,
                'full_access' => 1,
            ];
        } else {
            $reviewsModule = AdminsRole::where([
                'subadmin_id' => $admin->id,
                'module' => 'reviews',
            ])->first();

            if (!$reviewsModule) {
                return ['status' => 'error', 'message' => 'You do not have permission to access reviews.'];
            }

            $reviewsModule = $reviewsModule->toArray();
        }

        if (
            empty($reviewsModule['view_access'])
            && empty($reviewsModule['edit_access'])
            && empty($reviewsModule['full_access'])
        ) {
            return ['status' => 'error', 'message' => 'You do not have permission to access reviews.'];
        }

        return ['status' => 'success', 'reviews' => $reviews, 'reviewsModule' => $reviewsModule];
    }

    public function addEditReview($request): string
    {
        $data = is_array($request)
            ? $request
            : $request->only(['id', 'product_id', 'user_id', 'rating', 'review', 'status']);

        $review = !empty($data['id'])
            ? Review::find($data['id'])
            : new Review();

        if (!$review) {
            return 'Review not found.';
        }

        $review->product_id = $data['product_id'];
        $review->user_id = $data['user_id'];
        $review->rating = $data['rating'];
        $review->review = $data['review'] ?? null;
        $review->status = isset($data['status']) ? (int) $data['status'] : ($review->status ?? 0);
        $review->save();

        return !empty($data['id']) ? 'Review updated successfully.' : 'Review added successfully.';
    }

    public function updateReviewStatus(array $data)
    {
        $status = ($data['status'] == "Active") ? 0 : 1;
        Review::where('id', $data['review_id'])->update(['status' => $status]);
        return $status;
    }

    public function deleteReview($id): array
    {
        $review = Review::find($id);
        if (!$review) return ['status' => 'error', 'message' => 'Review not found.'];
        $review->delete();
        return ['status' => 'success', 'message' => 'Review deleted successfully.'];
    }
}
