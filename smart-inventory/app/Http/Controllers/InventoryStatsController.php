<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

class InventoryStatsController extends Controller
{
    /**
     * GET /api/inventory/stats
     * Get inventory summary statistics (total volume, low stock count, etc.)
     * This is a single query for efficient calculation.
     */
    public function stats()
    {
        $totalVolume = Inventory::sum('quantity');
        $lowStockCount = Inventory::where('quantity', '<=', 5)->count();
        $outOfStockCount = Inventory::where('quantity', '<=', 0)->count();
        $totalSkus = Inventory::distinct('product_id')->count();

        return response()->json([
            'total_volume' => $totalVolume ?? 0,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'total_skus' => $totalSkus,
        ]);
    }
}
