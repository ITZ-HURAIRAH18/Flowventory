<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * List users with optional search & role filter.
     */
    public function list($search = null, $roleFilter = null, $perPage = 8)
    {
        $query = User::with('role')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%");
                });
            })
            ->when($roleFilter, function ($query) use ($roleFilter) {
                $query->whereHas('role', function ($q) use ($roleFilter) {
                    $q->where('name', $roleFilter);
                });
            })
            ->latest();

        $paginatedData = $query->paginate($perPage);
        
        // Get role counts for stats (without pagination filters)
        $roleCounts = User::join('roles', 'users.role_id', '=', 'roles.id')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('users.name', 'like', "%$search%")
                      ->orWhere('users.email', 'like', "%$search%");
                });
            })
            ->selectRaw('roles.name, count(*) as count')
            ->groupBy('roles.name')
            ->pluck('count', 'name')
            ->toArray();

        // Add role counts to pagination data
        $paginatedData = $paginatedData->toArray();
        $paginatedData['role_counts'] = [
            'super_admin' => $roleCounts['super_admin'] ?? 0,
            'branch_manager' => $roleCounts['branch_manager'] ?? 0,
            'sales_user' => $roleCounts['sales_user'] ?? 0,
        ];

        return $paginatedData;
    }

    /**
     * Create a new user.
     */
    public function create(array $data)
    {
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return $user->load('role');
    }

    /**
     * Update an existing user.
     */
    public function update(User $user, array $data)
    {
        // Only hash password if it was provided
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return $user->load('role');
    }

    /**
     * Delete a user.
     */
    public function delete(User $user)
    {
        return $user->delete();
    }

    /**
     * Get all available roles (for dropdown).
     */
    public function getRoles()
    {
        return Role::all();
    }
}
