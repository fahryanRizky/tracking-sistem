<?php

namespace App\Notifications;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ClaimReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Claim $claim) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'claim_reviewed',
            'message' => 'Klaim '.$this->claim->claim_number.' telah ditinjau dan menunggu keputusan Owner.',
            'url' => route('claims.show', $this->claim, false),
            'claim_id' => $this->claim->id,
        ];
    }
}
