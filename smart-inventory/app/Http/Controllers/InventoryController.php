<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class InventoryController extends Controller
{
    protected $service;

    public function __construct(InventoryService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $user->load('role');

        $query = Inventory::with(['product', 'branch']);

        // Manager sees only their branch inventory
        if ($user->role->name === 'branch_manager') {
            $branchIds = Branch::where('manager_id', $user->id)->pluck('id');
            $query->whereIn('branch_id', $branchIds);
        }

        $inventory = $query->get();

        $data = $inventory->map(function ($item) {
            return [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                ],
                'branch' => [
                    'id' => $item->branch->id,
                    'name' => $item->branch->name,
                ],
                'stock' => $item->quantity,
            ];
        });

        return response()->json($data);
    }

    /**
     * Get products available at a specific branch (stock > 0)
     */
    public function productsByBranch($branchId)
    {
        $inventory = Inventory::with('product')
            ->where('branch_id', $branchId)
            ->where('quantity', '>', 0)
            ->get();

        $data = $inventory->map(function ($item) {
            return [
                'id' => $item->product->id,
                'name' => $item->product->name,
                'sale_price' => $item->product->sale_price,
                'tax_percentage' => $item->product->tax_percentage,
                'stock' => $item->quantity,
                'status' => $item->product->status,
            ];
        });

        return response()->json($data);
    }

    public function add(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // Ownership check for managers
        if (!$this->canAccessBranch($request, $request->branch_id)) {
            return response()->json([
                'message' => 'Access denied. You can only manage inventory for your own branch.'
            ], 403);
        }

        // Block inactive products
        $product = Product::find($request->product_id);
        if ($product && $product->status === 'inactive') {
            return response()->json([
                'message' => 'Cannot add stock for an inactive product. Please activate the product first.'
            ], 422);
        }

        $inventory = $this->service->addStock(
            $request->branch_id,
            $request->product_id,
            $request->quantity
        );

        return response()->json($inventory, 201);
    }

    public function adjust(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer',
        ]);

        // Ownership check for managers
        if (!$this->canAccessBranch($request, $request->branch_id)) {
            return response()->json([
                'message' => 'Access denied. You can only manage inventory for your own branch.'
            ], 403);
        }

        // Block inactive products
        $product = Product::find($request->product_id);
        if ($product && $product->status === 'inactive') {
            return response()->json([
                'message' => 'Cannot adjust stock for an inactive product. Please activate the product first.'
            ], 422);
        }

        try {
            $inventory = $this->service->adjustStock(
                $request->branch_id,
                $request->product_id,
                $request->quantity
            );

            return response()->json($inventory);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'No inventory record found for this product at the selected branch. Please add stock first.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }


    public function transfer(Request $request)
    {
        $request->validate([
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id' => 'required|exists:branches,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // Ownership check for managers — must own the source branch
        if (!$this->canAccessBranch($request, $request->from_branch_id)) {
            return response()->json([
                'message' => 'Access denied. You can only transfer stock from your own branch.'
            ], 403);
        }

        // Block inactive products
        $product = Product::find($request->product_id);
        if ($product && $product->status === 'inactive') {
            return response()->json([
                'message' => 'Cannot transfer an inactive product. Please activate the product first.'
            ], 422);
        }

        $this->service->transfer(
            $request->from_branch_id,
            $request->to_branch_id,
            $request->product_id,
            $request->quantity
        );

        return response()->json(['message' => 'Stock transferred successfully']);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        $user->load('role');

        $query = StockMovement::with(['product', 'branch'])
            ->orderBy('created_at', 'desc');

        // Manager sees only their branch history
        if ($user->role->name === 'branch_manager') {
            $branchIds = Branch::where('manager_id', $user->id)->pluck('id');
            $query->whereIn('branch_id', $branchIds);
        }

        $movements = $query->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'product' => [
                    'name' => $item->product->name,
                ],
                'branch' => [
                    'name' => $item->branch->name,
                ],
                'quantity' => $item->quantity,
                'action' => $item->type,
                'created_at' => $item->created_at,
            ];
        });

        return response()->json($movements);
    }

    /**
     * Check if the current user can access a given branch.
     * Super admin can access all branches.
     * Branch manager can only access branches they manage.
     */
    private function canAccessBranch(Request $request, $branchId): bool
    {
        $user = $request->user();
        $user->load('role');

        if ($user->role->name === 'super_admin') {
            return true;
        }

        return Branch::where('id', $branchId)
            ->where('manager_id', $user->id)
            ->exists();
    }
}
