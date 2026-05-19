<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lapangan extends Model
{
    use HasFactory;

    protected $table = 'lapangan';

    protected $fillable = [
    'venue_id',
    'nama',
    'jenis',
    'lokasi',
    'harga',
    'rating',
    'status',
    'deskripsi',
    'foto',
    'foto_gallery',
    'fasilitas',
];

    protected $casts = [
    'foto_gallery' => 'array',
    'fasilitas' => 'array',
];

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'lapangan_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'lapangan_id');
    }

    public function venue()
{
    return $this->belongsTo(Venue::class);
}
    public function getFotoUrlAttribute()
{
    return $this->foto
        ? asset('uploads/lapangan/' . $this->foto)
        : 'https://via.placeholder.com/400x250?text=Lapangan';
}
}
