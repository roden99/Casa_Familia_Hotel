<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnToSupplier extends Model
{
    protected $fillable = [
        'supplier_id',
        'return_date',
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
        return $this->hasMany(ReturnToSupplierItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
