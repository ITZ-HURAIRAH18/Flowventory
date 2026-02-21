<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Inventory;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    /**
     * Get summary report data across branches based on user role.
     * Super admin sees all branches, managers see only their own.
     *
     * @param array $branchIds - Filtered branch IDs based on user role
     * @return array
     */
    public function getSummaryReport(array $branchIds): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        return [
            'today_sales' => $this->getTodaySales($branchIds, $today),
            'monthly_sales' => $this->getMonthlySales($branchIds, $startOfMonth),
            'total_orders' => $this->getTotalOrders($branchIds),
            'top_products' => $this->getTopProducts($branchIds, 10), // Limit to top 10
            'low_stock' => $this->getLowStockItems($branchIds, 50), // Paginate to 50 per page
        ];
    }

    /**
     * Get detailed report for a specific branch.
     *
     * @param int $branchId
     * @return array
     */
    public function getBranchReport(int $branchId): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        return [
            'today_sales' => $this->getTodaySales([$branchId], $today),
            'monthly_sales' => $this->getMonthlySales([$branchId], $startOfMonth),
            'total_orders' => $this->getTotalOrders([$branchId]),
            'top_products' => $this->getTopProducts([$branchId], 10), // Limit to top 10
            'low_stock' => $this->getLowStockItems([$branchId], 50), // Paginate to 50 per page
        ];
    }

    /**
     * Calculate total sales for today.
     *
     * @param array $branchIds
     * @param Carbon $date
     * @return float
     */
    private function getTodaySales(array $branchIds, Carbon $date): float
    {
        return (float) Order::whereIn('branch_id', $branchIds)
            ->whereDate('created_at', $date)
            ->sum('total');
    }

    /**
     * Calculate total sales for the current month.
     *
     * @param array $branchIds
     * @param Carbon $startDate
     * @return float
     */
    private function getMonthlySales(array $branchIds, Carbon $startDate): float
    {
        return (float) Order::whereIn('branch_id', $branchIds)
            ->where('created_at', '>=', $startDate)
            ->sum('total');
    }

    /**
     * Get total order count.
     *
     * @param array $branchIds
     * @return int
     */
    private function getTotalOrders(array $branchIds): int
    {
        return Order::whereIn('branch_id', $branchIds)->count();
    }

    /**
     * Get top 5-10 products by quantity sold.
     *
     * @param array $branchIds
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getTopProducts(array $branchIds, int $limit = 5)
    {
        return OrderItem::select(
            'products.name',
            DB::raw('SUM(order_items.quantity) as total_sold')
        )
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('orders.branch_id', $branchIds)
            ->groupBy('products.name')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();
    }

    /**
     * Get inventory items below stock threshold (10 units).
     *
     * @param array $branchIds
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getLowStockItems(array $branchIds, int $limit = 50)
    {
        return Inventory::whereIn('branch_id', $branchIds)
            ->where('quantity', '<=', 10)
            ->with(['product', 'branch'])
            ->limit($limit)
            ->get();
    }
}
