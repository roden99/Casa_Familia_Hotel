<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosTransactionItem extends Model
{
    protected $fillable = [
        'pos_transaction_id',
        'product_id',
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

    public function product()
    {
        return $this->belongsTo(product::class, 'product_id');
    }
}
