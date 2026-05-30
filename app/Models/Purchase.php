<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'user_id',
        'supplier_id',
        'batch_id',
        'item_name',
        'quantity',
        'unit_price',
        'total_price',
        'purchase_date',
        'payment_type',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'purchase_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    protected function remainingAmount(): Attribute
    {
        return Attribute::get(function () {
            $paid = $this->payments()->sum('amount');
            return max(0, $this->total_price - $paid);
        });
    }

    protected function paymentStatus(): Attribute
    {
        return Attribute::get(function () {
            $paid = $this->payments()->sum('amount');
            if ($paid <= 0) {
                return 'unpaid';
            }
            if ($paid >= $this->total_price) {
                return 'paid';
            }
            return 'partial';
        });
    }
}
