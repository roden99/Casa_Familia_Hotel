<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosDelivery extends Model
{
    protected $fillable = [
        'supplier_id',
        'invoice_no',
        'delivery_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function supplier()
    {
        return $this->belongsTo(supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PosDeliveryItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
