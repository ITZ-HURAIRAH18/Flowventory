<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Exception;

class OrderController extends Controller
{
    protected $service;

    public function __construct(OrderService $service)
    {
        $this->service = $service;
    }

    /**
     * POST /api/orders
     * Create a new order with items and manage inventory deductions.
     */
    public function store(StoreOrderRequest $request)
    {
        try {
            $order = $this->service->create(
                $request->branch_id,
                $request->user()->id,
                $request->items
            );

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}