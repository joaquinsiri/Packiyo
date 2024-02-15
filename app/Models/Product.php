<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'price'];

    protected $casts = [
        'name' => 'string',
        'price' => 'decimal:2',
    ];

    public function lineItems() : HasMany
    {
        return $this->hasMany(LineItem::class);
    }

    public function inventory() : HasOne
    {
        return $this->hasOne(Inventory::class);
    }
}
