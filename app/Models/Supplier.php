<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    //
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'address',
        'total_dues'
    ];

    protected $casts = [
        'total_dues' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
