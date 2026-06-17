<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements JWTSubject, FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable; 
    protected $hidden = [
        'password',
    ];
    protected $guarded = [];

    public function cartItems(){
        return $this->hasMany(Cart::class)->where('is_selected',1);
    }

    public function allCartItems(){
        return $this->hasMany(Cart::class);
    }

    public function orders(){
        return $this->hasMany(Order::class)->where('status','completed');
    }

    public function address(){
        return $this->hasOne(UserAddress::class)->where('is_default',1);
    }

    public function savedCards(){
        return $this->hasMany(SavedCard::class)->orderBy('is_default','desc');
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

    public function canAccessPanel(Panel $panel): bool
    {
        
        return $this->role === 'Admin';

    }
}
