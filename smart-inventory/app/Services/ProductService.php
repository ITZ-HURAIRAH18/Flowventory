<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    public function list($search = null)
    {
        return Product::when($search, function ($query) use ($search) {
            $query->where('name', 'like', "%$search%")
                  ->orWhere('sku', 'like', "%$search%");
        })
        ->latest()
        ->paginate(10);
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