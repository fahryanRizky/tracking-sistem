<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $summary = match ($user->role) {
            'vendor' => [
                'total' => Claim::where('vendor_id', $user->id)->count(),
                'approved' => Claim::where('vendor_id', $user->id)->where('status', 'approved')->count(),
                'pending' => Claim::where('vendor_id', $user->id)->whereIn('status', ['submitted', 'reviewed'])->count(),
            ],
            'admin' => [
                'total' => Claim::count(),
                'pending' => Claim::where('status', 'submitted')->count(),
                'vendors' => User::where('role', 'vendor')->where('status', 'pending')->count(),
            ],
            default => [
                'total' => Claim::count(),
                'approved' => Claim::where('status', 'approved')->count(),
                'approved_amount' => Claim::where('status', 'approved')->sum('total_actual_amount'),
                'awaiting_decision' => Claim::where('status', 'reviewed')->count(),
            ],
        };

        $recentClaims = Claim::with(['project', 'vendor'])
            ->when($user->role === 'vendor', fn ($query) => $query->where('vendor_id', $user->id))
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', compact('summary', 'recentClaims'));
    }
}
