<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $reviewId = $this->route('review');
        $uniqueReviewRule = Rule::unique('reviews')
            ->where(fn ($query) => $query->where('product_id', $this->input('product_id')));

        if ($reviewId) {
            $uniqueReviewRule->ignore($reviewId);
        }

        return [
            'product_id' => 'required|exists:products,id',
            'user_id' => [
                'required',
                'exists:users,id',
                $uniqueReviewRule,
            ],
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
            'status' => 'nullable|in:0,1',
        ];
    }
}
