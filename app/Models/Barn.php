<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barn extends Model
{
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    protected $fillable = [
        'user_id',
        'name',
        'location',
        'capacity',
        'notes',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }
}
