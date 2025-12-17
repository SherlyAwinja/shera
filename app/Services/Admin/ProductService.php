<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\AdminsRole;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use App\Models\ProductsImage;
use App\Models\ProductsAttribute;


class ProductService
{
    public function products() {
        $products = Product::with('category')->get();

        // set Admin/Subadmin Permissions for products
        $productsModuleCount = AdminsRole::where(['subadmin_id' => Auth::guard('admin')->user()->id, 'module' => 'products'])->count();
        $status = "success";
        $message = "";
        $productsModule = [];
        if (Auth::guard('admin')->user()->role == 'admin') {
            $productsModule = [
                'view_access' => 1,
                'edit_access' => 1,
                'full_access' => 1,
            ];
        } elseif ($productsModuleCount == 0) {
            $status = "error";
            $message = "You do not have permission to access this module";
        } else {
            $productsModule = AdminsRole::where(['subadmin_id' => Auth::guard('admin')->user()->id, 'module' => 'products'
            ])->first()->toArray();
        }
        return [
            "products" => $products,
            "productsModule" => $productsModule,
            "status" => $status,
            "message" => $message
        ];
    }

    /**
     * Update Product Status
     */
    public function updateProductStatus($data)
    {
        $status = ($data['status'] == "Active") ? 0 : 1;
        Product::where('id', $data['product_id'])->update(['status' => $status]);
        return $status;
    }

    /**
     * Delete Product
     */
    public function deleteProduct($id)
    {
        Product::where('id', $id)->delete();
        $message = "Product deleted successfully";
        return ['message' => $message];
    }

