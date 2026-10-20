<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::transliterate(Str::lower($credentials['username']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'username' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $user = User::where('username', $credentials['username'])->first();

        // Status baru diperiksa setelah kata sandi benar agar informasi akun tidak bocor.
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        if ($user->status !== 'active') {
            RateLimiter::hit($key);
            $message = $user->status === 'pending'
                ? 'Akun Anda sedang ditinjau Admin'
                : 'Akun Anda tidak aktif. Hubungi Admin untuk informasi lebih lanjut.';

            throw ValidationException::withMessages(['username' => $message]);
        }

        RateLimiter::clear($key);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'company_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Pendaftar selalu menjadi vendor pending; peran tidak pernah diambil dari input form.
        User::create([
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'company_name' => $data['company_name'],
            'password' => $data['password'],
            'role' => 'vendor',
            'status' => 'pending',
        ]);

        return redirect()->route('login')->with('status', 'Pendaftaran berhasil. Akun Anda menunggu persetujuan Admin.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
