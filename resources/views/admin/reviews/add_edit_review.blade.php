@extends('admin.layout.layout')

@section('content')
@php
    $isEdit = isset($review) && $review->exists;
@endphp

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Review Management</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ url('admin/dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('reviews.index') }}">Reviews</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="card card-primary card-outline mb-4">
                        <div class="card-header">
                            <div class="card-title">{{ $title }}</div>
                        </div>

                        @if (Session::has('error_message'))
                            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                                <strong>Error!</strong> {{ Session::get('error_message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if (Session::has('success_message'))
                            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                                <strong>Success!</strong> {{ Session::get('success_message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @foreach ($errors->all() as $error)
                            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                                <strong>Error!</strong> {{ $error }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endforeach

                        <form
                            name="reviewForm"
                            id="reviewForm"
                            action="{{ $isEdit ? route('reviews.update', $review->id) : route('reviews.store') }}"
                            method="post"
                        >
                            @csrf
                            @if($isEdit)
                                @method('PUT')
                            @endif

                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label" for="product_id">Product*</label>
                                    <select class="form-control" id="product_id" name="product_id" required>
                                        <option value="">Select product</option>
                                        @foreach($products as $product)
                                            <option
                                                value="{{ $product->id }}"
                                                @selected((string) old('product_id', $review->product_id ?? '') === (string) $product->id)
                                            >
                                                {{ $product->product_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="user_id">User*</label>
                                    <select class="form-control" id="user_id" name="user_id" required>
                                        <option value="">Select user</option>
                                        @foreach($users as $user)
                                            @php
                                                $userLabel = $user->name ? $user->name . ' (' . $user->email . ')' : $user->email;
                                            @endphp
                                            <option
                                                value="{{ $user->id }}"
                                                @selected((string) old('user_id', $review->user_id ?? '') === (string) $user->id)
                                            >
                                                {{ $userLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Each user can have only one review per product.</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="rating">Rating*</label>
                                    <select class="form-control" id="rating" name="rating" required>
                                        <option value="">Select rating</option>
                                        @foreach([1, 2, 3, 4, 5] as $rating)
                                            <option
                                                value="{{ $rating }}"
                                                @selected((string) old('rating', $review->rating ?? '') === (string) $rating)
                                            >
                                                {{ $rating }} Star{{ $rating > 1 ? 's' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="review">Review</label>
                                    <textarea
                                        class="form-control"
                                        id="review"
                                        name="review"
                                        rows="5"
                                        placeholder="Enter review text"
                                    >{{ old('review', $review->review ?? '') }}</textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="0" @selected((string) old('status', $review->status ?? '0') === '0')>Pending</option>
                                        <option value="1" @selected((string) old('status', $review->status ?? '') === '1')>Approved</option>
                                    </select>
                                </div>
                            </div>

                            <div class="card-footer d-flex justify-content-between align-items-center">
                                <a href="{{ route('reviews.index') }}" class="btn btn-outline-secondary">Back</a>
                                <button type="submit" class="btn btn-primary">Save Review</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Review Info</h3>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><strong>Mode:</strong> {{ $isEdit ? 'Edit existing review' : 'Create new review' }}</p>
                            @if($isEdit)
                                <p class="mb-2"><strong>Review ID:</strong> {{ $review->id }}</p>
                                <p class="mb-0"><strong>Created:</strong> {{ optional($review->created_at)->format('F j, Y, g:i a') ?: 'N/A' }}</p>
                            @else
                                <p class="mb-0 text-muted">Use this form to add or approve a customer review manually.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