    /**
     * Add/Edit Product
     */
    public function addEditProduct($request)
    {
        $data = $request->all();

        if (isset($data['id']) && $data['id'] != "") {
            $product = Product::find($data['id']);
            $message = "Product updated successfully";
        } else {
            $product = new Product();
            $message = "Product added successfully";
        }

        $product->admin_id = Auth::guard('admin')->user()->id;
        $product->admin_type = Auth::guard('admin')->user()->role;

        $product->category_id = $data['category_id'];
        $product->product_name = $data['product_name'];
        $product->product_code = $data['product_code'];
        $product->product_color = $data['product_color'];
        $product->family_color = $data['family_color'];
        $product->group_code = $data['group_code'];
        $product->product_price = $data['product_price'];
        $product->product_discount = $data['product_discount'] ?? 0;
        $product->product_gst = $data['product_gst'] ?? 0;
        $product->dimensions = !empty($data['product_dimensions']) ? $data['product_dimensions'] : null;
        $product->is_featured = $data['is_featured'] ?? 'No';
        

        // Calculate discount & final price
        if(!empty($data['product_discount']) && $data['product_discount'] > 0) {
            $product->discount_applied_on = 'product';
            $product->product_discount_amount = $data['product_price'] * $data['product_discount'] / 100;
        } else {
            $getCategoryDiscount = Category::select('discount')->where('id', $data['category_id'])->first();
            if ($getCategoryDiscount && $getCategoryDiscount->discount > 0) {
                $product->discount_applied_on = 'category';
                $product->product_discount = $getCategoryDiscount->discount;
                $product->product_discount_amount = ($data['product_price'] * $getCategoryDiscount->discount) / 100;
            } else {
                $product->discount_applied_on = '';
                $product->product_discount_amount = 0;
            }
        }

        $product->final_price = $data['product_price'] - $product->product_discount_amount;

        // Optional Fields
        $product->material = $data['material'] ?? '';
        $product->description = $data['description'] ?? '';
        $product->search_keywords = $data['search_keywords'] ?? '';
        $product->meta_title = $data['meta_title'] ?? '';
        $product->meta_description = $data['meta_description'] ?? '';
        $product->meta_keywords = $data['meta_keywords'] ?? '';

        $product->status = 1;

        if(!empty($data['main_image_hidden'])) {
            $sourcePath = public_path('temp/'.$data['main_image_hidden']);
            $destinationPath = public_path('front/images/products/'.$data['main_image_hidden']);

            // Create destination directory if it doesn't exist
            $imageDir = public_path('front/images/products');
            if (!file_exists($imageDir)) {
                mkdir($imageDir, 0755, true);
            }

            if(file_exists($sourcePath)) {
                @copy($sourcePath, $destinationPath);
                @unlink($sourcePath);
            }

            $product->main_image = $data['main_image_hidden'];
        }

        // Upload Product Video
        if(!empty($data['product_video_hidden'])) {
            $sourcePath = public_path('temp/'.$data['product_video_hidden']);
            $destinationPath = public_path('front/videos/products/'.$data['product_video_hidden']);

            // Create destination directory if it doesn't exist
            $videoDir = public_path('front/videos/products');
            if (!file_exists($videoDir)) {
                mkdir($videoDir, 0755, true);
            }

            if(file_exists($sourcePath)) {
                @copy($sourcePath, $destinationPath);
                @unlink($sourcePath);
            }

            $product->product_video = $data['product_video_hidden'];
        }

        $product->save();

        // Upload Alternate Product Images
        // NOTE: Frontend stores uploaded filenames in hidden field: product_images_hidden
        if(!empty($data['product_images_hidden'])) {
            // Ensure we have an array
            $imageFiles = is_array($data['product_images_hidden']) 
                ? $data['product_images_hidden'] 
                : explode(',', $data['product_images_hidden']);

            // Remove any empty values
            $imageFiles = array_filter($imageFiles);

            foreach ($imageFiles as $index => $filename) {
                $sourcePath = public_path('temp/'.$filename);
                $destinationPath = public_path('front/images/products/'.$filename);

                if (file_exists($sourcePath)) {
                    @copy($sourcePath, $destinationPath);
                    @unlink($sourcePath);
                }

                ProductsImage::create([
                    'product_id' => $product->id,
                    'image' => $filename,
                    'sort' => $index,
                    'status' => 1,
                ]);
            }
        }

        // Add Product Attributes
        $total_stock = 0;
        foreach($data['sku'] as $key => $value) {
            if(!empty($value)&&!empty($data['size'][$key])&&!empty($data['price'][$key])) {
                // SKU already exists check (no need to join with products)
                $attrCountSKU = ProductsAttribute::where('sku', $value)->count();
                if($attrCountSKU > 0) {
                    $message = "SKU already exists. Please add another SKU.";
                    return redirect()->back()->with('success_message', $message);
                }
                // Size already exists check, scoped to this product
                $attrCountSize = ProductsAttribute::where([
                    'product_id' => $product->id,
                    'size'       => $data['size'][$key],
                ])->count();
                if($attrCountSize > 0) {
                    $message = "Size already exists. Please add another size.";
                    return redirect()->back()->with('success_message', $message);
                }
                if(empty($data['stock'][$key])) {
                    $data['stock'][$key] = 0;
                }
                $attribute = new ProductsAttribute;
                $attribute->product_id = $product->id;
                $attribute->sku = $value;
                $attribute->size = $data['size'][$key];
                $attribute->price = $data['price'][$key];
                if(!empty($data['stock'][$key])) {
                    $attribute->stock = $data['stock'][$key];
                }
                $attribute->sort = $data['sort'][$key];
                $attribute->status = 1;
                $attribute->save();
                $total_stock = $total_stock + $data['stock'][$key];
            }
        }

        // Edit Product Attributes
        if(isset($data['id']) && $data['id'] != "" && isset($data['attrId'])) {
            foreach ($data['attrId'] as $key => $attr) {
                if(!empty($attr)) {
                    $update_attr = [
                        'price' => $data['update_price'][$key],
                        'stock' => $data['update_stock'][$key],
                        'sort' => $data['update_sort'][$key],
                    ];
                    ProductsAttribute::where(['id' => $data['attrId'][$key]])->update($update_attr);
                }
            }
        }

        // Update Product stock on Edit Product
        if(isset($data['attrId'])) {
            foreach ($data['attrId'] as $attrKeyId => $attrIdDetails) {
                $proAttrUpdate = ProductsAttribute::find($attrIdDetails);
                if ($proAttrUpdate) {
                    $proAttrUpdate->stock = $data['update_stock'][$attrKeyId];
                    $proAttrUpdate->save();
                    $total_stock = $total_stock + $data['update_stock'][$attrKeyId];
                }
            }
        }
        Product::where('id', $product->id)->update(['stock' => $total_stock]);


        return $message;
    }

