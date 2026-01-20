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
        $banners=Banner::orderBy('sort','asc')->get();
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
