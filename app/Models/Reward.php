<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
    'title',
    'points_required',
    'stock',
    'description',
    'image',
    'status',
    'expired_at',
];

    public function redemptions()
    {
        return $this->hasMany(Redemption::class);
    }

    protected function casts(): array
{
    return [
        'expired_at' => 'date',
    ];
}
}
