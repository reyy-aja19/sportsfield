<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    protected $fillable = [
        'name',
        'city',
        'address',
        'phone',
        'email',
        'description',
        'status',
        'photo',

        // tambahan maps
        'google_maps',
        'map_embed',
    ];

    public function lapangan()
    {
        return $this->hasMany(Lapangan::class);
    }
}