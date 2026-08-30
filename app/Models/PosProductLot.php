<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosProductLot extends Model
{
    protected $table = 'pos_product_lots';

    protected $fillable = [
        'product_id',
        'lot_number',
        'expiration_date',
        'quantity',
        'cost',
        'selling_price',
        'created_by',
        'updated_by',
    ];

    public function product()
    {
        return $this->belongsTo(product::class);
    }

    public function transactionItems()
    {
        return $this->hasMany(PosTransactionItem::class, 'pos_product_lot_id');
    }
}
