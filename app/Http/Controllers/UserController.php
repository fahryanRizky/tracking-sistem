<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $vendors = User::where('role', 'vendor')
            ->where('status', 'pending')
            ->oldest()
            ->paginate(15);

        return view('users.index', compact('vendors'));
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless($user->role === 'vendor' && $user->status === 'pending', 404);

        $user->update(['status' => 'active']);

        return back()->with('status', 'Akun vendor berhasil disetujui.');
    }

    public function reject(User $user): RedirectResponse
    {
        abort_unless($user->role === 'vendor' && $user->status === 'pending', 404);

        // Akun ditandai rejected agar riwayat pendaftaran tetap tersimpan.
        $user->update(['status' => 'rejected']);

        return back()->with('status', 'Pendaftaran vendor ditolak.');
    }
}
