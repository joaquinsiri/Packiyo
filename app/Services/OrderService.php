<?php

namespace App\Services;

use App\Models\Order;
use App\Models\LineItem;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrder(array $validatedData)
    {
        DB::beginTransaction();

        try {
            $order = Order::create(['customer_id' => $validatedData['data']['relationships']['customer']['data']['id']]);
    
            foreach ($validatedData['data']['relationships']['lineItems']['data'] as $lineItemData) {
                $lineItem = LineItem::create([
                    'order_id' => $order->id,
                    'product_id' => $lineItemData['attributes']['product_id'],
                    'quantity' => $lineItemData['attributes']['quantity'],
                ]);
    
                $inventory = Inventory::where('product_id', $lineItem->product_id)->lockForUpdate()->first();
    
                if ($inventory) {
                    $inventory->allocated_quantity += $lineItem->quantity;
                    $inventory->save();
                } else {
                    throw new \Exception('Inventory not found for product ' . $lineItem->product_id);
                }
            }
    
            DB::commit();

            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
    
            throw $e;
        }
    }
}