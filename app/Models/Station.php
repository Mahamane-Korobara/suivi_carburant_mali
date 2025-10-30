<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;


class Station extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'quartier',
        'commune',
        'gerant_name',
        'phone',
        'email',
        'type',
        'status',
        'rejection_reason',
        'password',
    ];

    protected $hidden = ['password'];
}
