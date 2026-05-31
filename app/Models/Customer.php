<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'address',
        'total_debts',
    ];

    protected $casts = [
        'total_debts' => 'decimal:2',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function recalculateTotalDebts(): static
    {
        $this->total_debts = $this->sales()->sum('total_price') - $this->sales()->sum('paid_amount');
        $this->save();

        return $this;
    }

    protected function debtsBalance(): Attribute
    {
        return Attribute::get(function () {
            return $this->total_debts;
        });
    }
}
