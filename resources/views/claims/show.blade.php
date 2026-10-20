@extends('layouts.app')

@section('title', 'Detail Klaim')

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('claims.index') }}" class="text-sm text-gray-600 underline underline-offset-4">Kembali ke daftar klaim</a>
            <h1 class="mt-3 text-2xl font-bold tracking-tight text-gray-900">{{ $claim->claim_number }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $claim->project->name }} · {{ $claim->created_at->format('d M Y') }} · Versi {{ $claim->version }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <x-status-badge :status="$claim->status" />
            <a href="{{ route('claims.pdf', $claim) }}" class="border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-white">Cetak PDF</a>
            @if (auth()->user()->role === 'vendor' && $claim->vendor_id === auth()->id() && $claim->status === 'rejected')
                <a href="{{ route('claims.revise', $claim) }}" class="rounded-sm bg-yellow-500 px-3 py-2 text-sm font-semibold text-gray-950 hover:bg-yellow-600">Revisi klaim</a>
            @endif
        </div>
    </div>

    <div x-data="{ activeTab: 'current' }">
        <div class="mb-4 flex gap-1 border-b border-gray-200" role="tablist" aria-label="Informasi klaim">
            <button type="button" role="tab" :aria-selected="activeTab === 'current'" @click="activeTab = 'current'"
                    :class="activeTab === 'current' ? 'border-yellow-500 text-gray-900' : 'border-transparent text-gray-500'"
                    class="border-b-2 px-4 py-2 text-sm font-semibold">Detail Klaim</button>
            <button type="button" role="tab" :aria-selected="activeTab === 'revisions'" @click="activeTab = 'revisions'"
                    :class="activeTab === 'revisions' ? 'border-yellow-500 text-gray-900' : 'border-transparent text-gray-500'"
                    class="border-b-2 px-4 py-2 text-sm font-semibold">Riwayat Revisi ({{ $claim->revisions->count() }})</button>
        </div>

        <div x-show="activeTab === 'current'" role="tabpanel">
    <section class="mb-6 grid gap-4 sm:grid-cols-2">
        <article class="border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-500">Vendor</p>
            <p class="mt-1 font-semibold">{{ $claim->vendor->company_name ?: $claim->vendor->username }}</p>
        </article>
        <article class="border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-500">Nilai pengajuan</p>
            <p class="mt-1 text-xl font-bold">Rp {{ number_format((float) $claim->total_claim_amount, 0, ',', '.') }}</p>
        </article>
        <article class="border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-500">Nilai realisasi</p>
            <p class="mt-1 text-xl font-bold">Rp {{ number_format((float) $claim->total_actual_amount, 0, ',', '.') }}</p>
        </article>
        <article class="border border-gray-200 bg-white p-5">
            <p class="text-sm text-gray-500">Deskripsi proyek</p>
            <p class="mt-1 text-sm text-gray-800">{{ $claim->project->description ?: 'Tidak ada deskripsi proyek.' }}</p>
        </article>
    </section>

    @if ($claim->status === 'rejected')
        <section class="mb-6 border border-red-200 bg-red-50 p-5">
            <h2 class="font-semibold text-red-900">Alasan penolakan</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-red-800">{{ $claim->rejection_reason }}</p>
        </section>
    @endif

    <section class="border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-5 py-4"><h2 class="font-semibold">Rincian item</h2></div>
        <div class="overflow-x-auto">
            <table class="table-auto w-full border-collapse border border-gray-300 text-left text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="border-b border-gray-300 p-3">Item</th>
                        <th class="border-b border-gray-300 p-3">Satuan</th>
                        <th class="border-b border-gray-300 p-3">Qty klaim</th>
                        <th class="border-b border-gray-300 p-3">Harga klaim</th>
                        <th class="border-b border-gray-300 p-3">Qty aktual</th>
                        <th class="border-b border-gray-300 p-3">Harga aktual</th>
                        <th class="border-b border-gray-300 p-3">Nilai klaim</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($claim->items as $item)
                        <tr @class(['bg-red-100' => $item->actual_quantity !== null && (float) $item->actual_quantity < (float) $item->claimed_quantity])>
                            <td class="border-b border-gray-200 p-3">{{ $item->item_name }}</td>
                            <td class="border-b border-gray-200 p-3">{{ $item->unit }}</td>
                            <td class="border-b border-gray-200 p-3">{{ number_format((float) $item->claimed_quantity, 2, ',', '.') }}</td>
                            <td class="border-b border-gray-200 p-3">Rp {{ number_format((float) $item->claimed_unit_price, 0, ',', '.') }}</td>
                            <td class="border-b border-gray-200 p-3">{{ $item->actual_quantity === null ? 'Belum ditinjau' : number_format((float) $item->actual_quantity, 2, ',', '.') }}</td>
                            <td class="border-b border-gray-200 p-3">{{ $item->actual_unit_price === null ? 'Belum ditinjau' : 'Rp '.number_format((float) $item->actual_unit_price, 0, ',', '.') }}</td>
                            <td class="border-b border-gray-200 p-3">Rp {{ number_format((float) $item->claimed_quantity * (float) $item->claimed_unit_price, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
        </div>

        <div x-show="activeTab === 'revisions'" role="tabpanel" x-cloak class="border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold">Riwayat revisi</h2>
                <p class="mt-1 text-sm text-gray-500">Snapshot versi klaim saat Owner menolak pengajuan.</p>
            </div>
            @forelse ($claim->revisions as $revision)
                <article class="border-b border-gray-200 p-5 last:border-b-0">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-semibold">Versi {{ $revision->version }}</h3>
                        <span class="text-sm text-gray-500">{{ $revision->created_at->locale('id')->translatedFormat('d M Y, H:i') }} · {{ $revision->creator->username }}</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-700">Status sebelum ditolak: {{ ucfirst($revision->status_previous) }}</p>
                    <p class="mt-1 text-sm text-red-800">Alasan penolakan: {{ $revision->rejection_reason }}</p>
                    <p class="mt-1 text-sm font-semibold">Total tagihan: Rp {{ number_format((float) $revision->total_amount, 0, ',', '.') }}</p>
                    <div class="mt-3 overflow-x-auto">
                        <table class="table-auto w-full border-collapse border border-gray-300 text-left text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="border-b border-gray-300 p-2">Item</th>
                                    <th class="border-b border-gray-300 p-2">Satuan</th>
                                    <th class="border-b border-gray-300 p-2">Qty klaim</th>
                                    <th class="border-b border-gray-300 p-2">Harga satuan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($revision->data_snapshot['items'] ?? [] as $snapshotItem)
                                    <tr>
                                        <td class="border-b border-gray-200 p-2">{{ $snapshotItem['item_name'] }}</td>
                                        <td class="border-b border-gray-200 p-2">{{ $snapshotItem['unit'] }}</td>
                                        <td class="border-b border-gray-200 p-2">{{ number_format((float) $snapshotItem['claimed_quantity'], 2, ',', '.') }}</td>
                                        <td class="border-b border-gray-200 p-2">Rp {{ number_format((float) $snapshotItem['claimed_unit_price'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>
            @empty
                <p class="p-5 text-sm text-gray-500">Belum ada revisi. Riwayat akan muncul jika Owner menolak klaim.</p>
            @endforelse
        </div>
    </div>

    <section class="mt-6 border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="font-semibold">Foto bukti lapangan</h2>
            <p class="mt-1 text-sm text-gray-500">Lampiran yang ditambahkan Admin saat melakukan tinjauan.</p>
        </div>
        @if ($claim->attachments->isEmpty())
            <p class="px-5 py-6 text-sm text-gray-500">Belum ada foto bukti untuk klaim ini.</p>
        @else
            <div class="grid grid-cols-2 gap-3 p-5 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($claim->attachments as $attachment)
                    @php
                        // URL relatif agar gambar memakai host dan port tempat aplikasi sedang dibuka.
                        $attachmentUrl = '/storage/'.ltrim($attachment->file_path, '/');
                    @endphp
                    <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener noreferrer"
                       class="group block border border-gray-200 p-1 hover:border-gray-400">
                        <img src="{{ $attachmentUrl }}"
                             alt="Foto bukti klaim {{ $claim->claim_number }} nomor {{ $loop->iteration }}"
                             class="h-36 w-full object-cover sm:h-44">
                        <span class="block px-2 py-2 text-xs text-gray-600">{{ $attachment->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    @if (auth()->user()->role === 'admin' && $claim->status === 'submitted')
        <div class="mt-5 flex justify-end">
            <a href="{{ route('claims.review', $claim) }}" class="rounded-sm bg-yellow-500 px-5 py-2 text-sm font-semibold text-gray-950 hover:bg-yellow-600">Input realisasi lapangan</a>
        </div>
    @endif

    @if (auth()->user()->role === 'owner' && $claim->status === 'reviewed')
        <section class="mt-6 grid gap-4 border border-gray-200 bg-white p-5 lg:grid-cols-2">
            <form method="POST" action="{{ route('claims.approve', $claim) }}" class="flex items-end justify-between gap-4">
                @csrf
                @method('PATCH')
                <div>
                    <h2 class="font-semibold">Setujui klaim</h2>
                    <p class="mt-1 text-sm text-gray-500">Klaim akan ditandai sebagai disetujui.</p>
                </div>
                <button type="submit" class="rounded-sm bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">Setujui</button>
            </form>
            <form method="POST" action="{{ route('claims.reject', $claim) }}" class="space-y-3 border-t border-gray-200 pt-4 lg:border-l lg:border-t-0 lg:pl-5 lg:pt-0">
                @csrf
                @method('PATCH')
                <label for="rejection_reason" class="block text-sm font-semibold">Alasan penolakan</label>
                <textarea id="rejection_reason" name="rejection_reason" rows="3" required maxlength="5000" class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">{{ old('rejection_reason') }}</textarea>
                @error('rejection_reason')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                <button type="submit" class="rounded-sm bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">Tolak klaim</button>
            </form>
        </section>
    @endif

    <section class="mt-6 border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-5 py-4"><h2 class="font-semibold">Riwayat aktivitas</h2></div>
        <ol class="divide-y divide-gray-200 px-5">
            @forelse ($claim->activityLogs as $activity)
                <li class="py-3">
                    <p class="text-sm font-medium">{{ ucfirst($activity->action) }} <span class="font-normal text-gray-500">oleh {{ $activity->user->username }}</span></p>
                    @if ($activity->notes)<p class="mt-1 text-sm text-gray-600">{{ $activity->notes }}</p>@endif
                    <time class="mt-1 block text-xs text-gray-500">{{ $activity->created_at->format('d M Y H:i') }}</time>
                </li>
            @empty
                <li class="py-4 text-sm text-gray-500">Belum ada aktivitas.</li>
            @endforelse
        </ol>
    </section>

    <section id="comments" class="mt-6 border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="font-semibold">Diskusi klaim</h2>
            <p class="mt-1 text-sm text-gray-500">Komentar tersimpan sebagai riwayat permanen dan tidak dapat diubah atau dihapus.</p>
        </div>
        <ol class="space-y-3 p-5">
            @forelse ($claim->comments as $comment)
                @php
                    $roleColors = [
                        'vendor' => 'bg-blue-100 text-blue-800',
                        'admin' => 'bg-yellow-100 text-yellow-800',
                        'owner' => 'bg-green-100 text-green-800',
                    ];
                @endphp
                <li class="border border-gray-200 bg-gray-50 p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold text-gray-900">{{ $comment->user->company_name ?: $comment->user->username }}</span>
                        <span @class(['px-2 py-0.5 text-xs font-semibold', $roleColors[$comment->user->role] ?? 'bg-gray-200 text-gray-700'])>{{ ucfirst($comment->user->role) }}</span>
                        <time class="ml-auto text-xs text-gray-500">{{ $comment->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</time>
                    </div>
                    <p class="mt-2 whitespace-pre-line break-words text-sm text-gray-700">{{ $comment->message }}</p>
                </li>
            @empty
                <li class="border border-gray-200 bg-gray-50 p-4 text-sm text-gray-500">Belum ada komentar. Mulai diskusi klaim di bawah.</li>
            @endforelse
        </ol>
        <form method="POST" action="{{ route('claims.comments.store', $claim) }}" class="border-t border-gray-200 p-5">
            @csrf
            <label for="message" class="mb-1 block text-sm font-semibold text-gray-700">Tulis komentar</label>
            <textarea id="message" name="message" rows="3" maxlength="1000" required
                      class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500"
                      placeholder="Tulis komentar terkait klaim ini...">{{ old('message') }}</textarea>
            @error('message')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            <div class="mt-3 flex justify-end">
                <button type="submit" class="rounded-sm bg-yellow-500 px-4 py-2 text-sm font-semibold text-gray-950 hover:bg-yellow-600">Kirim komentar</button>
            </div>
        </form>
    </section>
@endsection
