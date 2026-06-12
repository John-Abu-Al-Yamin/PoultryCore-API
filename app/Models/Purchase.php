<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'supplier_id',
        'batch_id',
        'type',
        'item_name',
        'unit',
        'quantity',
        'unit_price',
        'purchase_number',
        'total_price',
        'paid_amount',
        'status',
        'purchase_date',
        'payment_type',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'paid_amount' => 'decimal:2',
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

    protected static function booted(): void
    {
        static::restored(function (Purchase $purchase) {
            if ($purchase->type === 'chicks') {
                $batch = $purchase->batch;
                if ($batch) {
                    $batch->increment('current_quantity', $purchase->quantity);
                }
            }

            if ($purchase->payment_type === 'credit') {
                $remaining = $purchase->total_price - $purchase->paid_amount;
                if ($remaining > 0 && $purchase->supplier) {
                    $purchase->supplier->increment('total_dues', $remaining);
                }
            }
        });
    }

    protected function remainingAmount(): Attribute
    {
        return Attribute::get(function () {
            return max(0, $this->total_price - $this->paid_amount);
        });
    }

    public function recalculateStatus(): void
    {
        if ($this->paid_amount <= 0) {
            $this->status = 'unpaid';
        } elseif ($this->paid_amount >= $this->total_price) {
            $this->status = 'paid';
        } else {
            $this->status = 'partial';
        }

        $this->save();
    }
}
