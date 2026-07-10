<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferStock extends Model
{
    protected $fillable = [
        'transfer_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function items()
    {
        return $this->hasMany(TransferStockItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
