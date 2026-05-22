<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    //
    protected $fillable = [
        'user_id',
        'barn_id',
        'poultry_type',
        'initial_quantity',
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
}
