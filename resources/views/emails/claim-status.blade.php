<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Informasi klaim {{ $claim->claim_number }}</title>
</head>
<body style="margin:0;background:#f9fafb;color:#111827;font-family:Arial,sans-serif;line-height:1.6">
    <table role="presentation" style="width:100%;border-collapse:collapse">
        <tr>
            <td style="padding:24px">
                <table role="presentation" style="width:100%;max-width:640px;margin:0 auto;border:1px solid #e5e7eb;background:#ffffff;border-collapse:collapse">
                    <tr>
                        <td style="padding:24px;border-bottom:1px solid #e5e7eb">
                            <p style="margin:0;font-size:12px;color:#6b7280">TRACKING SISTEM PT JAYA</p>
                            <h1 style="margin:8px 0 0;font-size:22px">Pembaruan status klaim</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px">
                            <p>Status klaim proyek telah diperbarui menjadi <strong>{{ $statusLabel }}</strong>.</p>
                            <table role="presentation" style="width:100%;border-collapse:collapse">
                                <tr><td style="padding:8px;border:1px solid #e5e7eb">Nomor klaim</td><td style="padding:8px;border:1px solid #e5e7eb">{{ $claim->claim_number }}</td></tr>
                                <tr><td style="padding:8px;border:1px solid #e5e7eb">Vendor</td><td style="padding:8px;border:1px solid #e5e7eb">{{ $claim->vendor->company_name ?: $claim->vendor->username }}</td></tr>
                                <tr><td style="padding:8px;border:1px solid #e5e7eb">Proyek</td><td style="padding:8px;border:1px solid #e5e7eb">{{ $claim->project->name }}</td></tr>
                                <tr><td style="padding:8px;border:1px solid #e5e7eb">Status</td><td style="padding:8px;border:1px solid #e5e7eb">{{ $statusLabel }}</td></tr>
                                <tr><td style="padding:8px;border:1px solid #e5e7eb">Total tagihan</td><td style="padding:8px;border:1px solid #e5e7eb">Rp {{ number_format((float) $claim->total_claim_amount, 0, ',', '.') }}</td></tr>
                                <tr><td style="padding:8px;border:1px solid #e5e7eb">Total realisasi</td><td style="padding:8px;border:1px solid #e5e7eb">Rp {{ number_format((float) $claim->total_actual_amount, 0, ',', '.') }}</td></tr>
                                <tr><td style="padding:8px;border:1px solid #e5e7eb">Selisih (realisasi - tagihan)</td><td style="padding:8px;border:1px solid #e5e7eb">Rp {{ number_format($difference, 0, ',', '.') }}</td></tr>
                            </table>
                            @if ($claim->rejection_reason)
                                <p><strong>Alasan penolakan:</strong><br>{{ $claim->rejection_reason }}</p>
                            @endif
                            <p style="margin:24px 0">
                                <a href="{{ $claimUrl }}" style="display:inline-block;padding:10px 16px;background:#eab308;color:#111827;text-decoration:none;font-weight:bold">Buka klaim</a>
                            </p>
                            <p style="font-size:12px;color:#6b7280">Email ini dikirim otomatis oleh Tracking Sistem PT Jaya.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
