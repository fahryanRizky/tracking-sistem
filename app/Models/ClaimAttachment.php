<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimAttachment extends Model
{
    public $timestamps = true;

    public const UPDATED_AT = null;

    protected $fillable = ['claim_id', 'file_path', 'uploaded_by'];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
