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
    public function list($search = null, $roleFilter = null)
    {
        return User::with('role')
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
            ->latest()
            ->paginate(10);
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
