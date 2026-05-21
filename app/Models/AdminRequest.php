<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRequest extends Model
{
    protected $fillable = [
        'user_id',
        'venue_name',
        'phone',
        'reason',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}