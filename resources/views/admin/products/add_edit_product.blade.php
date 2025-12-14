@extends('admin.layout.layout')
@section('content')
<!--begin::App Main-->
<main class="app-main">
    <!--begin::App Content Header-->
    <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Catalogue Management</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                    </ol>
                </div>
            </div>
            <!--end::Row-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::App Content Header-->
    <!--begin::App Content-->
    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row g-4">
                <!--begin::Col-->
                <div class="col-md-6">
                    <!--begin::Quick Example-->
                    <div class="card card-primary card-outline mb-4">
                        <!--begin::Header-->
                        <div class="card-header">
                            <div class="card-title">{{ $title }}</div>
                        </div>
                        <!--end::Header-->
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
                        <!--begin::Form-->
                        <form name="productForm" id="productForm" action="{{ isset($product) ? route('products.update', $product->id) : route('products.store') }}" method="post">
                            @csrf
                            @if(isset($product)) @method('PUT') @endif
                            <div class="mb-3">
                                <label for="category_id">Select Category*</label>
                                <select name="category_id" class="form-control">
                                    <option value="">Select</option>
                                    @foreach($getCategories as $cat)
                                    <option value="{{ $cat['id'] }}" @if(old('category_id', $product->category_id ?? '') == $cat['id']) selected @endif>{{ $cat['name'] }}</option>
                                    @if(!empty($cat['subcategories']))
                                    @foreach($cat['subcategories'] as $subcat)
                                    <option value="{{ $subcat['id'] }}" @if(old('category_id', $product->category_id ?? '') == $subcat['id']) selected @endif>&nbsp;&nbsp;&nbsp;&nbsp;&raquo;&raquo; {{ $subcat['name'] }}</option>
                                    @if(!empty($subcat['subcategories']))
                                    @foreach($subcat['subcategories'] as $subsubcat)
                                    <option value="{{ $subsubcat['id'] }}" @if(old('category_id', $product->category_id ?? '') == $subsubcat['id']) selected @endif>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&raquo;&raquo; {{ $subsubcat['name'] }}</option>
                                    @endforeach
                                    @endif
                                    @endforeach
                                    @endif
                                    @endforeach
                                </select>

                            <div class="mb-3">
                                <label class="form-label" for="product_name">Product Name*</label>
                                <input type="text" class="form-control" id="product_name" name="product_name" placeholder="Enter Product Name" value="{{ old('product_name', $product->product_name ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="product_code">Product Code*</label>
                                <input type="text" class="form-control" id="product_code" name="product_code" placeholder="Enter Product Code" value="{{ old('product_code', $product->product_code ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="product_color">Product Color*</label>
                                <input type="text" class="form-control" id="product_color" name="product_color" placeholder="Enter Product Color" value="{{ old('product_color', $product->product_color ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="family_color">Family Color*</label>
                                <input type="text" class="form-control" id="family_color" name="family_color" placeholder="Enter Family Color" value="{{ old('family_color', $product->family_color ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="group_code">Group Code*</label>
                                <input type="text" class="form-control" id="group_code" name="group_code" placeholder="Enter Group Code" value="{{ old('group_code', $product->group_code ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="product_price">Product Price*</label>
                                <input type="text" class="form-control" id="product_price" name="product_price" placeholder="Enter Product Price" value="{{ old('product_price', $product->product_price ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="product_discount">Product Discount(%)</label>
                                <input type="number" step="0.01" class="form-control" name="product_discount" value="{{ old('product_discount', $product->product_discount ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="product_gst">Product GST(%)</label>
                                <input type="number" step="0.01" class="form-control" name="product_gst" value="{{ old('product_gst', $product->product_gst ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="product_dimensions">Product Dimension (L x W x H)</label>
                                <div class="d-flex gap-2">
                                    <input type="number" step="0.01" class="form-control"
                                           name="product_dimensions[length]" placeholder="Length (L)"
                                           value="{{ old('product_dimensions[length]', $product->product_dimensions['length'] ?? '') }}">
                            
                                    <input type="number" step="0.01" class="form-control"
                                           name="product_dimensions[width]" placeholder="Width (W)"
                                           value="{{ old('product_dimensions[width]', $product->product_dimensions['width'] ?? '') }}">
                            
                                    <input type="number" step="0.01" class="form-control"
                                           name="product_dimensions[height]" placeholder="Height (H)"
                                           value="{{ old('product_dimensions[height]', $product->product_dimensions['height'] ?? '') }}">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="main_image_dropzone">Product Main Image(Max 500KB)</label>
                                <div class="dropzone" id="mainImageDropzone"></div>

                                <input type="hidden" name="main_image_hidden" id="main_image_hidden">

                                @if(!empty($product['main_image']))
                                <a target="_blank" href="{{ url('front/images/products/'.$product['main_image']) }}">
                                <img style="width: 50px; margin: 10px;" src="{{ asset('front/images/products/'.$product['main_image']) }}"></a>
                                <a style='color:#3f6ed3'; class="confirmDelete" title="Delete Product Image" href="javascript:void(0)" data-module="product-main-image" data-id="{{ $product['id'] }}"><i class="fa fa-trash"></i></a>
                                @endif
                                
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="main_video_dropzone">Product Video(Max 2MB)</label>
                                <div class="dropzone" id="productVideoDropzone"></div>

                                <input type="hidden" name="product_video" id="product_video_hidden">

                                @if(!empty($product['product_video']))
                                <a target="_blank" href="{{ url('front/videos/products/'.$product['product_video']) }}">View Video</a>
                                <a href="javascript:void(0)" class="confirmDelete" data-module="product-video" data-id="{{ $product['id'] }}">Delete Video</a>
                                @endif

                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="material">Material</label>
                                <textarea name="material" class="form-control" id="material" placeholder="Enter Material">{{ old('material', $product->material ?? '') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="description">Product Description</label>
                                <textarea name="description" class="form-control" rows="3" id="description" placeholder="Enter Product Description">{{ old('description', $product->description ?? '') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="search_keywords">Search Keywords</label>
                                <textarea name="search_keywords" class="form-control" placeholder="Enter Search Keywords">{{ old('search_keywords', $product->search_keywords ?? '') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="meta_title">Meta Title</label>
                                <input type="text" class="form-control" name="meta_title" value="{{ old('meta_title', $product->meta_title ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="meta_description">Meta Description</label>
                                <input type="text" class="form-control" name="meta_description" value="{{ old('meta_description', $product->meta_description ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="meta_keywords">Meta Keywords</label>
                                <input type="text" class="form-control" name="meta_keywords" value="{{ old('meta_keywords', $product->meta_keywords ?? '') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="is_featured">Is Featured</label>
                                <select name="is_featured" class="form-control">
                                    <option value="No" {{(old('is_featured', $product->is_featured ?? '') == 'No') ? 'selected' : ''}}>No</option>
                                    <option value="Yes" {{(old('is_featured', $product->is_featured ?? '') == 'Yes') ? 'selected' : ''}}>Yes</option>
                                </select>
                            </div>

                            </div>

                            <div class = "card-footer">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                            </div>
                        </form>

                        <!--end::Form-->
                    </div>
                    <!--end::Quick Example-->
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::App Content-->
</main>
<!--end::App Main-->
@endsection