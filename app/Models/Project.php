<?php

namespace App\Models;

use App\Concerns\ScopesByCountry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory, ScopesByCountry;

    /**
     * Transient (non-persisted) note for the next state-history row.
     * Set by workflow actions (approveReadiness, updateExecution, rollback…)
     * just before saving so ProjectObserver can record the user-supplied
     * note instead of the generic fallback message.
     */
    public ?string $stateChangeNote = null;

    protected $fillable = [
        'project_number',
        'title',
        'country_id',
        'organization_id',
        'funder_organization_id',
        'description',
        'beneficiaries_count',
        'start_date',
        'expected_end_date',
        'actual_end_date',
        'approved_amount',
        'state',
        'status',
        'documentation_status',
        'documentation_type',
        'financial_status',
        'readiness_notes',
        'execution_notes',
        'documentation_notes',
        'final_report',
        'final_report_approved',
        'final_report_approved_by',
        'final_report_approved_at',
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
        'final_report_approved' => 'boolean',
        'final_report_approved_at' => 'datetime',
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

    public function payments()
    {
        return $this->hasMany(ProjectPayment::class);
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

    public function finalReportApprover()
    {
        return $this->belongsTo(User::class, 'final_report_approved_by');
    }

    /**
     * The project balance (incoming − outgoing). Closing requires this == 0
     * AND only counts approved transactions.
     */
    public function balance(): float
    {
        return (float) $this->financialTransactions()
            ->where('approval_status', 'approved')
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type='incoming' THEN amount ELSE -amount END), 0) as balance")
            ->value('balance');
    }
}
