@extends('layouts.app')

@section('title', 'Revisi Klaim')

@section('content')
    <div class="mb-6">
        <a href="{{ route('claims.show', $claim) }}" class="text-sm text-gray-600 underline underline-offset-4">Kembali ke detail klaim</a>
        <h1 class="mt-3 text-2xl font-bold tracking-tight text-gray-900">Revisi klaim {{ $claim->claim_number }}</h1>
        <p class="mt-1 text-sm text-gray-600">Versi saat ini: {{ $claim->version }}. Perbarui rincian tagihan lalu kirim ulang untuk ditinjau.</p>
        <div class="mt-4 border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">Alasan penolakan sebelumnya</p>
            <p class="mt-1 whitespace-pre-line">{{ $claim->rejection_reason }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('claims.revise.store', $claim) }}"
          x-data="{ items: @js(old('items', $claim->items->map(fn ($item) => ['item_name' => $item->item_name, 'unit' => $item->unit, 'claimed_quantity' => (float) $item->claimed_quantity, 'claimed_unit_price' => (float) $item->claimed_unit_price])->values()->all())) }"
          class="space-y-6">
        @csrf
        @method('PUT')
        <section class="border border-gray-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                <div>
                    <h2 class="font-semibold">Rincian klaim revisi</h2>
                    <p class="mt-1 text-sm text-gray-500">Item realisasi akan diisi kembali oleh Admin setelah pengajuan.</p>
                </div>
                <button type="button" @click="items.push({ item_name: '', unit: '', claimed_quantity: 1, claimed_unit_price: 0 })" class="border border-gray-300 px-3 py-2 text-sm font-medium hover:bg-gray-50">Tambah item</button>
            </div>
            <div class="space-y-4 p-5">
                @error('items')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                <template x-for="(item, index) in items" :key="index">
                    <div class="grid gap-3 border border-gray-200 p-4 md:grid-cols-12">
                        <div class="md:col-span-4">
                            <label :for="'revise-name-' + index" class="mb-1 block text-xs font-semibold text-gray-600">Nama item</label>
                            <input :id="'revise-name-' + index" :name="'items[' + index + '][item_name]'" x-model="item.item_name" type="text" required maxlength="255" class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
                        </div>
                        <div class="md:col-span-2">
                            <label :for="'revise-unit-' + index" class="mb-1 block text-xs font-semibold text-gray-600">Satuan</label>
                            <input :id="'revise-unit-' + index" :name="'items[' + index + '][unit]'" x-model="item.unit" type="text" required maxlength="50" class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
                        </div>
                        <div class="md:col-span-2">
                            <label :for="'revise-quantity-' + index" class="mb-1 block text-xs font-semibold text-gray-600">Kuantitas</label>
                            <input :id="'revise-quantity-' + index" :name="'items[' + index + '][claimed_quantity]'" x-model="item.claimed_quantity" type="number" min="0" max="99999999.99" step="0.01" required class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
                        </div>
                        <div class="md:col-span-3">
                            <label :for="'revise-price-' + index" class="mb-1 block text-xs font-semibold text-gray-600">Harga satuan (Rp)</label>
                            <input :id="'revise-price-' + index" :name="'items[' + index + '][claimed_unit_price]'" x-model="item.claimed_unit_price" type="number" min="0" max="9999999999999" step="0.01" required class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
                        </div>
                        <div class="flex items-end md:col-span-1">
                            <button type="button" @click="if (items.length > 1) items.splice(index, 1)" :disabled="items.length === 1" class="w-full border border-red-300 px-2 py-2 text-sm font-medium text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40">Hapus</button>
                        </div>
                    </div>
                </template>
            </div>
        </section>
        <div class="flex justify-end gap-3">
            <a href="{{ route('claims.show', $claim) }}" class="border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
            <button type="submit" class="rounded-sm bg-yellow-500 px-5 py-2 text-sm font-semibold text-gray-950 hover:bg-yellow-600">Kirim revisi</button>
        </div>
    </form>
@endsection
