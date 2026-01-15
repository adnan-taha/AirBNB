<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'apartment_id',
        'tenant_id',
        'start_date',
        'end_date',
        'status',
        'total_price'
    ];

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}
