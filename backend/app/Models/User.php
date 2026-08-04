<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected string $guard_name = 'web';

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'is_active',
        'avatar', 'employee_id', 'gender', 'date_of_birth', 'joining_date',
        'address', 'emergency_contact',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
        'date_of_birth'     => 'date',
        'joining_date'      => 'date',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}
