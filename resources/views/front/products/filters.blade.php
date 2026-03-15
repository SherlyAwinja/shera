<?php
use App\Models\ProductsFilter;
?>

<div class="col-lg-3 col-md-12">

    <!-- Categories Filter Start -->
    @php
        // Get current main category (already passed in getCategoryListingData)
        $mainCategory = $categoryDetails ?? null;
        $selectedCategories = [];

        if (request()->has('category')) {
            $selectedCategories = explode('~', request()->get('category'));
        }
    @endphp

    @if(!empty($mainCategory) && $mainCategory->subcategories->count() > 0)
        <div class="border-bottom mb-4 pb-4">
            <h5 class="font-weight-semi-bold mb-4">Filter by Categories</h5>
            <div>
                @foreach($mainCategory->subcategories as $subcategory)
                    <div class="custom-control custom-checkbox d-flex align-items-center justify-content-between mb-2">
                        <input type="checkbox"
                            name="category[]"
                            id="category{{ $subcategory->id }}"
                            value="{{ $subcategory->id }}"
                            class="custom-control-input filterAjax"
                            {{ in_array($subcategory->id, $selectedCategories) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="category{{ $subcategory->id }}">
                            {{ $subcategory->name }}
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    <!-- Categories Filter End -->

    <!-- Availability start -->
    <div class="border-bottom mb-4 pb-4">
        <h5 class="font-weight-semi-bold mb-4">Availability</h5>
        @php
            $getAvailability = ProductsFilter::getAvailability($catIds);
            $selectedAvailability = request()->has('availability') ? preg_split('/[~,]/', request()->get('availability')) : [];
        @endphp
        <div>
            @foreach($getAvailability as $key => $availability)
                <div class="custom-control custom-checkbox d-flex align-items-center justify-content-between mb-2">
                    <input type="checkbox" name = "availability" id = "availability{{$key}}" value = "{{$availability}}" class = "custom-control-input filterAjax" {{ in_array($availability, $selectedAvailability) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="availability{{$key}}">{{ ucfirst(str_replace('_', ' ', $availability)) }}</label>
                </div>
            @endforeach
        </div>
    </div>
    <!-- Availability end -->

    <!-- Gender start -->
    <div class="border-bottom mb-4 pb-4">
        <h5 class="font-weight-semi-bold mb-4">Gender</h5>
        @php
            $selectedGenders = request()->has('gender') ? preg_split('/[~,]/', request()->get('gender')) : [];
            $selectedGenders = array_map('strtolower', array_filter($selectedGenders));
            $genderOptions = [
                'men' => 'Men',
                'women' => 'Women',
                'unisex' => 'Unisex',
                'kids' => 'Kids',
            ];
        @endphp
        <div>
            @foreach($genderOptions as $genderValue => $genderLabel)
                <div class="custom-control custom-checkbox d-flex align-items-center justify-content-between mb-2">
                    <input type="checkbox" name = "gender" id = "gender{{$genderValue}}" value = "{{$genderValue}}" class = "custom-control-input filterAjax" {{ in_array($genderValue, $selectedGenders) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="gender{{$genderValue}}">{{ $genderLabel }}</label>
                </div>
            @endforeach
        </div>
    </div>
    <!-- Gender end -->

    <!-- Price Start -->
    <div class = "border-bottom mb-4 pb-4">
        <h5 class = font-weight-semi-bold mb-4>Filter by Price</h5>
        @php
            $priceRanges = [
                '0-2500' => 'Below 2500',
                '2500-5000' => '2500 to 5000',
                '5000-10000' => '5000 to 10000',
                '10000-25000' => '10000 to 25000',
                '25000-50000' => '25000 to 50000',
                '50000-999999' => 'Above 50000',
            ];
            $selectedPrices = [];
            if (request()->has('price')) {
                $selectedPrices = explode('~', request()->get('price'));
            }
        @endphp
        <div>
            @foreach($priceRanges as $key => $price)
                <div class="custom-control custom-checkbox d-flex align-items-center justify-content-between mb-2">
                    <input type="checkbox" name = "price" id = "price{{$key}}" value = "{{$key}}" class = "custom-control-input filterAjax" {{ in_array($key, $selectedPrices, true) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="price{{$key}}">{{ $price }}</label>
                </div>
            @endforeach
        </div>
    </div>
    <!-- Price End -->

    <!-- Color Start -->
    <div class="border-bottom mb-4 pb-4">
        <h5 class="front-weight-semi-bold mb-4">Filter by Color</h5>
        @php
            $getColors = ProductsFilter::getColors($catIds);
            $selectedColors = request()->has('color') ? preg_split('/[~,]/', request()->get('color')) : [];
        @endphp
        <div>
            @foreach($getColors as $key => $color)
                <div class="custom-control custom-checkbox d-flex align-items-center justify-content-between mb-2">
                    <input type="checkbox" name = "color" id = "color{{$key}}" value = "{{$color}}" class = "custom-control-input filterAjax" {{ in_array($color, $selectedColors) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="color{{$key}}">{{ ucfirst($color) }}</label>
                </div>
            @endforeach
        </div>
    </div>
    <!-- Color End -->

    <!-- Occasion start -->
    <div class="border-bottom mb-4 pb-4">
        <h5 class="font-weight-semi-bold mb-4">Filter by Occasion</h5>
        @php
            $selectedOccasions = request()->has('occasion') ? preg_split('/[~,]/', request()->get('occasion')) : [];
            $selectedOccasions = array_map('strtolower', array_filter($selectedOccasions));
            $occasionOptions = [
                'work' => 'Work',
                'cassual' => 'Cassual',
                'travel' => 'Travel',
                'gym' => 'Gym',
            ];
        @endphp
        <div>
            @foreach($occasionOptions as $occasionValue => $occasionLabel)
                <div class="custom-control custom-checkbox d-flex align-items-center justify-content-between mb-2">
                    <input type="checkbox" name = "occasion" id = "occasion{{$occasionValue}}" value = "{{$occasionValue}}" class = "custom-control-input filterAjax" {{ in_array($occasionValue, $selectedOccasions) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="occasion{{$occasionValue}}">{{ $occasionLabel }}</label>
                </div>
            @endforeach
        </div>
    </div>
    <!-- Occasion end -->

    <!-- Size Start -->
    <div class = "border-bottom mb-4 pb-4">
        <h5 class="font-weight-semi-bold mb-4">Filter by Size</h5>
        @php
            $getSizes = ProductsFilter::getSizes($catIds);
            $selectedSizes = request()->has('size') ? preg_split('/[~,]/', request()->get('size')) : [];
        @endphp
        <div>
            @foreach($getSizes as $key => $size)
                <div class="custom-control custom-checkbox d-flex align-items-center justify-content-between mb-2">
                    <input type="checkbox" name = "size" id = "size{{$key}}" value = "{{$size}}" class = "custom-control-input filterAjax" {{ in_array($size, $selectedSizes) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="size{{$key}}">{{ strtoupper($size) }}</label>
                </div>
            @endforeach
        </div>
    </div>
    <!-- Size End -->

    <!-- Brand Start -->
    <div class = "border-bottom mb-4 pb-4">
        <h5 class="font-weight-semi-bold mb-4">Brands</h5>
        @php
            $getBrands = ProductsFilter::getBrands($catIds);
            $selectedBrands = [];
            if (request()->has('brand') && !empty(request()->get('brand'))) {
                $selectedBrands = preg_split('/[~,]/', request()->get('brand'));
            }
        @endphp
        <div>
            @foreach($getBrands as $key => $brand)
                <div class="custom-control custom-checkbox d-flex align-items-center justify-content-between mb-2">
                    <input type="checkbox" name = "brand" id = "brand{{$key}}" value = "{{$brand['name']}}" class = "custom-control-input filterAjax" {{ in_array($brand['name'], $selectedBrands) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="brand{{$key}}">{{ ucfirst($brand['name']) }}</label>
                </div>
            @endforeach
        </div>
    </div>
    <!-- Brand End -->

    <!-- Dynamic Filter Start -->

    @foreach($filters as $filter)

    @php
        // Get values already sorted by 'sort' from eager loading
        $filterValues = $filter->values
            ->where('status', 1)
            ->filter(function($value) use ($catIds) {
                // Keep only those linked to products in these categories
                return $value->products->whereIn('category_id', $catIds)->isNotEmpty();
            });

        if ($filterValues->isEmpty()) {
            continue;
        }

        $selectedRawValues = request()->get($filter->filter_name, []);
        if (is_array($selectedRawValues)) {
            $selectedValues = preg_split('/[~,]/', implode('~', $selectedRawValues));
        } else {
            $selectedValues = preg_split('/[~,]/', (string) $selectedRawValues);
        }
        $selectedValues = array_values(array_filter(array_map('trim', $selectedValues)));
    @endphp


    <div class="border-bottom mb-4 pb-4">

        <h5 class="font-weight-semi-bold mb-4">
            Filter by {{ ucwords($filter->filter_name) }}
        </h5>

        <div>

            @foreach($filterValues as $key => $valueObj)

            <div class="custom-control custom-checkbox d-flex align-items-center justify-content-between mb-2">

                <input
                    type="checkbox"
                    name="{{ $filter->filter_name }}"
                    id="{{ $filter->filter_name }}{{ $key }}"
                    value="{{ $valueObj->value }}"
                    class="custom-control-input filterAjax"
                    {{ in_array($valueObj->value, $selectedValues) ? 'checked' : '' }}
                >

                <label
                    class="custom-control-label"
                    for="{{ $filter->filter_name }}{{ $key }}"
                >
                    {{ ucfirst($valueObj->value) }}
                </label>

            </div>

            @endforeach

        </div>

    </div>

    @endforeach

    <!-- Dynamic Filter End -->
</div>
