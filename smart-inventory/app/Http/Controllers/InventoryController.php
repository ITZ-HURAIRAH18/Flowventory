<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
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

    public function index()
    {
        $inventory = Inventory::with(['product', 'branch'])->get();

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

        $this->service->transfer(
            $request->from_branch_id,
            $request->to_branch_id,
            $request->product_id,
            $request->quantity
        );

        return response()->json(['message' => 'Stock transferred successfully']);
    }

    public function history()
    {
        $movements = StockMovement::with(['product', 'branch'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
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
}
