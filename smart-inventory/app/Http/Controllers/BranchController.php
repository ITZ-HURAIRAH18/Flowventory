<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::with('manager');

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhereHas('manager', function($managerQuery) use ($search) {
                      $managerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // If per_page is specified, use pagination, otherwise return all
        $perPage = $request->get('per_page');
        
        if ($perPage) {
            $branches = $query->paginate($perPage);
        } else {
            $branches = $query->get();
        }
        
        return response()->json($branches);
    }

    public function store(StoreBranchRequest $request)
    {
        $branch = Branch::create($request->validated());
        return response()->json($branch, 201);
    }

    public function show(Branch $branch)
    {
        $branch->load('manager', 'users');
        return response()->json($branch);
    }

    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $branch->update($request->validated());
        return response()->json($branch);
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();
        return response()->json(['message' => 'Branch deleted successfully']);
    }
}