<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    public function list($search = null, $status = null, $perPage = null)
    {
        $query = Product::when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('sku', 'like', "%$search%");
            });
        })
        ->when($status, function ($query) use ($status) {
            $query->where('status', $status);
        })
        ->latest();

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