<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'created_at', 'updated_at'];

    protected $casts = [
        'customer_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer() : BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lineItems() : HasMany
    {
        return $this->hasMany(LineItem::class);
    } 

    public function getReadyToShipAttribute()
    {
        foreach ($this->lineItems as $lineItem) {
            $inventory = $lineItem->product->inventory;
    
            if ($inventory) {
                $availableQuantity = $inventory->quantity - $inventory->allocated_quantity;
    
                if ($lineItem->quantity > $availableQuantity) {
                    return false;
                }
            } else {
                throw new \Exception('Inventory not found for product ' . $lineItem->product_id);
            }
        }
    
        return true;
    }
}
