<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'customer_id',
        'batch_id',
        'item_name',
        'unit',
        'quantity',
        'unit_price',
        'sale_number',
        'total_price',
        'paid_amount',
        'status',
        'sale_date',
        'payment_type',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'sale_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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
        static::restored(function (Sale $sale) {
            $batch = $sale->batch;
            if ($batch) {
                $batch->decrement('current_quantity', $sale->quantity);
            }

            if ($sale->payment_type === 'credit') {
                $remaining = $sale->total_price - $sale->paid_amount;
                if ($remaining > 0 && $sale->customer) {
                    $sale->customer->increment('total_debts', $remaining);
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
