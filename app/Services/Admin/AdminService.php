<?php
namespace App\Services\Admin;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Laravel\Facades\Image;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\AdminsRole;


class AdminService
{
    public function login($data)
    {
        $admin = Admin::where('email', $data['email'])->first();
        if($admin){
            if($admin->status == 0){
                return "inactive"; // Return status if the account is inactive
            }
            if(Auth::guard('admin')->attempt(['email' => $data['email'], 'password' => $data['password'], 'status' => 1])){
                // Remember Admin Email and Password
                if(!empty($data["remember"])){
                    setcookie("email", $data["email"], time() + 3600);
                    setcookie("password", $data["password"], time() + 3600);
                } else {
                    setcookie("email", "");
                    setcookie("password", "");
                }
                return "success"; // Return Success if login is successful
            } else {
                return "invalid"; // Return Invalid if credentials are incorrect
            }
        } else {
            return "not_found"; // Return Not Found if email is not found
        }
    }


    public function verifyPassword($data)
    {
        if (Hash::check($data['current_password'], Auth::guard('admin')->user()->password)) {
            return true;
        } else {
            return false;
        }
    }

    public function updatePassword($data)
    {
        
        // Verify current password
        if (Hash::check($data['current_password'], Auth::guard('admin')->user()->password)) {
            // Check if new password and confirm password matches
            if ($data['new_password'] == $data['confirm_password']) {
                Admin::where('email', Auth::guard('admin')->user()->email)
                ->update(['password' => bcrypt($data['new_password'])]);
                $status = "success";
                $message = "Password has been updated successfully";
            } else {
                $status = "error";
                $message = "New password and confirm password must match!";
            }
        } else {
            $status = "error";
            $message = "Current password is incorrect";
        }
        return ['status' => $status, 'message' => $message];
    }

    public function updateDetails($request)
    {
        $data = $request->all();
        
        // Upload Admin Image
        if ($request->hasFile('image')) {
            $image_tmp = $request->file('image');
            if ($image_tmp->isValid()) {
                $manager = new ImageManager(new Driver());
                $image = $manager->read($image_tmp);
                $extension = $image_tmp->getClientOriginalExtension();
                $imageName = rand(111, 99999) . '.' . $extension;
                $imagePath = 'admin/images/photos/' . $imageName; // Use 'public_path()' to save images
                $image->save($imagePath);
            }
        } else if(!empty($data['current_image'])) {
            $imageName = $data['current_image'];
        } else {
            $imageName = "";
        }
        
        // Update Admin Details
        Admin::where('email', Auth::guard('admin')->user()->email)
        ->update([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'image' => $imageName,
        ]);
    }

    public function deleteProfileImage($adminId)
    {
        $profileImage = Admin::where('id', $adminId)->value('image');
        if($profileImage) {
            $profileImagePath = 'admin/images/photos/' . $profileImage;
            if(file_exists(public_path($profileImagePath))) {
                unlink(public_path($profileImagePath));
            }
            Admin::where('id', $adminId)->update(['image' => null]);
            return ['status' => 'true', 'message' => 'Profile image deleted successfully'];
        }
        return ['status' => 'false', 'message' => 'Profile image not found'];
    }

    public function subadmins()
    {
        $subadmins = Admin::where('role', 'subadmin')->get();
        return $subadmins;
    }

    public function updateSubadminStatus($data)
    {
        $status = $data['status'] == "Active" ? 0 : 1;
        Admin::where('id', $data['subadmin_id'])->update(['status' => $status]);
        return $status;
    }

    public function deleteSubadmin($id)
    {
        Admin::where('id', $id)->delete();
        $message = "Subadmin deleted successfully";
        return array("message" => $message);
    }

    public function addEditSubadmin($request)
    {
        $data = $request->all();
        if(isset($data['id']) && $data['id'] != ""){
            $subadmindata = Admin::find($data['id']);
            $message = "Subadmin updated successfully";
        } else {
            $subadmindata = new Admin();
            $message = "Subadmin added successfully";
        }

        // Upload Subadmin Image
        if ($request->hasFile('image')) {
            $image_tmp = $request->file('image');
            if ($image_tmp->isValid()) {
                // Create Image Manager with desired driver
                $manager = new ImageManager(new Driver());

                // Read the image from file
                $image = $manager->read($image_tmp);

                // Get the extension of the image
                $extension = $image_tmp->getClientOriginalExtension();

                // Generate a unique name for the image
                $imageName = rand(111, 99999) . '.' . $extension;
                $image_path = 'admin/images/photos/' . $imageName;

                // Save Image in specified path
                $image->save($image_path);
            }
        } else if(!empty($data['current_image'])) {
            $imageName = $data['current_image'];
        } else {
            $imageName = "";
        }
        
        $subadmindata->image = $imageName;
        $subadmindata->name = $data['name'] ?? null;
        $subadmindata->mobile = $data['mobile'] ?? null;

        if(!isset($data['id']) || $data['id'] == "") {
            $subadmindata->email = $data['email'] ?? null;
            $subadmindata->role = 'subadmin';
            $subadmindata->status = 1;
        }

        if(isset($data['password']) && $data['password'] != "") {
            $subadmindata->password = bcrypt($data['password']);
        }

        $subadmindata->save();
        return array("message" => $message);
    }

    public function updateRole($request)
    {
        $data = $request->all();
        
        // Remove existing roles before updating
        AdminsRole::where('subadmin_id', $data['subadmin_id'])->delete();

        // Assign new roles dynamically
        foreach($data as $key => $value) {
            if(!is_array($value)) continue; // Skip non-module fields

            $view = isset($value['view']) ? $value['view'] : 0;
            $edit = isset($value['edit']) ? $value['edit'] : 0;
            $full = isset($value['full']) ? $value['full'] : 0;

            AdminsRole::insert([
                'subadmin_id' => $data['subadmin_id'],
                'module' => $key,
                'view_access' => $view,
                'edit_access' => $edit,
                'full_access' => $full,
            ]);
        }
        return ['message' => 'Subadmin Roles updated successfully'];
    }
}