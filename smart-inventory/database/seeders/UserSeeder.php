<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Roles ───
        $adminRole   = Role::where('name', 'super_admin')->first();
        $managerRole = Role::where('name', 'branch_manager')->first();
        $salesRole   = Role::where('name', 'sales_user')->first();

        // ─── Super Admin ───
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Admin User',
                'password' => Hash::make('password123'),
                'role_id'  => $adminRole->id,
            ]
        );

        // ─── Branch Managers ───
        $manager1 = User::firstOrCreate(
            ['email' => 'manager1@example.com'],
            [
                'name'     => 'Branch Manager 1',
                'password' => Hash::make('password123'),
                'role_id'  => $managerRole->id,
            ]
        );

        $manager2 = User::firstOrCreate(
            ['email' => 'manager2@example.com'],
            [
                'name'     => 'Branch Manager 2',
                'password' => Hash::make('password123'),
                'role_id'  => $managerRole->id,
            ]
        );

        // ─── Sample Branches (assign managers via manager_id on branches table) ───
        Branch::firstOrCreate(
            ['name' => 'Main Branch'],
            [
                'address'    => '123 Main Street',
                'manager_id' => $manager1->id,
            ]
        );

        Branch::firstOrCreate(
            ['name' => 'North Branch'],
            [
                'address'    => '456 North Avenue',
                'manager_id' => $manager2->id,
            ]
        );

        // ─── Sales Users ───
        User::firstOrCreate(
            ['email' => 'sales1@example.com'],
            [
                'name'     => 'Sales User 1',
                'password' => Hash::make('password123'),
                'role_id'  => $salesRole->id,
            ]
        );
    }
}