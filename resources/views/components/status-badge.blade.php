@props(['status'])

@php
    $labels = [
        'pending' => 'Menunggu',
        'submitted' => 'Diajukan',
        'reviewed' => 'Ditinjau',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'active' => 'Aktif',
    ];
    $colors = [
        'pending' => 'bg-yellow-500',
        'submitted' => 'bg-yellow-500',
        'reviewed' => 'bg-blue-600',
        'approved' => 'bg-green-600',
        'active' => 'bg-green-600',
        'rejected' => 'bg-red-600',
    ];
@endphp
<span {{ $attributes->class(['inline-flex rounded-full px-2.5 py-1 text-xs font-bold text-white', $colors[$status] ?? 'bg-gray-600']) }}>
    {{ $labels[$status] ?? ucfirst($status) }}
</span>
