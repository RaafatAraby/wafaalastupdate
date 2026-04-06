<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectResponsibility extends Model
{
    protected $fillable = [
        'project_id',
        'readiness_user_id',
        'execution_user_id',
        'documentation_user_id',
        'finance_user_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function readinessUser()
    {
        return $this->belongsTo(User::class, 'readiness_user_id');
    }

    public function executionUser()
    {
        return $this->belongsTo(User::class, 'execution_user_id');
    }

    public function documentationUser()
    {
        return $this->belongsTo(User::class, 'documentation_user_id');
    }

    public function financeUser()
    {
        return $this->belongsTo(User::class, 'finance_user_id');
    }
}
