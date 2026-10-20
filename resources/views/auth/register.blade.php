@extends('layouts.guest')

@section('title', 'Pendaftaran Vendor')

@section('content')
    <h1 class="text-xl font-bold text-gray-900">Pendaftaran vendor</h1>
    <p class="mt-1 text-sm text-gray-500">Akun dapat digunakan setelah disetujui oleh Admin.</p>
    <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="company_name" class="mb-1 block text-sm font-medium text-gray-700">Nama perusahaan</label>
            <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" required
                   class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
            @error('company_name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="username" class="mb-1 block text-sm font-medium text-gray-700">Username</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}" required autocomplete="username"
                   class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
            @error('username')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email untuk notifikasi <span class="font-normal text-gray-500">(opsional)</span></label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email"
                   class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
            @error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Kata sandi</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
            @error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Ulangi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   class="block w-full border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-500">
        </div>
        <button type="submit" class="w-full rounded-sm bg-yellow-500 px-4 py-2.5 text-sm font-semibold text-gray-950 hover:bg-yellow-600">Kirim pendaftaran</button>
    </form>
    <p class="mt-5 text-center text-sm text-gray-600">Sudah memiliki akun?
        <a href="{{ route('login') }}" class="font-semibold text-gray-900 underline decoration-yellow-500 underline-offset-4">Masuk</a>
    </p>
@endsection
