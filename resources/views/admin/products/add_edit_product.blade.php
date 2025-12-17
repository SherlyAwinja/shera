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
                <div class="col-md-8">
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

                            <?php $familyColors = \App\Models\Color::colors(); ?>
                            <div class="mb-3">
                                <label class="form-label" for="family_color">Family Color*</label>
                                <select name="family_color" class="form-control">
                                    <option value="">Please Select</option>
                                    @foreach($familyColors as $color)
                                    <option value="{{  $color->name }}" @if(isset($product['family_color']) && $product['family_color'] == $color->name) selected @endif>{{ $color->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="group_code">Group Code</label>
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
                                <label class="form-label mb-1">Product Attributes</label>
                                {{-- header row --}}
                                <div class="d-none d-md-flex fw-semibold bg-light border rounded px-2 py-1 mb-2">
                                    <div class="flex-fill col-2">Size</div>
                                    <div class="flex-fill col-2 ms-4">SKU</div>
                                    <div class="flex-fill col-2 ms-4">Price</div>
                                    <div class="flex-fill col-2 ms-4">Stock</div>
                                    <div class="flex-fill col-2 ms-4">Sort</div>
                                    <div style="width: 60px"></div>
                                </div>
                                
                                {{-- dynamic rows --}}
                                <div class="field_wrapper">
                                    {{-- first row --}}
                                    <div class="d-flex align-items-center gap-2 mb-2 attribute-row">

                                        <input name="size[]" class="form-control flex-fill col-2" placeholder="Enter Size">
                                        <input name="sku[]" class="form-control flex-fill col-2" placeholder="Enter SKU">
                                        <input name="price[]" class="form-control flex-fill col-2" placeholder="Enter Price">
                                        <input name="stock[]" class="form-control flex-fill col-2" placeholder="Enter Stock">
                                        <input name="sort[]" class="form-control flex-fill col-2" placeholder="Enter Sort">
                                        <a href="javascript:void(0)" class="btn btn-sm btn-success add_button" title="Add row">
                                            <i class="fas fa-plus"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            @if(isset($product['attributes']) && count($product['attributes']) > 0)
                            <div class="mb-3">
                                <label class="form-label mb-1">Existing Product Attributes</label>

                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0">
                                        <thead class="table-light text-center">
                                            <tr>
                                                <th style="width: 15%">Size</th>
                                                <th style="width: 20%">SKU</th>
                                                <th style="width: 15%">Price</th>
                                                <th style="width: 15%">Stock</th>
                                                <th style="width: 15%">Sort</th>
                                                <th style="width: 15%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($product['attributes'] as $attribute)
                                            <input type="hidden" name="attrId[]" value="{{ $attribute['id'] }}">
                                            <tr class="text-center">
                                                <td>{{ $attribute['size'] }}</td>
                                                <td>{{ $attribute['sku'] }}</td>
                                                <td>
                                                    <input type="number" name="update_price[]" value="{{ $attribute['price'] }}" class="form-control text-center" required>
                                                </td>
                                                <td>
                                                    <input type="number" name="update_stock[]" value="{{ $attribute['stock'] }}" class="form-control text-center" required>
                                                </td>
                                                <td>
                                                    <input type="number" name="update_sort[]" value="{{ $attribute['sort'] }}" class="form-control text-center" required>
                                                </td>
                                                <td>
                                                    @if($attribute['status'] == 1)
                                                    <a class="updateAttributeStatus" data-attribute_id="{{ $attribute->id }}" style='color:#3f6ed3' href="javascript:void(0)">
                                                        <i class="fas fa-toggle-on" data-status="Active"></i>
                                                    </a>&nbsp;&nbsp;
                                                    @else
                                                    <a class="updateAttributeStatus" data-attribute_id="{{ $attribute->id }}" style='color:#dc3545' href="javascript:void(0)">
                                                        <i class="fas fa-toggle-off" data-status="Inactive"></i>
                                                    </a>&nbsp;&nbsp;
                                                    @endif
                                                    <a title="Delete Attribute" href="javascript:void(0)" class="confirmDelete text-danger" data-module="product-attribute" data-id="{{ $attribute['id'] }}"><i class="fas fa-trash"></i></a>
                                                </td>

                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                            
                            <div class="mb-3">
                                <label class="form-label" for="main_image_dropzone">Product Main Image(Max 500KB)</label>
                                <div class="dropzone" id="mainImageDropzone"></div>

                                <input type="hidden" name="main_image_hidden" id="main_image_hidden">

                                @if(!empty($product['main_image']))
                                <div class="mt-3">
                                    <p class="mb-2"><strong>Current Main Image:</strong></p>
                                    <div class="d-inline-block position-relative">
                                        <a target="_blank" href="{{ url('front/images/products/'.$product['main_image']) }}" title="Click to view full size">
                                            <img src="{{ asset('front/images/products/'.$product['main_image']) }}" 
                                            style="width: 80px; height: 80px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 2px;">
                                        </a>
                                        <a style='color:#dc3545'; class="confirmDelete" title="Delete Product Image" href="javascript:void(0)" data-module="product-main-image" data-id="{{ $product['id'] }}"
                                           style="position: absolute; top: -5px; right: -5px; background: white; border-radius: 50%; padding: 2px 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                                @endif
                                
                            </div>
                            <div id="mainImageDropzoneError" style="color: red; display: none;"></div>

                            <div class="mb-3">
                                <label class="form-label" for="product_images_dropzone">
                                    Alternate Product Images (Multiple Uploads Allowed, Max 500KB each)
                                </label>
                                <div class="dropzone" id="productImagesDropzone"></div>
                                @if(isset($product->product_images) && $product->product_images->count() > 0)
                                <div class="mt-3">
                                    <p class="mb-2"><strong>Existing Alternate Images:</strong></p>
                                    @if($product->product_images->count() > 1)
                                    <!--Instruction Line-->
                                    <p class="drag-instruction text-muted small mb-2">
                                        <i class="fas fa-arrows-alt"></i> Drag and drop below images to reorder them
                                    </p>
                                    @endif
                                    <!-- Container for sortable images -->
                                    <div id="sortable-images" class="sortable-wrapper d-flex gap-2 flex-wrap">
                                        @foreach($product->product_images as $img)
                                        <div class="sortable-item position-relative" data-id="{{ $img->id }}" style="margin-bottom: 10px;">
                                            <a target="_blank" href="{{ url('front/images/products/'.$img->image) }}" title="Click to view full size">
                                                <img src="{{ asset('front/images/products/'.$img->image) }}" 
                                                style="width: 80px; height: 80px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 2px;">
                                            </a>
                                            <a href="javascript:void(0)" class="confirmDelete text-danger" 
                                               data-module="product-image" 
                                               data-id="{{ $img->id }}" 
                                               data-image="{{ $img->image }}"
                                               title="Delete Image"
                                               style="position: absolute; top: -5px; right: -5px; background: white; border-radius: 50%; padding: 2px 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            <!-- Hidden input to collect alternate images -->
                                <input type="hidden" name="product_images_hidden" id="product_images_hidden">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="main_video_dropzone">Product Video(Max 2MB)</label>
                                <div class="dropzone" id="productVideoDropzone"></div>

                                <input type="hidden" name="product_video_hidden" id="product_video_hidden">

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