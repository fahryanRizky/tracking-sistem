@extends('layouts.app')

@section('title', 'Tinjau Klaim')

@section('content')
    <div class="mb-6">
        <a href="{{ route('claims.index') }}" class="text-sm text-gray-600 underline underline-offset-4">Kembali ke klaim masuk</a>
        <h1 class="mt-3 text-2xl font-bold tracking-tight text-gray-900">Realisasi lapangan</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $claim->claim_number }} · {{ $claim->project->name }} · {{ $claim->vendor->company_name ?: $claim->vendor->username }}</p>
    </div>

    <form method="POST" action="{{ route('claims.review.update', $claim) }}" enctype="multipart/form-data" class="border border-gray-200 bg-white">
        @csrf
        @method('PUT')
        <div class="overflow-x-auto">
            <table class="table-auto w-full border-collapse border border-gray-300 text-left text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="border-b border-gray-300 p-3">Item</th>
                        <th class="border-b border-gray-300 p-3">Satuan</th>
                        <th class="border-b border-gray-300 p-3">Kuantitas klaim</th>
                        <th class="border-b border-gray-300 p-3">Harga klaim</th>
                        <th class="border-b border-gray-300 p-3">Kuantitas aktual</th>
                        <th class="border-b border-gray-300 p-3">Harga aktual</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($claim->items as $index => $item)
                        <tr @class(['bg-red-100' => false])>
                            <td class="border-b border-gray-200 p-3">{{ $item->item_name }}</td>
                            <td class="border-b border-gray-200 p-3">{{ $item->unit }}</td>
                            <td class="border-b border-gray-200 p-3">{{ number_format((float) $item->claimed_quantity, 2, ',', '.') }}</td>
                            <td class="border-b border-gray-200 p-3">Rp {{ number_format((float) $item->claimed_unit_price, 0, ',', '.') }}</td>
                            <td class="border-b border-gray-200 p-3">
                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                <input name="items[{{ $index }}][actual_quantity]" type="number" min="0" step="0.01" value="{{ old("items.$index.actual_quantity") }}" required class="w-32 border-gray-300 px-2 py-1.5 text-sm focus:border-yellow-500 focus:ring-yellow-500">
                            </td>
                            <td class="border-b border-gray-200 p-3">
                                <input name="items[{{ $index }}][actual_unit_price]" type="number" min="0" step="0.01" value="{{ old("items.$index.actual_unit_price") }}" required class="w-40 border-gray-300 px-2 py-1.5 text-sm focus:border-yellow-500 focus:ring-yellow-500">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex flex-col gap-4 border-t border-gray-200 p-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="w-full sm:max-w-lg">
                <label for="attachments" class="mb-1 block text-sm font-medium text-gray-700">Foto bukti lapangan <span class="font-normal text-gray-500">(opsional)</span></label>
                <input id="attachments" name="attachments[]" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" multiple
                       class="block w-full border border-gray-300 text-sm text-gray-700 file:mr-3 file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium">
                <p class="mt-1 text-xs text-gray-500">Pilih foto JPG atau PNG, maksimal 5 MB per foto.</p>
                @error('attachments')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                @error('attachments.*')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="shrink-0 rounded-sm bg-yellow-500 px-5 py-2 text-sm font-semibold text-gray-950 hover:bg-yellow-600">Simpan realisasi</button>
        </div>
    </form>
@endsection
