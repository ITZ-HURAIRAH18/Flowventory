<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderService
{
    /**
     * Create a new order with items and manage inventory deductions.
     *
     * @param int $branchId
     * @param int $userId
     * @param array $items - Array of ['product_id' => int, 'quantity' => int]
     * @return Order
     * @throws Exception
     */
    public function create(int $branchId, int $userId, array $items): Order
    {
        return DB::transaction(function () use ($branchId, $userId, $items) {
            $subtotal = 0;
            $orderItemsData = [];

            // Process each item: validate stock, calculate prices, prepare item data
            foreach ($items as $item) {
                $inventory = $this->getAndValidateInventory($branchId, $item['product_id'], $item['quantity']);
                $product = Product::findOrFail($item['product_id']);

                $linePrice = $product->sale_price * $item['quantity'];
                $lineTax = $linePrice * ($product->tax_percentage / 100);
                $subtotal += $linePrice;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->sale_price,
                    'tax' => $lineTax,
                ];

                // Deduct stock from inventory
                $inventory->decrement('quantity', $item['quantity']);
            }

            // Calculate totals
            $tax = collect($orderItemsData)->sum('tax');
            $total = $subtotal + $tax;

            // Create order
            $order = Order::create([
                'branch_id' => $branchId,
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ]);

            // Create order items
            foreach ($orderItemsData as $data) {
                $order->items()->create($data);
            }

            return $order->load('items');
        });
    }

    /**
     * Get inventory and validate sufficient stock.
     * Uses pessimistic locking to prevent race conditions.
     *
     * @param int $branchId
     * @param int $productId
     * @param int $requestedQty
     * @return Inventory
     * @throws Exception
     */
    private function getAndValidateInventory(int $branchId, int $productId, int $requestedQty): Inventory
    {
        $inventory = Inventory::where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (!$inventory || $inventory->quantity < $requestedQty) {
            throw new Exception('Insufficient stock for product ID: ' . $productId);
        }

        return $inventory;
    }
}
