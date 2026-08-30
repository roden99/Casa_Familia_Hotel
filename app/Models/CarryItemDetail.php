<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarryItemDetail extends Model
{
    protected $fillable = [
        'carry_item_id',
        'product_id',
        'lot_id',
        'quantity',
        'created_by',
    ];

    public function carryItem()
    {
        return $this->belongsTo(CarryItem::class);
    }

    public function product()
    {
        return $this->belongsTo(product::class);
    }

    public function lot()
    {
        return $this->belongsTo(ProductLot::class, 'lot_id');
    }
}
