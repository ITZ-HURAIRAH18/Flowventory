<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function branchReport($branchId)
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        // Total Sales Today
        $todaySales = Order::where('branch_id', $branchId)
            ->whereDate('created_at', $today)
            ->sum('total');

        // Total Sales This Month
        $monthlySales = Order::where('branch_id', $branchId)
            ->where('created_at', '>=', $startOfMonth)
            ->sum('total');

        // Total Orders
        $totalOrders = Order::where('branch_id', $branchId)->count();

        // Top 5 Products
        $topProducts = OrderItem::select(
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_sold')
            )
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.branch_id', $branchId)
            ->groupBy('products.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        // Low Stock Items (threshold = 10)
        $lowStock = Inventory::where('branch_id', $branchId)
            ->where('quantity', '<=', 10)
            ->with('product')
            ->get();

        return response()->json([
            'today_sales' => $todaySales,
            'monthly_sales' => $monthlySales,
            'total_orders' => $totalOrders,
            'top_products' => $topProducts,
            'low_stock' => $lowStock,
        ]);
    }
}