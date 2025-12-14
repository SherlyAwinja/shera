<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('12345678');
        
        $admin = new Admin;
        $admin->name = 'Sheryl Awinja';
        $admin->role = 'admin';
        $admin->mobile = '1234567890';
        $admin->email = 'admin@admin.com';
        $admin->password = $password;
        $admin->status = 1;
        $admin->save();


        $admin = new Admin;
        $admin->name = 'Shera';
        $admin->role = 'subadmin';
        $admin->mobile = '0740549910';
        $admin->email = 'shera@admin.com';
        $admin->password = $password;
        $admin->status = 1;
        $admin->save();

        $admin = new Admin;
        $admin->name = 'Jane Doe';
        $admin->role = 'subadmin';
        $admin->mobile = '0123456789';
        $admin->email = 'jane@admin.com';
        $admin->password = $password;
        $admin->status = 1;
        $admin->save();
    }
}
