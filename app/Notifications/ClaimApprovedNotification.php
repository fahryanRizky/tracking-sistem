<?php

namespace App\Notifications;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ClaimApprovedNotification extends Notification
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
            'type' => 'claim_approved',
            'message' => 'Klaim '.$this->claim->claim_number.' telah disetujui Owner.',
            'url' => route('claims.show', $this->claim, false),
            'claim_id' => $this->claim->id,
        ];
    }
}
