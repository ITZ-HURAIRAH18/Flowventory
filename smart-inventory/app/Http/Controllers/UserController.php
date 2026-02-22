<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Services\UserService;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/users
     * List all users with search & role filter (paginated).
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 8);
        
        return response()->json(
            $this->service->list($request->search, $request->role, $perPage)
        );
    }

    /**
     * GET /api/users/{user}
     * Show a single user with role.
     */
    public function show(User $user)
    {
        return response()->json($user->load('role'));
    }

    /**
     * POST /api/users
     * Create a new user.
     */
    public function store(StoreUserRequest $request)
    {
        return response()->json(
            $this->service->create($request->validated()),
            201
        );
    }

    /**
     * PUT /api/users/{user}
     * Update an existing user.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        return response()->json(
            $this->service->update($user, $request->validated())
        );
    }

    /**
     * DELETE /api/users/{user}
     * Delete a user.
     */
    public function destroy(User $user)
    {
        $this->service->delete($user);
        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * GET /api/users/managers
     * Fetch only branch managers (for branch form dropdowns).
     */
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

    /**
     * GET /api/roles
     * Return all available roles (for dropdowns).
     */
    public function getRoles()
    {
        return response()->json($this->service->getRoles());
    }
}