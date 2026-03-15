<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    public function list($search = null, $status = null, $perPage = null)
    {
        $query = Product::query();

        // Apply search filter - only on name and sku columns
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('sku', 'like', "%$search%");
            });
        }

        // Apply status filter with default to active
        if ($status) {
            $query->where('status', $status);
        } else {
            // Default: show only active products unless specifically requesting all
            if (is_null($status) && request()->has('status')) {
                // Status explicitly set to null in request, show all
            } else if (!request()->has('status')) {
                // No status filter in request, default to active
                $query->where('status', 'active');
            }
        }

        $query->latest();

        // If perPage is provided, use pagination, otherwise return all
        if ($perPage) {
            return $query->paginate($perPage);
        } else {
            return $query->get();
        }
    }

    public function create(array $data)
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data)
    {
        $product->update($data);
        return $product;
    }

    public function delete(Product $product)
    {
        return $product->delete();
    }
}