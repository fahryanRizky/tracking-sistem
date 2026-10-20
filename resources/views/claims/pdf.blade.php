<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Klaim {{ $claim->claim_number }}</title>
    <style>
        @page { margin: 34px 42px; }
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.45; }
        .header { border-bottom: 2px solid #111827; margin-bottom: 18px; padding-bottom: 12px; }
        .company { font-size: 17px; font-weight: bold; letter-spacing: .5px; }
        .muted { color: #4b5563; }
        h1 { font-size: 16px; margin: 18px 0 4px; text-align: center; }
        .subtitle { margin-bottom: 18px; text-align: center; }
        .meta, .items { border-collapse: collapse; width: 100%; }
        .meta td { border: 1px solid #d1d5db; padding: 7px; }
        .items { margin-top: 18px; }
        .items th { background: #f3f4f6; text-align: left; }
        .items th, .items td { border: 1px solid #d1d5db; padding: 7px; }
        .number { text-align: right; }
        .totals { margin: 16px 0 35px auto; width: 48%; }
        .totals td { padding: 5px; }
        .signature { margin-top: 46px; width: 100%; }
        .signature td { padding: 4px; text-align: center; vertical-align: top; width: 33.33%; }
        .signature-space { height: 62px; }
        .signature-line { border-top: 1px solid #374151; margin: 0 auto; padding-top: 5px; width: 80%; }
    </style>
</head>
<body>
    <header class="header">
        <div class="company">PT JAYA</div>
        <div class="muted">Dokumen verifikasi klaim proyek</div>
    </header>

    <h1>LAPORAN VERIFIKASI KLAIM</h1>
    <div class="subtitle">Nomor {{ $claim->claim_number }} &mdash; Versi {{ $claim->version }}</div>

    <table class="meta">
        <tr><td><strong>Proyek</strong></td><td>{{ $claim->project->name }}</td></tr>
        <tr><td><strong>Vendor</strong></td><td>{{ $claim->vendor->company_name ?: $claim->vendor->username }}</td></tr>
        <tr><td><strong>Tanggal pengajuan</strong></td><td>{{ $claim->created_at->format('d-m-Y') }}</td></tr>
        <tr><td><strong>Status</strong></td><td>{{ ucfirst($claim->status) }}</td></tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>No.</th>
                <th>Nama item</th>
                <th>Satuan</th>
                <th class="number">Qty klaim</th>
                <th class="number">Harga klaim</th>
                <th class="number">Qty aktual</th>
                <th class="number">Harga aktual</th>
                <th class="number">Jumlah klaim</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($claim->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td>{{ $item->unit }}</td>
                    <td class="number">{{ number_format((float) $item->claimed_quantity, 2, ',', '.') }}</td>
                    <td class="number">Rp {{ number_format((float) $item->claimed_unit_price, 0, ',', '.') }}</td>
                    <td class="number">{{ $item->actual_quantity === null ? '-' : number_format((float) $item->actual_quantity, 2, ',', '.') }}</td>
                    <td class="number">{{ $item->actual_unit_price === null ? '-' : 'Rp '.number_format((float) $item->actual_unit_price, 0, ',', '.') }}</td>
                    <td class="number">Rp {{ number_format((float) $item->claimed_quantity * (float) $item->claimed_unit_price, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Total tagihan</td><td class="number"><strong>Rp {{ number_format((float) $claim->total_claim_amount, 0, ',', '.') }}</strong></td></tr>
        <tr><td>Total realisasi</td><td class="number"><strong>Rp {{ number_format((float) $claim->total_actual_amount, 0, ',', '.') }}</strong></td></tr>
        <tr><td>Selisih</td><td class="number"><strong>Rp {{ number_format((float) $claim->total_actual_amount - (float) $claim->total_claim_amount, 0, ',', '.') }}</strong></td></tr>
    </table>

    @if ($claim->rejection_reason)
        <p><strong>Alasan penolakan:</strong> {{ $claim->rejection_reason }}</p>
    @endif

    <table class="signature">
        <tr>
            <td>Vendor</td>
            <td>Admin Verifikator</td>
            <td>Owner PT Jaya</td>
        </tr>
        <tr><td class="signature-space"></td><td class="signature-space"></td><td class="signature-space"></td></tr>
        <tr>
            <td><div class="signature-line">{{ $claim->vendor->company_name ?: $claim->vendor->username }}</div></td>
            <td><div class="signature-line">Nama dan tanda tangan</div></td>
            <td><div class="signature-line">Nama dan tanda tangan</div></td>
        </tr>
    </table>
</body>
</html>
