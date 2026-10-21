@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <h1 class="text-xl font-bold text-gray-900">Masuk ke akun</h1>
    <p class="mt-1 text-sm text-gray-500">Gunakan username dan kata sandi yang terdaftar.</p>

    <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
        @csrf
        <div>
            <label for="username" class="mb-1 block text-sm font-medium text-gray-700">Username</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus autocomplete="username"
                   class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
            @error('username')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Kata sandi</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
            @error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="remember" value="1" class="border-gray-300 text-yellow-600 focus:ring-yellow-500">
            Ingat saya
        </label>
        <button type="submit" class="w-full rounded-sm bg-yellow-500 px-4 py-2.5 text-sm font-semibold text-gray-950 hover:bg-yellow-600">Masuk</button>
    </form>
    <p class="mt-5 text-center text-sm text-gray-600">Belum memiliki akun vendor?
        <a href="{{ route('register') }}" class="font-semibold text-gray-900 underline decoration-yellow-500 underline-offset-4">Daftar di sini</a>
    </p>
@endsection
