<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::with('manager')->paginate(10);
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