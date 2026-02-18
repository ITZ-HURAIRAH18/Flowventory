<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // 🔹 Fetch users by role (managers)
    public function getManagers()
    {
        $managerRole = Role::where('name', 'branch_manager')->first();

        if (!$managerRole) {
            return response()->json([], 200);
        }

        $managers = User::where('role_id', $managerRole->id)
            ->select('id', 'name', 'email')
            ->get();

        return response()->json($managers);
    }
}