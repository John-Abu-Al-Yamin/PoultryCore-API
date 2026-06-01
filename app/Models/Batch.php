<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    protected function casts(): array
    {
        return [
            'current_quantity' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    protected $fillable = [
        'user_id',
        'barn_id',
        'poultry_type',
        'current_quantity',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    public function barn()
    {
        return $this->belongsTo(Barn::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
    public function deaths()
    {
        return $this->hasMany(Death::class);
    }
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
