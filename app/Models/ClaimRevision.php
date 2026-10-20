<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimRevision extends Model
{
    public $timestamps = true;

    public const UPDATED_AT = null;

    protected $fillable = [
        'claim_id',
        'version',
        'total_amount',
        'status_previous',
        'rejection_reason',
        'data_snapshot',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'data_snapshot' => 'array',
        ];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
