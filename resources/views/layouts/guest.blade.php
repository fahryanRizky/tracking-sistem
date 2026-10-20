<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') | {{ config('app.name', 'Tracking Sistem') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    
    <!-- Gunakan Tailwind CDN untuk memastikan tampilan selalu rapi -->
    <script src="https://cdn.tailwindcss.com"></script>
    
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
    <main class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-5 py-10">
        <a href="{{ route('login') }}" class="mb-6 text-center text-xl font-bold tracking-tight text-gray-900">Tracking Sistem</a>
        <section class="border border-gray-200 bg-white p-6 sm:p-8">
            @yield('content')
        </section>
        <p class="mt-5 text-center text-sm text-gray-500">Sistem verifikasi klaim proyek</p>
    </main>
</body>
</html>