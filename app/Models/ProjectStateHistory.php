<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectStateHistory extends Model
{
    protected $fillable = [
        'project_id',
        'from_state',
        'to_state',
        'changed_by',
        'notes',
        'created_at',
    ];

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
