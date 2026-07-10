<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosTransaction extends Model
{
    protected $fillable = [
        'receipt_no',
        'sale_date',
        'customer_id',
        'payment_method',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function items()
    {
        return $this->hasMany(PosTransactionItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
