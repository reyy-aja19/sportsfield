<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    protected $fillable = [
    'user_id',
    'name',
    'city',
    'address',
    'phone',
    'email',
    'description',
    'status',
    'photo',
    'google_maps',
    'map_embed',
    'approval_status',
];

    public function lapangan()
    {
        return $this->hasMany(Lapangan::class);
    }

    public function user()
{
    return $this->belongsTo(User::class);
}
}