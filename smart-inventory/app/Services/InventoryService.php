<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryService
{
    public function addStock($branchId, $productId, $qty)
    {
        return DB::transaction(function () use ($branchId, $productId, $qty) {

            $inventory = Inventory::firstOrCreate(
                ['branch_id'=>$branchId,'product_id'=>$productId],
                ['quantity'=>0]
            );

            $inventory->increment('quantity', $qty);

            StockMovement::create([
                'branch_id'=>$branchId,
                'product_id'=>$productId,
                'type'=>'add',
                'quantity'=>$qty
            ]);

            return $inventory;
        });
    }

    public function adjustStock($branchId, $productId, $qty)
    {
        return DB::transaction(function () use ($branchId, $productId, $qty) {

            $inventory = Inventory::where([
                'branch_id'=>$branchId,
                'product_id'=>$productId
            ])->firstOrFail();

            $newQty = $inventory->quantity + $qty;

            if ($newQty < 0) {
                throw new Exception("Stock cannot go negative");
            }

            $inventory->update(['quantity'=>$newQty]);

            StockMovement::create([
                'branch_id'=>$branchId,
                'product_id'=>$productId,
                'type'=>'adjust',
                'quantity'=>$qty
            ]);

            return $inventory;
        });
    }

    public function transfer($fromBranch, $toBranch, $productId, $qty)
    {
        return DB::transaction(function () use ($fromBranch,$toBranch,$productId,$qty){

            $fromInventory = Inventory::where([
                'branch_id'=>$fromBranch,
                'product_id'=>$productId
            ])->firstOrFail();

            if ($fromInventory->quantity < $qty) {
                throw new Exception("Not enough stock");
            }

            $fromInventory->decrement('quantity',$qty);

            $toInventory = Inventory::firstOrCreate(
                ['branch_id'=>$toBranch,'product_id'=>$productId],
                ['quantity'=>0]
            );

            $toInventory->increment('quantity',$qty);

            StockMovement::create([
                'branch_id'=>$fromBranch,
                'product_id'=>$productId,
                'type'=>'transfer_out',
                'quantity'=>$qty
            ]);

            StockMovement::create([
                'branch_id'=>$toBranch,
                'product_id'=>$productId,
                'type'=>'transfer_in',
                'quantity'=>$qty
            ]);
        });
    }
}