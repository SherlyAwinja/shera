<?php
namespace App\Services\Admin;

use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Models\Brand;
use App\Models\AdminsRole;
use Illuminate\Support\Facades\Auth;

class BrandService
{
    public function brands()
    {
        $brands = Brand::get();
        $admin = Auth::guard('admin')->user();
        $status = "success";
        $message = "";
        $brandsModule = [];
        // Admin has full access
        if($admin->role == "admin"){
            $brandsModule = [
                'view_access' => 1,
                'edit_access' => 1,
                'full_access' => 1,
            ];
        } else {
            $brandsModuleCount = AdminsRole::where([
                'subadmin_id' => $admin->id,
                'module' => 'brands',
            ])->count();
            if($brandsModuleCount == 0){
                $status = "error";
                $message = "You do not have access to this feature";
            } else {
                $brandsModule = AdminsRole::where([
                    'subadmin_id' => $admin->id,
                    'module' => 'brands',
                ])->first()->toArray();
            }
        }
        return [
            "brands" => $brands, 
            "brandsModule" => $brandsModule, 
            "status" => $status, 
            "message" => $message, 
        ];
    }

    public function addEditBrand($request)
    {
        $data = $request->all();

        if(isset($data['id']) && $data['id'] != ""){
            // Edit Brand
            $brand = Brand::find($data['id']);
            $message = "Brand updated successfully";
        } else {
            // Add Brand
            $brand = new Brand;
            $message = "Brand added successfully";
        }

        // Upload Brand Image
        if($request->hasFile('image')){
            $image_temp = $request->file('image');
            if($image_temp->isValid()){
                $manager = new ImageManager(new Driver());
                $image = $manager->read($image_temp);
                $extension = $image_temp->getClientOriginalExtension();
                $imageName = rand(111, 99999).'.'.$extension;
                $image_path = 'front/images/brands/'.$imageName;
                $image->save($image_path);
                $brand->image = $imageName;
            }
        }

        // Upload Brand logo
        if($request->hasFile('logo')) {
            $sizechart_temp = $request->file('logo');
            if($sizechart_temp->isValid()){
                $manager = new ImageManager(new Driver());
                $image = $manager->read($sizechart_temp);
                $sizechart_extension = $sizechart_temp->getClientOriginalExtension();
                $sizechartimageName = rand(111, 99999).'.'.$sizechart_extension;
                $sizechart_image_path = 'front/images/logos/'.$sizechartimageName;
                $image->save($sizechart_image_path);
                $brand->logo = $sizechartimageName;
            }
        }

        // Format name and URL
        $data['name'] = str_replace("-", "", ucwords(strtolower($data['name'])));
        $data['url'] = str_replace(" ", "-", strtolower($data['url']));

        $brand->name = $data['name'];

        // Discount default
        if (empty($data['brand_discount'])) {
            $data['brand_discount'] = 0;
        }

        $brand->discount = $data['brand_discount'];
        $brand->description = $data['description'];
        $brand->url = $data['url'];
        $brand->meta_title = $data['meta_title'];
        $brand->meta_description = $data['meta_description'];
        $brand->meta_keywords = $data['meta_keywords'];

        // Menu Status
        if (!empty($data['menu_status'])) {
            $brand->menu_status = 1;
        } else {
            $brand->menu_status = 0;
        }

        // Status default
        $brand->status = 1;

        $brand->save();

        return $message;
    }

    public function updateBrandStatus($data)
    {
        $status = ($data['status'] == "Active") ? 0 : 1;
        Brand::where('id', $data['brand_id'])->update(['status' => $status]);
        return $status;
    }

    public function deleteBrand($id)
    {
        Brand::where('id', $id)->delete();
        $message = "Brand deleted successfully";
        return ['message' => $message];
    }
}