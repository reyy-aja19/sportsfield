<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'open_match_id',
        'user_id',
        'message',
    ];

    // Hubungan balik: Pesan ini milik seorang User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Hubungan balik: Pesan ini milik sebuah OpenMatch
    public function openMatch()
    {
        return $this->belongsTo(OpenMatch::class);
    }
}