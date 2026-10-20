<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimComment extends Model
{
    public $timestamps = true;

    public const UPDATED_AT = null;

    protected $fillable = ['claim_id', 'user_id', 'message'];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
