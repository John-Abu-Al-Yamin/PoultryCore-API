<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'address',
        'total_dues',
    ];

    protected $casts = [
        'total_dues' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function recalculateTotalDues(): static
    {
        $this->total_dues = $this->purchases()->sum('total_price') - $this->purchases()->sum('paid_amount');
        $this->save();

        return $this;
    }

    protected function duesBalance(): Attribute
    {
        return Attribute::get(function () {
            return $this->purchases()->sum('total_price') - $this->purchases()->sum('paid_amount');
        });
    }
}
