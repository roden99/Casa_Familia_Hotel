<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosDeliveryItem extends Model
{
    protected $fillable = [
        'pos_delivery_id',
        'product_id',
        'pos_product_lot_id',
        'quantity',
        'cost',
        'selling_price',
        'created_by',
    ];

    public function posDelivery()
    {
        return $this->belongsTo(PosDelivery::class);
    }

    public function product()
    {
        return $this->belongsTo(product::class);
    }

    public function posProductLot()
    {
        return $this->belongsTo(PosProductLot::class, 'pos_product_lot_id');
    }
}
