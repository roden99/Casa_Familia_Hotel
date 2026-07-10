<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferStockItem extends Model
{
    protected $fillable = [
        'transfer_stock_id',
        'product_id',
        'quantity',
        'multiplier',
        'created_by',
    ];

    public function transferStock()
    {
        return $this->belongsTo(TransferStock::class);
    }

    public function product()
    {
        return $this->belongsTo(product::class);
    }
}
