<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'department_id',
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(\App\Models\ActivityLog::class, 'causer_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function canManageUsers(): bool
    {
        return in_array($this->role, ['super_admin'], true);
    }

    public function canViewReports(): bool
    {
        return in_array($this->role, ['super_admin', 'project_manager', 'finance_officer', 'viewer'], true);
    }
}
