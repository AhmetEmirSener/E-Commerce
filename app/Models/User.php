<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;


class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable; 
    protected $hidden = [
        'password',
    ];
    protected $guarded = [];

    public function cartItems(){
        return $this->hasMany(Cart::class)->where('is_selected',1);
    }

    public function address(){
        return $this->hasOne(UserAddress::class)->where('is_default',1);
    }

    public function savedCarts(){
        return $this->hasMany(SavedCard::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'email' => $this->email,
            'role' => $this->role,  
        ];
    }
}
