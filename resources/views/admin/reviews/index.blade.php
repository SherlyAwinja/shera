@extends('admin.layout.layout')

@section('content')
<main class="app-main">

    {{-- Header --}}
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Review Management</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="#">Home</a>
                        </li>
                        <li class="breadcrumb-item active">Reviews</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">

                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Reviews</h3>

                            @if($reviewsModule['edit_access'] == 1 || $reviewsModule['full_access'] == 1)
                                <a href="{{ route('reviews.create') }}" class="btn btn-primary float-end">
                                    Add Review
                                </a>
                            @endif
                        </div>

                        <div class="card-body">

                            {{-- Success Message --}}
                            @if(Session::has('success_message'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong>Success:</strong> {{ Session::get('success_message') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            {{-- Reviews Table --}}
                            <table id="reviews" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>User</th>
                                        <th>Rating</th>
                                        <th>Review</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($reviews as $review)
                                        <tr>
                                            <td>{{ $review->product->product_name ?? 'N/A' }}</td>
                                            <td>{{ $review->user->name ?? 'Guest' }}</td>
                                            <td>{{ $review->rating }}</td>
                                            <td>{{ $review->review }}</td>

                                            {{-- Status --}}
                                            <td>
                                                @if($reviewsModule['edit_access'] == 1 || $reviewsModule['full_access'] == 1)
                                                    <a href="javascript:void(0)"
                                                       class="updateReviewStatus"
                                                       data-review-id="{{ $review->id }}">
                                                        <i class="fas fa-toggle-{{ $review->status ? 'on' : 'off' }}"
                                                           style="color: {{ $review->status ? '#3f6ed3' : 'grey' }}"
                                                           data-status="{{ $review->status ? 'Active' : 'Inactive' }}">
                                                        </i>
                                                    </a>
                                                @else
                                                    {{ $review->status ? 'Active' : 'Inactive' }}
                                                @endif
                                            </td>

                                            {{-- Actions --}}
                                            <td>
                                                {{-- Edit --}}
                                                @if($reviewsModule['edit_access'] == 1 || $reviewsModule['full_access'] == 1)
                                                    <a href="{{ route('reviews.edit', $review->id) }}">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    &nbsp;
                                                @endif

                                                {{-- Delete --}}
                                                @if($reviewsModule['full_access'] == 1)
                                                    <form action="{{ route('reviews.destroy', $review->id) }}"
                                                          method="POST"
                                                          style="display:inline-block;">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="submit"
                                                                class="confirmDelete"
                                                                data-module="review"
                                                                data-id="{{ $review->id }}"
                                                                style="border:none; background:none; color:#3f6ed3;">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                            </table>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

</main>
@endsection
