<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Claim extends Model
{
    protected $fillable = [
        'project_id',
        'vendor_id',
        'claim_number',
        'version',
        'status',
        'total_claim_amount',
        'total_actual_amount',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'total_claim_amount' => 'decimal:2',
            'total_actual_amount' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClaimItem::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->latest('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ClaimAttachment::class)->oldest('created_at');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ClaimComment::class)->oldest('created_at');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ClaimRevision::class)->latest('version');
    }
}
