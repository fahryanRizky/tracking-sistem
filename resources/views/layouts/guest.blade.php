<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') | {{ config('app.name', 'Tracking Sistem') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
    <main class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-5 py-10">
        <a href="{{ route('login') }}" class="mb-6 text-center text-xl font-bold tracking-tight text-gray-900">Tracking Sistem</a>
        <section class="border border-gray-200 bg-white p-6 sm:p-8">
            @if (session('success') || session('status'))
                <div x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 3000)" x-show="visible" x-transition
                     role="status" class="mb-5 border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">{{ session('success') ?? session('status') }}</div>
            @endif
            @if ($errors->any())
                <div x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 3000)" x-show="visible" x-transition
                     role="alert" class="mb-5 border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                    <p class="font-semibold">Periksa kembali data yang dimasukkan.</p>
                    <ul class="mt-1 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </section>
        <p class="mt-5 text-center text-sm text-gray-500">Sistem verifikasi klaim proyek</p>
    </main>
</body>
</html>