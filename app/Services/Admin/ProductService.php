<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\AdminsRole;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;


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

            if(file_exists($sourcePath)) {
                @copy($sourcePath, $destinationPath);
                @unlink($sourcePath);
            }

            $product->product_video = $data['product_video_hidden'];
        }

        $product->main_image = $request->main_image ?? $product->main_image;
        $product->product_video = $request->product_video ?? $product->product_video;
        $product->save();
        return $message;
    }

    /**
     * Handle Product Image Upload
     */
    public function handleImageUpload($file)
    {
        $imageName = time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('front/images/products'), $imageName);
        return $imageName;
    }

    /**
     * Handle Product Video Upload
     */
    public function handleVideoUpload($file)
    {
        $videoName = time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('front/videos/products'), $videoName);
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

    /**
     * Delete Product Video
     */
    public function deleteProductVideo($id) {
        // Get Product Video
        $productVideo = Product::select('product_video')->where('id', $id)->first();

        // Get Product Video Path
        $product_video_path = 'front/videos/products/';

        // Delete Product Video from folderif exists
        if (file_exists($product_video_path . $productVideo->product_video)) {
            unlink($product_video_path . $productVideo->product_video);
        }
        
        // Delete Product Video from product table
        Product::where('id', $id)->update(['product_video' => '']);

        $message = "Product video deleted successfully";
        return $message;
    }
    
}