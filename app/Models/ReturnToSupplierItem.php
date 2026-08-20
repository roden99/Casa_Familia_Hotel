<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnToSupplierItem extends Model
{
    protected $fillable = [
        'return_to_supplier_id',
        'product_id',
        'lot_id',
        'quantity',
        'unit_price',
        'created_by',
        'updated_by',
    ];

    public function returnToSupplier()
    {
        return $this->belongsTo(ReturnToSupplier::class);
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
