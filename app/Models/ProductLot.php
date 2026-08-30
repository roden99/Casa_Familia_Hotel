<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductLot extends Model
{
    protected $table = 'product_lots';

    protected $fillable = [
        'product_id',
        'lot_number',
        'expiration_date',
        'quantity',
        'created_by',
        'updated_by',
    ];

    public function product()
    {
        return $this->belongsTo(product::class);
    }

    public function deliveryItems()
    {
        return $this->hasMany(DeliveryItem::class, 'lot_id');
    }
}
