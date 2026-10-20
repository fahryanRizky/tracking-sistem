<?php

namespace App\Mail;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

abstract class ClaimStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Claim $claim,
        public string $statusLabel,
    ) {
        $this->claim->loadMissing(['vendor', 'project']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Klaim '.$this->claim->claim_number.' - '.$this->statusLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.claim-status',
            with: [
                'claim' => $this->claim,
                'statusLabel' => $this->statusLabel,
                'difference' => (float) $this->claim->total_actual_amount - (float) $this->claim->total_claim_amount,
                'claimUrl' => route('claims.show', $this->claim),
            ],
        );
    }
}
