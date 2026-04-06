<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_number',
        'title',
        'country_id',
        'organization_id',
        'funder_organization_id',
        'description',
        'start_date',
        'expected_end_date',
        'actual_end_date',
        'approved_amount',
        'state',
        'status',
        'documentation_status',
        'financial_status',
        'readiness_notes',
        'execution_notes',
        'documentation_notes',
        'final_report',
        'photo_album_url',
        'video_album_url',
        'created_by',
        'updated_by',
        'archived_at',
        'is_archived',
    ];

    protected $casts = [
        'start_date' => 'date',
        'expected_end_date' => 'date',
        'actual_end_date' => 'date',
        'approved_amount' => 'decimal:2',
        'archived_at' => 'datetime',
        'is_archived' => 'boolean',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function funderOrganization()
    {
        return $this->belongsTo(Organization::class, 'funder_organization_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function responsibility()
    {
        return $this->hasOne(ProjectResponsibility::class);
    }

    public function stateHistories()
    {
        return $this->hasMany(ProjectStateHistory::class);
    }

    public function financialTransactions()
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