    public function updateAttributeStatus($data) {
        $status = ($data['status'] == "Active") ? 0 : 1;
        ProductsAttribute::where('id', $data['attribute_id'])->update(['status' => $status]);
        return $status;
    }

    /**
     * Handle Product Image Upload
     */
    public function handleImageUpload($file)
    {
        // Always upload to temp first; final move happens on form submit
        $imageName = time() . '_' . rand(1111, 9999). '.'. $file->getClientOriginalExtension();

        $tempDir = public_path('temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $file->move($tempDir, $imageName);
        return $imageName;
    }

    /**
     * Handle Product Video Upload
     */
    public function handleVideoUpload($file)
    {
        // Always upload to temp first; final move happens on form submit
        $videoName = time() . '_' . rand(1111, 9999). '.'. $file->getClientOriginalExtension();

        $tempDir = public_path('temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $file->move($tempDir, $videoName);
        return $videoName;
    }

    /**
     * Delete Product Main Image
     */
    public function deleteProductMainImage($id)
    {
        // Get Product Main Image
        $product = Product::select('main_image')->where('id', $id)->first();

        if (!$product || !$product->main_image) {
            return "Product main image not found";
        }

        // Get Product Image Path
        $image_path = public_path('front/images/products/' . $product->main_image);

        // Delete Product Main Image if exists
        if (file_exists($image_path)) {
            unlink($image_path);
        }

        // Delete Product Main Image from product table
        Product::where('id', $id)->update(['main_image' => null]);

        $message = "Product main image deleted successfully";
        return $message;
    }

    public function deleteProductImage($id)
    {
        // Get Product Image
        $product = ProductsImage::select('image')->where('id', $id)->first();

        if (!$product || !$product->image) {
            return "Product image not found";
        }

        // Get Product Image Path
        $image_path = public_path('front/images/products/' . $product->image);

        // Delete Product Image if exists
        if (file_exists($image_path)) {
            unlink($image_path);
        }

        // Delete Product Image from products_images table
        ProductsImage::where('id', $id)->delete();

        $message = "Product image deleted successfully";
        return $message;
    }

    /**
     * Delete Product Video
     */
    public function deleteProductVideo($id) {
        // Get Product Video
        $productVideo = Product::select('product_video')->where('id', $id)->first();

        if (!$productVideo || !$productVideo->product_video) {
            return "Product video not found";
        }

        // Get Product Video Path
        $product_video_path = public_path('front/videos/products/' . $productVideo->product_video);

        // Delete Product Video from folder if exists
        if (file_exists($product_video_path)) {
            unlink($product_video_path);
        }
        
        // Delete Product Video from product table
        Product::where('id', $id)->update(['product_video' => null]);

        $message = "Product video deleted successfully";
        return $message;
    }

    public function deleteProductAttribute($id) {
        // Delete Attribute
        ProductsAttribute::where('id', $id)->delete();
        return "Product attribute deleted successfully";
    }


    public function updateImageSorting(array $sortedImages): void {
        foreach($sortedImages as $imageData) {
            if(isset($imageData['id']) && isset($imageData['sort'])) {
                ProductsImage::where('id', $imageData['id'])->update([
                    'sort' => $imageData['sort']
                ]);
            }
        }
    }

    public function deleteDropzoneImage(string $imageName): bool {
        // Check temp folder first (for files not yet submitted)
        $tempPath = public_path('temp/' . $imageName);
        if (file_exists($tempPath)) {
            return unlink($tempPath);
        }
        // Check final folder (for already saved files)
        $imagePath = public_path('front/images/products/' . $imageName);
        if (file_exists($imagePath)) {
            return unlink($imagePath);
        }
        return false;
    }

    public function deleteDropzoneVideo(string $videoName): bool {
        // Check temp folder first (for files not yet submitted)
        $tempPath = public_path('temp/' . $videoName);
        if (file_exists($tempPath)) {
            return unlink($tempPath);
        }
        // Check final folder (for already saved files)
        $videoPath = public_path('front/videos/products/' . $videoName);
        if (file_exists($videoPath)) {
            return unlink($videoPath);
        }
        return false;
    }
}