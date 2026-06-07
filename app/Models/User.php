<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function attendances()
{
    return $this->hasMany(Attendance::class);
}

public function correctionRequests()
{
    return $this->hasMany(CorrectionRequest::class);
}

public function approvedCorrectionRequests()
{
    return $this->hasMany(CorrectionRequest::class, 'approved_by');
}

public function notifications()
{
    return $this->hasMany(Notification::class);
}
}
