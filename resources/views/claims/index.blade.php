@extends('layouts.app')

@section('title', auth()->user()->role === 'vendor' ? 'Klaim Saya' : 'Semua Klaim')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ auth()->user()->role === 'vendor' ? 'Klaim Saya' : 'Semua Klaim' }}</h1>
            <p class="mt-1 text-sm text-gray-600">Daftar klaim tagihan untuk seluruh proyek.</p>
        </div>
        @if (auth()->user()->role === 'vendor')
            <a href="{{ route('claims.create') }}" class="rounded-sm bg-yellow-500 px-4 py-2 text-sm font-semibold text-gray-950 hover:bg-yellow-600">Buat klaim</a>
        @endif
    </div>

    @if (in_array(auth()->user()->role, ['admin', 'owner'], true))
        <form method="GET" action="{{ route('claims.index') }}" class="mb-5 grid gap-3 border border-gray-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-5">
            <div>
                <label for="vendor" class="mb-1 block text-xs font-semibold text-gray-600">Nama vendor</label>
                <input id="vendor" name="vendor" type="search" value="{{ $filters['vendor'] ?? '' }}" placeholder="Perusahaan atau username"
                       class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
            </div>
            <div>
                <label for="status" class="mb-1 block text-xs font-semibold text-gray-600">Status</label>
                <select id="status" name="status" class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
                    <option value="">Semua status</option>
                    @foreach (['submitted' => 'Submitted', 'reviewed' => 'Reviewed', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="date_from" class="mb-1 block text-xs font-semibold text-gray-600">Dari tanggal</label>
                <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}"
                       class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
            </div>
            <div>
                <label for="date_to" class="mb-1 block text-xs font-semibold text-gray-600">Sampai tanggal</label>
                <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}"
                       class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-sm bg-yellow-500 px-4 py-2 text-sm font-semibold text-gray-950 hover:bg-yellow-600">Filter</button>
                <a href="{{ route('claims.index') }}" class="border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Reset</a>
            </div>
        </form>
    @endif

    <div class="overflow-x-auto border border-gray-300 bg-white">
        <table class="table-auto w-full border-collapse border border-gray-300 text-left text-sm">
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th class="border-b border-gray-300 p-2">Nomor klaim</th>
                    <th class="border-b border-gray-300 p-2">Proyek</th>
                    @if (auth()->user()->role !== 'vendor')<th class="border-b border-gray-300 p-2">Vendor</th>@endif
                    <th class="border-b border-gray-300 p-2">Tanggal</th>
                    <th class="border-b border-gray-300 p-2">Nilai klaim</th>
                    <th class="border-b border-gray-300 p-2">Status</th>
                    <th class="border-b border-gray-300 p-2">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($claims as $claim)
                    <tr>
                        <td class="border-b border-gray-200 p-2 font-medium">{{ $claim->claim_number }}</td>
                        <td class="border-b border-gray-200 p-2">{{ $claim->project->name }}</td>
                        @if (auth()->user()->role !== 'vendor')
                            <td class="border-b border-gray-200 p-2">{{ $claim->vendor->company_name ?: $claim->vendor->username }}</td>
                        @endif
                        <td class="border-b border-gray-200 p-2">{{ $claim->created_at->format('d M Y') }}</td>
                        <td class="border-b border-gray-200 p-2">Rp {{ number_format((float) $claim->total_claim_amount, 0, ',', '.') }}</td>
                        <td class="border-b border-gray-200 p-2"><x-status-badge :status="$claim->status" /></td>
                        <td class="border-b border-gray-200 p-2">
                            @if (auth()->user()->role !== 'owner' || $claim->status !== 'submitted')
                                <a href="{{ route('claims.show', $claim) }}" class="font-semibold text-gray-800 underline decoration-yellow-500 underline-offset-4">Detail</a>
                            @else
                                <span class="text-gray-400">Menunggu tinjauan Admin</span>
                            @endif
                            @if (auth()->user()->role === 'admin' && $claim->status === 'submitted')
                                <a href="{{ route('claims.review', $claim) }}" class="ml-3 font-semibold text-gray-800 underline decoration-yellow-500 underline-offset-4">Tinjau</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ auth()->user()->role === 'vendor' ? 6 : 7 }}" class="p-8 text-center text-gray-500">Belum ada klaim.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($claims->hasPages())
        <nav class="mt-4 flex items-center justify-between text-sm" aria-label="Halaman klaim">
            <span class="text-gray-600">Halaman {{ $claims->currentPage() }} dari {{ $claims->lastPage() }}</span>
            <div class="flex gap-2">
                @if ($claims->previousPageUrl())
                    <a href="{{ $claims->previousPageUrl() }}" class="border border-gray-300 px-3 py-2 hover:bg-white">Sebelumnya</a>
                @endif
                @if ($claims->nextPageUrl())
                    <a href="{{ $claims->nextPageUrl() }}" class="border border-gray-300 px-3 py-2 hover:bg-white">Berikutnya</a>
                @endif
            </div>
        </nav>
    @endif
@endsection
