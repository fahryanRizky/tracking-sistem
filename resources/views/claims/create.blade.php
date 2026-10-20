@extends('layouts.app')

@section('title', 'Buat Klaim')

@section('content')
    <div class="mb-6">
        <a href="{{ route('claims.index') }}" class="text-sm text-gray-600 underline underline-offset-4">Kembali ke klaim</a>
        <h1 class="mt-3 text-2xl font-bold tracking-tight text-gray-900">Buat klaim baru</h1>
        <p class="mt-1 text-sm text-gray-600">Masukkan rincian tagihan yang akan diverifikasi.</p>
    </div>

        <form method="POST" action="{{ route('claims.store') }}" x-data="{ items: [{ item_name: '', unit: '', claimed_quantity: 1, claimed_unit_price: 0 }], creatingProject: @js((bool) old('create_project', false)) }" class="space-y-6">
            @csrf
            <section class="border border-gray-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <label for="project_id" class="block text-sm font-medium text-gray-700">Proyek</label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" x-model="creatingProject" class="border-gray-300 text-yellow-600 focus:ring-yellow-500">
                        Buat proyek baru
                    </label>
                </div>
                <input type="hidden" name="create_project" :value="creatingProject ? 1 : 0">
                <div x-show="!creatingProject" x-cloak class="mt-2">
                    <select id="project_id" name="project_id" :required="!creatingProject" :disabled="creatingProject" class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
                        <option value="">Pilih proyek</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                    @if ($projects->isEmpty())
                        <p class="mt-2 text-sm text-gray-500">Belum ada proyek. Pilih “Buat proyek baru” untuk menambahkan proyek.</p>
                    @endif
                </div>
                <div x-show="creatingProject" x-cloak class="mt-4 grid gap-4">
                    <div>
                        <label for="new_project_name" class="mb-1 block text-sm font-medium text-gray-700">Nama proyek baru</label>
                        <input id="new_project_name" name="new_project_name" type="text" maxlength="255" :required="creatingProject" :disabled="!creatingProject" value="{{ old('new_project_name') }}" class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
                        @error('new_project_name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="new_project_description" class="mb-1 block text-sm font-medium text-gray-700">Deskripsi proyek <span class="font-normal text-gray-500">(opsional)</span></label>
                        <textarea id="new_project_description" name="new_project_description" rows="3" :disabled="!creatingProject" class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">{{ old('new_project_description') }}</textarea>
                        @error('new_project_description')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <p class="text-xs text-gray-500">Proyek baru akan tersedia bagi semua vendor.</p>
                </div>
                @error('project_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </section>

            <section class="border border-gray-200 bg-white">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                    <div>
                        <h2 class="font-semibold">Rincian item</h2>
                        <p class="mt-1 text-sm text-gray-500">Tambahkan satu atau lebih item tagihan.</p>
                    </div>
                    <button type="button" @click="items.push({ item_name: '', unit: '', claimed_quantity: 1, claimed_unit_price: 0 })" class="border border-gray-300 px-3 py-2 text-sm font-medium hover:bg-gray-50">Tambah item</button>
                </div>
                <div class="space-y-4 p-5">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="grid gap-3 border border-gray-200 p-4 md:grid-cols-12">
                            <div class="md:col-span-4">
                                <label :for="'item-name-' + index" class="mb-1 block text-xs font-semibold text-gray-600">Nama item</label>
                                <input :id="'item-name-' + index" :name="'items[' + index + '][item_name]'" x-model="item.item_name" type="text" required maxlength="255" class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
                            </div>
                            <div class="md:col-span-2">
                                <label :for="'unit-' + index" class="mb-1 block text-xs font-semibold text-gray-600">Satuan</label>
                                <input :id="'unit-' + index" :name="'items[' + index + '][unit]'" x-model="item.unit" type="text" required maxlength="50" placeholder="Unit" class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
                            </div>
                            <div class="md:col-span-2">
                                <label :for="'quantity-' + index" class="mb-1 block text-xs font-semibold text-gray-600">Kuantitas</label>
                                <input :id="'quantity-' + index" :name="'items[' + index + '][claimed_quantity]'" x-model="item.claimed_quantity" type="number" min="0" step="0.01" required class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
                            </div>
                            <div class="md:col-span-3">
                                <label :for="'price-' + index" class="mb-1 block text-xs font-semibold text-gray-600">Harga satuan (Rp)</label>
                                <input :id="'price-' + index" :name="'items[' + index + '][claimed_unit_price]'" x-model="item.claimed_unit_price" type="number" min="0" step="0.01" required class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
                            </div>
                            <div class="flex items-end md:col-span-1">
                                <button type="button" @click="if (items.length > 1) items.splice(index, 1)" :disabled="items.length === 1" class="w-full border border-red-300 px-2 py-2 text-sm font-medium text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40">Hapus</button>
                            </div>
                        </div>
                    </template>
                </div>
            </section>
            <div class="flex justify-end gap-3">
                <a href="{{ route('claims.index') }}" class="border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
                <button type="submit" class="rounded-sm bg-yellow-500 px-5 py-2 text-sm font-semibold text-gray-950 hover:bg-yellow-600">Ajukan klaim</button>
            </div>
        </form>
@endsection
