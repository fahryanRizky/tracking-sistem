<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimItem extends Model
{
    protected $fillable = [
        'item_name',
        'unit',
        'claimed_quantity',
        'claimed_unit_price',
        'actual_quantity',
        'actual_unit_price',
    ];

    protected function casts(): array
    {
        return [
            'claimed_quantity' => 'decimal:2',
            'claimed_unit_price' => 'decimal:2',
            'actual_quantity' => 'decimal:2',
            'actual_unit_price' => 'decimal:2',
        ];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }
}
