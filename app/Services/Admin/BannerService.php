<?php

namespace App\Services\Admin;

use App\Models\Banner;
use App\Models\AdminsRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class BannerService
{
    // Banner Service methods will go here
    /**
     * Get all banners and module permisions
     */
    public function banners()
    {
        $admin=Auth::guard('admin')->user();
        $banners= Banner::orderBy('sort','asc')->get();
        $bannersModuleCount=AdminsRole::where([
            'subadmin_id'=>$admin->id,
            'module'=>'banners'
            ])->count();

            $bannersModule=[];

            if($admin->role == "admin"){
                $bannersModule =[
                    'view_access' =>1,
                    'edit_access' =>1,
                    'full_access' =>1
                ];
            } elseif($bannersModuleCount == 0){
                return[
                    'status' => 'error',
                    'message' =>'This feature is restricted for you!'
                ];
            }else{
                $bannersModule=AdminsRole::where([
                    'subadmin_id'=>$admin->id,
                    'module'=>'banners'
                    ])->first()->toArray();
            }

        return[
            'status' => 'success',
            'banners' => $banners,
            'bannersModule' => $bannersModule,
        ];
    }

    /**
     * Update bannerr status via AJAX
     */
    public function updateBannerStatus($data)
    {
        $status = ($data['status'] == 'Active') ? 0 : 1;
        Banner::where('id', $data['banner_id'])->update(['status' => $status]);
        return $status;
    }

    /**
     * Add or Edit a Banner
     */
    public function addEditBanner($request)
    {
        $data = $request->all();

        $banner = isset($data['id']) ? Banner::find($data['id']) : new Banner();

        $banner->type = $request->type;
        $banner->link = $request->link;
        $banner->title = $request->title;
        $banner->alt = $request->alt;
        $banner->sort = $request->sort;
        $banner->status = $request->status;

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = 'front/images/banners/';
            if (!File::exists(public_path($path))) {
                File::makeDirectory(public_path($path), 0755, true);
            }

            // Delete old image if editing
            if (!empty($banner->image) && File::exists(public_path($path . $banner->image))) {
                File::delete(public_path($path . $banner->image));
            }

            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path($path), $imageName);
            $banner->image = $imageName;
        }

        $banner->save();
        return isset($data['id']) ? 'Banner updated successfully!' : 'Banner added successfully!';
    }

    /**
     * Delete a Banner
     */
    public function deleteBanner($id)
    {
        $banner = Banner::findOrFail($id);
        $bannerImagePath = public_path('front/images/banners/' . $banner->image);

        if (File::exists($bannerImagePath)) {
            File::delete($bannerImagePath);
        }

        $banner->delete();

        return[
            'status' => 'success',
            'message' => 'Banner deleted successfully!'
        ];
    }
}
