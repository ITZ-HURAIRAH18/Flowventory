<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Http\Request;
use Exception;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            return DB::transaction(function () use ($request) {

                $subtotal = 0;
                $orderItemsData = [];

                foreach ($request->items as $item) {

                    // Lock inventory row to prevent race conditions
                    $inventory = Inventory::where('branch_id', $request->branch_id)
                        ->where('product_id', $item['product_id'])
                        ->lockForUpdate()
                        ->first();

                    if (!$inventory || $inventory->quantity < $item['quantity']) {
                        throw new Exception('Insufficient stock for product ID: ' . $item['product_id']);
                    }

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

                    // Deduct stock
                    $inventory->quantity -= $item['quantity'];
                    $inventory->save();
                }

                $tax = collect($orderItemsData)->sum('tax');
                $total = $subtotal + $tax;

                $order = Order::create([
                    'branch_id' => $request->branch_id,
                    'user_id' => $request->user()->id,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                ]);

                foreach ($orderItemsData as $data) {
                    $order->items()->create($data);
                }

                return response()->json([
                    'message' => 'Order created successfully',
                    'order' => $order->load('items'),
                ], 201);
            });
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}