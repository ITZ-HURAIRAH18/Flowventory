<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin
        $adminRole = Role::where('name', 'super_admin')->first();
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole->id,
            ]
        );

        // Create Branch Manager Role
        $managerRole = Role::where('name', 'branch_manager')->first();

        // Create a few managers
        User::firstOrCreate(
            ['email' => 'manager1@example.com'],
            [
                'name' => 'Branch Manager 1',
                'password' => Hash::make('password123'),
                'role_id' => $managerRole->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'manager2@example.com'],
            [
                'name' => 'Branch Manager 2',
                'password' => Hash::make('password123'),
                'role_id' => $managerRole->id,
            ]
        );

        // Optional: create sales users if needed
        $salesRole = Role::where('name', 'sales_user')->first();

        User::firstOrCreate(
            ['email' => 'sales1@example.com'],
            [
                'name' => 'Sales User 1',
                'password' => Hash::make('password123'),
                'role_id' => $salesRole->id,
            ]
        );
    }
}