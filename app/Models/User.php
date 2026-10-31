<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $attributes = [
        'session_version' => 1,
        'must_change_password' => false,
    ];

    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_active', 'last_login_at',
        'session_version', 'must_change_password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'session_version' => 'integer',
            'password' => 'hashed',
        ];
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}
