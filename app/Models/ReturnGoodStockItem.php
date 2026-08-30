<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnGoodStockItem extends Model
{
    protected $fillable = [
        'return_good_stock_id',
        'product_id',
        'lot_id',
        'quantity',
        'unit_price',
        'created_by',
        'updated_by',
    ];

    public function returnGoodStock()
    {
        return $this->belongsTo(ReturnGoodStock::class);
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
