<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_sales_account_id',
        'invoice_no',
        'invoice_date',
        'delivery_date',
        'discount_percentage',
        'terms',
        'payment_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'terms' => 'integer',
    ];

    public function customerSalesAccount()
    {
        return $this->belongsTo(CustomerSalesAccount::class);
    }

    public function items()
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function payment()
    {
        return $this->belongsTo(\App\Models\CustomerSalesAccountPayment::class, 'payment_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
