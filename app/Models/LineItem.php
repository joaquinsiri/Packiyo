<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineItem extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'product_id', 'quantity'];

    protected $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
    ];

    public function order() : BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product() : BelongsTo
    {
        return $this->belongsTo(Product::class);
        
    }
}
