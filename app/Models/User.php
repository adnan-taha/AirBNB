<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'password',
        'role',
        'photo',
        'id_photo_front',
        'id_photo_back',
        'birth_date',
        'is_approved',
    ];

    protected $hidden = ['password'];

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }


}
