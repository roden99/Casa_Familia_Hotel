<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosTransactionItem extends Model
{
    protected $fillable = [
        'pos_transaction_id',
        'pos_product_lot_id',
        'quantity',
        'unit_price',
        'discount_percentage',
        'total_price',
        'created_by',
    ];

    public function posTransaction()
    {
        return $this->belongsTo(PosTransaction::class);
    }

    public function posProductLot()
    {
        return $this->belongsTo(PosProductLot::class, 'pos_product_lot_id');
    }
}
