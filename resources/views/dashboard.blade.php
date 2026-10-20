@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-600">Ringkasan aktivitas klaim proyek Anda.</p>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan">
        @if (auth()->user()->role === 'vendor')
            <article class="border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">Total klaim</p>
                <p class="mt-2 text-3xl font-bold">{{ $summary['total'] }}</p>
            </article>
            <article class="border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">Klaim disetujui</p>
                <p class="mt-2 text-3xl font-bold text-green-700">{{ $summary['approved'] }}</p>
            </article>
            <article class="border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">Sedang diproses</p>
                <p class="mt-2 text-3xl font-bold text-yellow-700">{{ $summary['pending'] }}</p>
            </article>
        @elseif (auth()->user()->role === 'admin')
            <article class="border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">Semua klaim</p>
                <p class="mt-2 text-3xl font-bold">{{ $summary['total'] }}</p>
            </article>
            <article class="border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">Menunggu realisasi</p>
                <p class="mt-2 text-3xl font-bold text-yellow-700">{{ $summary['pending'] }}</p>
            </article>
            <article class="border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">Pendaftaran vendor</p>
                <p class="mt-2 text-3xl font-bold">{{ $summary['vendors'] }}</p>
            </article>
        @else
            <article class="border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">Total klaim</p>
                <p class="mt-2 text-3xl font-bold">{{ $summary['total'] }}</p>
            </article>
            <article class="border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">Klaim disetujui</p>
                <p class="mt-2 text-3xl font-bold text-green-700">{{ $summary['approved'] }}</p>
            </article>
            <article class="border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">Menunggu keputusan</p>
                <p class="mt-2 text-3xl font-bold text-yellow-700">{{ $summary['awaiting_decision'] }}</p>
            </article>
            <article class="border border-gray-200 bg-white p-5">
                <p class="text-sm text-gray-500">Nilai aktual disetujui</p>
                <p class="mt-2 text-xl font-bold">Rp {{ number_format((float) $summary['approved_amount'], 0, ',', '.') }}</p>
            </article>
        @endif
    </section>

    <section class="mt-8 border border-gray-200 bg-white">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <h2 class="font-semibold text-gray-900">Klaim terbaru</h2>
            <a href="{{ route('claims.index') }}" class="text-sm font-semibold text-gray-800 underline decoration-yellow-500 underline-offset-4">Lihat semua</a>
        </div>
        @if ($recentClaims->isEmpty())
            <p class="px-5 py-8 text-sm text-gray-500">Belum ada klaim yang tercatat.</p>
        @else
            <div class="overflow-x-auto">
                <table class="table-auto w-full border-collapse border border-gray-300 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="border-b border-gray-300 p-2">Nomor klaim</th>
                            <th class="border-b border-gray-300 p-2">Proyek</th>
                            @if (auth()->user()->role !== 'vendor')<th class="border-b border-gray-300 p-2">Vendor</th>@endif
                            <th class="border-b border-gray-300 p-2">Status</th>
                            <th class="border-b border-gray-300 p-2">Nilai klaim</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentClaims as $claim)
                            <tr>
                                <td class="border-b border-gray-200 p-2 font-medium">
                                    @if (auth()->user()->role !== 'owner' || $claim->status !== 'submitted')
                                        <a href="{{ route('claims.show', $claim) }}" class="underline decoration-gray-300 underline-offset-4">{{ $claim->claim_number }}</a>
                                    @else
                                        {{ $claim->claim_number }}
                                    @endif
                                </td>
                                <td class="border-b border-gray-200 p-2">{{ $claim->project->name }}</td>
                                @if (auth()->user()->role !== 'vendor')<td class="border-b border-gray-200 p-2">{{ $claim->vendor->company_name ?: $claim->vendor->username }}</td>@endif
                                <td class="border-b border-gray-200 p-2"><x-status-badge :status="$claim->status" /></td>
                                <td class="border-b border-gray-200 p-2">Rp {{ number_format((float) $claim->total_claim_amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
