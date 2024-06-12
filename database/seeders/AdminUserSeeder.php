<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
            'title' => 'Admin',
            'description' => 'Admin',
            'leaves' => 20,
        ],
        [
            'title' => 'Project Manager',
            'description' => 'Project Manager',
            'leaves' => 20,
        ],[
            'title' => 'Frontend Teamlead',
            'description' => 'Frontend Teamlead',
            'leaves' => 20,
        ],[
            'title' => 'Backend Teamlead',
            'description' => 'Backend Teamlead',
            'leaves' => 20,
        ],[
            'title' => 'Frontend Developer',
            'description' => 'Frontend Developer',
            'leaves' => 20,
        ],[
            'title' => 'Backend Developer',
            'description' => 'Backend Developer',
            'leaves' => 20,
        ]
            
        ];

        foreach ($roles as $role) {
            Role::create([
                'title' => $role['title'],
                'description' => $role['description'],
                'leaves' => $role['leaves']
            ]);
        }

        $role = Role::where('title', 'Admin')->get();

        foreach ($role as $roledata) {
            $roleId = $roledata->id;
        }

        $user = User::create([
            'first_name' => 'Admin',
            'last_name' => 'test',
            'email' => 'admin@staffmanagement.com',
            'password' => Hash::make('C9LD*rrC'),
            'role_id' => $roleId, 
        ]);

        $user->userMeta()->create([
            'address' => 'test address',
            'contact_no' => 8606887766,
            'gender' => 'male',
            'date_of_birth' => '1998/01/01 09:00:00',
            'join_date' => '2021/01/01 09:00:00',
            'father' => 'test',
            'mother' => 'test',
            'marital_status' => 'single',
            'pincode' => '695111',
            'aadhar' => '123456789012',
            'pan' => '7894561230',
            'profile_pic' => 'test',
        ]);
    }
}
