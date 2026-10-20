<?php

namespace App\Mail;

use App\Models\Claim;
use Illuminate\Contracts\Queue\ShouldQueue;

class ClaimReviewedMail extends ClaimStatusMail implements ShouldQueue
{
    public function __construct(Claim $claim)
    {
        parent::__construct($claim, 'Selesai ditinjau');
    }
}
