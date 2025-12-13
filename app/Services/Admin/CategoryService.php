<?php

namespace App\Services\Admin;
use App\Models\Category;
use App\Models\AdminsRole;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;


class CategoryService {

    /**
     * Check if the current admin/subadmin has access to categories module
     * Returns array with 'has_access' boolean and 'categoriesModule' array
     */
    public function checkCategoryAccess()
    {
        $admin = Auth::guard('admin')->user();
        
        // Admin has full access
        if($admin->role == "admin") {
            return [
                'has_access' => true,
                'categoriesModule' => [
                    'view_access' => 1,
                    'edit_access' => 1,
                    'full_access' => 1,
                ]
            ];
        }
        
        // Check if subadmin has access to categories module
        $categoriesModule = AdminsRole::where([
            'subadmin_id' => $admin->id,
            'module' => 'categories'
        ])->first();
        
        if(!$categoriesModule) {
            return [
                'has_access' => false,
                'categoriesModule' => null
            ];
        }
        
        return [
            'has_access' => true,
            'categoriesModule' => $categoriesModule->toArray()
        ];
    }

    public function categories()
    {
        $categories = Category::with('parentCategory')->get();
        $accessCheck = $this->checkCategoryAccess();
        
        if(!$accessCheck['has_access']) {
            return [
                "categories" => [],
                "categoriesModule" => [],
                "status" => "error",
                "message" => "You are not authorized to access this module",
            ];
        }
        
        return [
            "categories" => $categories,
            "categoriesModule" => $accessCheck['categoriesModule'],
            "status" => "success",
            "message" => "",
        ];
    }

    public function addEditCategory($request)
    {
        $data = $request->all();

        if (isset($data['id']) && $data['id'] !=""){

            // Edit Category
            $category = Category::find($data['id']);
            $message = "Category Updated Successfully!";
        } else {

            // Add Category
            $category = new Category;
            $message = "Categpry added Successfully!";
        }

        // Save Parent_id (null for Main Category)
        $category->parent_id = !empty($data['parent_id']) ? $data['parent_id'] : null;

        // Upload Category Image
        if($request->hasFile('category_image')) {
            $image_tmp = $request->file('category_image');
            if($image_tmp->isValid()){
                $manager = new ImageManager(new Driver());
                $image = $manager->read($image_tmp);
                $extension = $image_tmp->getClientOriginalExtension();
                $imageName = rand(111,99999).'.'.$extension;
                $image_path = 'front/images/categories/'.$imageName;
                $image->save($image_path);
                $category->image = $imageName;
            }
                
        }

        // Upload Size Chart
        if($request->hasFile('size_chart')) {
            $sizechart_tmp = $request->file('size_chart');
            if($sizechart_tmp->isValid()){
                $manager = new ImageManager(new Driver());
                $image = $manager->read($sizechart_tmp);
                $sizechart_extension = $sizechart_tmp->getClientOriginalExtension();
                $sizechartimageName = rand(111,99999).'.'.$sizechart_extension;
                $sizechart_image_path = 'front/images/sizecharts/'.$sizechartimageName;
                $image->save($sizechart_image_path);
                $category->size_chart = $sizechartimageName;
            }
        }

        // Format name and URL
        $data['category_name'] = str_replace("-", "", ucwords(strtolower($data['category_name'])));
        $data['url'] = str_replace("", "-", strtolower($data['url']));

        $category->name = $data['category_name'];

        // Discount default
        if(empty($data['category_discount'])){
            $data['category_discount'] = 0;
        }

        $category->discount = $data['category_discount'];
        $category->description = $data['description'];
        $category->url = $data['url'];
        $category->meta_title = $data['meta_title'];
        $category->meta_description = $data['meta_description'];
        $category->meta_keywords = $data['meta_keywords'];

        // Menu Status default
        if(!empty($data['menu_status'])){
            $category->menu_status = 1;
        } else {
            $category->menu_status = 0;
        }

        // Status default
        $category->status = 1;

        $category->save();

        return $message;
    }

    public function updateCategoryStatus($data)
    {
        $status = $data['status'] == "Active" ? 0 : 1;
        Category::where('id', $data['category_id'])->update(['status' => $status]);
        return $status;
    }

    public function deleteCategory($id)
    {
        Category::where('id', $id)->delete();
        $message = "Category deleted successfully";
        return ['message' => $message];
    }

    public function deleteCategoryImage($category_id)
    {
        $categoryImage = Category::where('id', $category_id)->value('image');
        if($categoryImage){
            $category_image_path = 'front/images/categories/'.$categoryImage;
            if(file_exists(public_path($category_image_path))){
                unlink(public_path($category_image_path));
            }
            Category::where('id', $category_id)->update(['image' => null]);
            return ['status' => true, 'message' => 'Category image deleted successfully'];
        }
        return ['status' => false, 'message' => 'Category image not found'];
    }

    public function deleteSizeChartImage($category_id)
    {
        $sizeChartImage = Category::where('id', $category_id)->value('size_chart');
        if($sizeChartImage){
            $sizechart_image_path = 'front/images/sizecharts/'.$sizeChartImage;
            if(file_exists(public_path($sizechart_image_path))){
                unlink(public_path($sizechart_image_path));
            }
            Category::where('id', $category_id)->update(['size_chart' => null]);
            return ['status' => true, 'message' => 'Size chart image deleted successfully'];
        }
        return ['status' => false, 'message' => 'Size chart image not found'];
    }
}