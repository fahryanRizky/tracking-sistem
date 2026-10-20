@extends('layouts.app')

@section('title', 'Verifikasi Vendor')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Verifikasi vendor</h1>
        <p class="mt-1 text-sm text-gray-600">Tinjau pendaftaran vendor yang masih menunggu persetujuan.</p>
    </div>

    <div class="overflow-x-auto border border-gray-300 bg-white">
        <table class="table-auto w-full border-collapse border border-gray-300 text-left text-sm">
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th class="border-b border-gray-300 p-3">Perusahaan</th>
                    <th class="border-b border-gray-300 p-3">Username</th>
                    <th class="border-b border-gray-300 p-3">Tanggal daftar</th>
                    <th class="border-b border-gray-300 p-3">Status</th>
                    <th class="border-b border-gray-300 p-3">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vendors as $vendor)
                    <tr>
                        <td class="border-b border-gray-200 p-3">{{ $vendor->company_name }}</td>
                        <td class="border-b border-gray-200 p-3">{{ $vendor->username }}</td>
                        <td class="border-b border-gray-200 p-3">{{ $vendor->created_at->format('d M Y') }}</td>
                        <td class="border-b border-gray-200 p-3"><x-status-badge :status="$vendor->status" /></td>
                        <td class="border-b border-gray-200 p-3">
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('vendors.approve', $vendor) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="border border-green-700 px-3 py-1.5 text-xs font-semibold text-green-800 hover:bg-green-50">Setujui</button>
                                </form>
                                <form method="POST" action="{{ route('vendors.reject', $vendor) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="border border-red-700 px-3 py-1.5 text-xs font-semibold text-red-800 hover:bg-red-50">Tolak</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center text-gray-500">Tidak ada pendaftaran vendor yang menunggu.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($vendors->hasPages())
        <nav class="mt-4 flex items-center justify-between text-sm" aria-label="Halaman pendaftaran vendor">
            <span class="text-gray-600">Halaman {{ $vendors->currentPage() }} dari {{ $vendors->lastPage() }}</span>
            <div class="flex gap-2">
                @if ($vendors->previousPageUrl())
                    <a href="{{ $vendors->previousPageUrl() }}" class="border border-gray-300 px-3 py-2 hover:bg-white">Sebelumnya</a>
                @endif
                @if ($vendors->nextPageUrl())
                    <a href="{{ $vendors->nextPageUrl() }}" class="border border-gray-300 px-3 py-2 hover:bg-white">Berikutnya</a>
                @endif
            </div>
        </nav>
    @endif
@endsection
