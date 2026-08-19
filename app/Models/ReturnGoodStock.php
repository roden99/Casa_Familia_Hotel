<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnGoodStock extends Model
{
    protected $fillable = [
        'sales_order_id',
        'rgs_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function items()
    {
        return $this->hasMany(ReturnGoodStockItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
