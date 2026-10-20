<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ config('app.name', 'Tracking Sistem') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
    @php
        $navigation = match (auth()->user()->role) {
            'vendor' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Klaim Saya', 'route' => 'claims.index', 'icon' => 'document'],
                ['label' => 'Buat Klaim', 'route' => 'claims.create', 'icon' => 'plus'],
            ],
            'admin' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Verifikasi Vendor', 'route' => 'vendors.index', 'icon' => 'users'],
                ['label' => 'Klaim Masuk', 'route' => 'claims.index', 'icon' => 'document'],
            ],
            default => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Semua Klaim', 'route' => 'claims.index', 'icon' => 'document'],
            ],
        };
    @endphp
    <div class="min-h-screen md:flex">
        <aside class="w-full border-b border-gray-200 bg-white md:min-h-screen md:w-64 md:border-b-0 md:border-r">
            <div class="border-b border-gray-200 px-6 py-5">
                <a href="{{ route('dashboard') }}" class="text-lg font-bold tracking-tight text-gray-900">Tracking Sistem</a>
                <p class="mt-1 text-xs text-gray-500">Verifikasi klaim proyek</p>
            </div>
            <nav aria-label="Navigasi utama" class="space-y-1 p-3">
                @foreach ($navigation as $item)
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'flex items-center gap-3 border-l-2 px-3 py-2.5 text-sm font-medium',
                           'border-yellow-500 bg-yellow-500 text-gray-950' => request()->routeIs($item['route']),
                           'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' => ! request()->routeIs($item['route']),
                       ])>
                        @if ($item['icon'] === 'home')
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V10Z"/></svg>
                        @elseif ($item['icon'] === 'users')
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m16 0v-2a4 4 0 0 0-3-3.87M14 3.13a4 4 0 0 1 0 7.75M14 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/></svg>
                        @elseif ($item['icon'] === 'plus')
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                        @else
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v12a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5M8 13h8m-8 4h8"/></svg>
                        @endif
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>
        <div class="min-w-0 flex-1">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 bg-white px-5 py-4 sm:px-8">
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ auth()->user()->username }}</p>
                    <p class="text-xs capitalize text-gray-500">{{ auth()->user()->role }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Keluar</button>
                </form>
            </header>
            <main class="mx-auto max-w-7xl px-5 py-6 sm:px-8 sm:py-8">
                @if (session('status'))
                    <div role="status" class="mb-5 border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div role="alert" class="mb-5 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-semibold">Periksa kembali data yang dimasukkan.</p>
                        <ul class="mt-1 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
