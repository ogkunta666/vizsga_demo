<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'barber_id',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'start_at',
        'duration_min',
        'note',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
        ];
    }

    public function barber()
    {
        return $this->belongsTo(Barber::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
