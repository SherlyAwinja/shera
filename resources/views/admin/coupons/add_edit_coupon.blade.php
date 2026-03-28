@extends('admin.layout.layout')

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">{{ $title }}</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="{{ route('coupons.index') }}">Coupons</a>
                        </li>
                        <li class="breadcrumb-item active">{{ $title }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card mb-4">
                <div class="card-body">
                    @if(Session::has('success_message'))
                        <div class="alert alert-success">
                            {{ Session::get('success_message') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ isset($coupon->id) ? route('coupons.update', $coupon->id) : route('coupons.store') }}" method="POST">
                        @csrf
                        @if(isset($coupon->id))
                            @method('PUT')
                        @endif

                        <!-- Coupon Option & Code -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Coupon Option</label><br>
                                    <div class="form-check form-check-inline">
                                        <input id="couponOptionAutomatic" class="form-check-input" type="radio" name="coupon_option" value="Automatic"
                                            {{ old('coupon_option', $coupon->coupon_option ?? 'Automatic') == 'Automatic' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="couponOptionAutomatic">Automatic</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input id="couponOptionManual" class="form-check-input" type="radio" name="coupon_option" value="Manual"
                                            {{ old('coupon_option', $coupon->coupon_option ?? 'Automatic') == 'Manual' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="couponOptionManual">Manual</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6" id="couponCodeWrapper">
                                <div class="form-group">
                                    <label for="coupon_code">Coupon Code</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="coupon_code" id="coupon_code"
                                            value="{{ old('coupon_code', $coupon->coupon_code ?? '') }}" placeholder="Enter coupon code">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" id="regenCoupon" type="button" title="Regenerate">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        Automatic will generate a code - you may customize it here.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Coupon Type & Amount Type -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Coupon Type</label><br>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="coupon_type" id="couponTypeMultiple" value="Multiple"
                                            {{ old('coupon_type', $coupon->coupon_type ?? 'Multiple') == 'Multiple' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="couponTypeMultiple">Multiple Times</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="coupon_type" id="couponTypeSingle" value="Single"
                                            {{ old('coupon_type', $coupon->coupon_type ?? 'Multiple') == 'Single' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="couponTypeSingle">Single Time</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Amount Type</label><br>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="amount_type" id="amountTypePercent" value="percentage"
                                            {{ old('amount_type', $coupon->amount_type ?? 'percentage') == 'percentage' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="amountTypePercent">Percentage (%)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="amount_type" id="amountTypeFixed" value="fixed"
                                            {{ old('amount_type', $coupon->amount_type ?? 'percentage') == 'fixed' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="amountTypeFixed">Fixed (KES)</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Amount, Min/Max Qty & Expiry -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="amount">Amount</label>
                                    <input type="number" step="0.01" min="0" id="amount" name="amount" class="form-control"
                                        value="{{ old('amount', $coupon->amount ?? '') }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="min_qty">Min Quantity</label>
                                    <select name="min_qty" id="min_qty" class="form-control">
                                        <option value="">Select Min Qty</option>
                                        @for($i = 1; $i <= 10; $i++)
                                            <option value="{{ $i }}" {{ old('min_qty', $coupon->min_qty ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="max_qty">Max Quantity</label>
                                    <select name="max_qty" id="max_qty" class="form-control">
                                        <option value="">Select Max Qty</option>
                                        @for($i = 1; $i <= 100; $i++)
                                            <option value="{{ $i }}" {{ old('max_qty', $coupon->max_qty ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                        @for($i = 150; $i <= 1000; $i+=50)
                                            <option value="{{ $i }}" {{ old('max_qty', $coupon->max_qty ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="expiry_date">Expiry Date</label>
                                    <input type="date" name="expiry_date" id="expiry_date" class="form-control"
                                        value="{{ old('expiry_date', isset($coupon->expiry_date) ? date('Y-m-d', strtotime($coupon->expiry_date)) : '') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Price Range -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="min_cart_value">Min Price Range</label>
                                    <input type="number" step="0.01" name="min_cart_value" class="form-control"
                                        value="{{ old('min_cart_value', $coupon->min_cart_value ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="max_cart_value">Max Price Range</label>
                                    <input type="number" step="0.01" name="max_cart_value" class="form-control"
                                        value="{{ old('max_cart_value', $coupon->max_cart_value ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="usage_limit_per_user">Usage Limit Per User</label>
                                    <input type="number" min="0" name="usage_limit_per_user" id="usage_limit_per_user" class="form-control"
                                        value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user ?? 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="total_usage_limit">Total Usage Limit</label>
                                    <input type="number" min="0" name="total_usage_limit" id="total_usage_limit" class="form-control"
                                        value="{{ old('total_usage_limit', $coupon->total_usage_limit ?? 0) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_discount">Maximum Discount</label>
                                    <input type="number" step="0.01" min="0" name="max_discount" id="max_discount" class="form-control"
                                        value="{{ old('max_discount', $coupon->max_discount ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Categories -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="categoriesSelect">Categories</label>
                                    <select name="categories[]" id="categoriesSelect" class="form-control select2" multiple style="color:#000;" data-actions-box="true">
                                        @foreach($categories as $category)
                                            <option value="{{ $category['id'] }}" @if(in_array($category['id'], $selCats)) selected @endif>
                                                {{ $category['name'] ?? $category['category_name'] ?? '' }}
                                            </option>
                                            @foreach($category['subcategories'] ?? [] as $subcategory)
                                                <option value="{{ $subcategory['id'] }}" @if(in_array($subcategory['id'], $selCats)) selected @endif>
                                                    &nbsp;&nbsp;-- {{ $subcategory['name'] ?? $subcategory['category_name'] ?? '' }}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-success select-all" data-target="#categoriesSelect">Select all</button>
                                        <button type="button" class="btn btn-sm btn-danger deselect-all" data-target="#categoriesSelect">Deselect all</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Brands -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="brandsSelect">Brands</label>
                                    <select name="brands[]" id="brandsSelect" class="form-control select2" multiple>
                                        @foreach($brands as $id => $name)
                                            <option value="{{ $id }}" @if(in_array($id, old('brands', $selBrands ?? []))) selected @endif>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-success select-all" data-target="#brandsSelect">Select all</button>
                                        <button type="button" class="btn btn-sm btn-danger deselect-all" data-target="#brandsSelect">Deselect all</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Users -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="usersSelect">Users</label>
                                    <select name="users[]" id="usersSelect" class="form-control select2" multiple>
                                        @foreach($users as $user)
                                            <option value="{{ $user['email'] }}" @if(in_array($user['email'], old('users', $selUsers ?? []))) selected @endif>{{ $user['email'] }}</option>
                                        @endforeach
                                    </select>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-success select-all" data-target="#usersSelect">Select all</button>
                                        <button type="button" class="btn btn-sm btn-danger deselect-all" data-target="#usersSelect">Deselect all</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Visibility -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group d-flex align-items-center">
                                    <input type="checkbox" name="visible" id="visible" class="form-check-input mr-2" value="1"
                                        {{ old('visible', $coupon->visible ?? 1) == 1 ? 'checked' : '' }}>
                                    <label for="visible" class="mb-0">&nbsp;Visible in Cart</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group d-flex align-items-center">
                                    <input type="checkbox" name="status" id="status" class="form-check-input mr-2" value="1"
                                        {{ old('status', $coupon->status ?? 1) == 1 ? 'checked' : '' }}>
                                    <label for="status" class="mb-0">&nbsp;Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button class="btn btn-primary">{{ isset($coupon->id) ? 'Update Coupon' : 'Add Coupon' }}</button>
                            <a href="{{ route('coupons.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
