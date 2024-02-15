<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'quantity', 'allocated_quantity'];

    protected $casts = [
        'product_id' => 'integer',
        'quantity' => 'integer',
        'allocated_quantity' => 'integer',
    ];

    public function product() : BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
