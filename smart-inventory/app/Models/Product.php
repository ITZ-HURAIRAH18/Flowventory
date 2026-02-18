<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'sku', 'cost_price', 'sale_price', 'tax_percentage', 'status'];
}