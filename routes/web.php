<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:vendor')->group(function (): void {
        Route::get('/claims/create', [ClaimController::class, 'create'])->name('claims.create');
        Route::post('/claims', [ClaimController::class, 'store'])->name('claims.store');
        Route::get('/claims/{claim}/revise', [ClaimController::class, 'revisionForm'])->name('claims.revise');
        Route::put('/claims/{claim}/revise', [ClaimController::class, 'revise'])->name('claims.revise.store');
    });

    Route::middleware('role:vendor,admin,owner')->group(function (): void {
        Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');
        Route::get('/claims/{claim}', [ClaimController::class, 'show'])->name('claims.show');
        Route::get('/claims/{claim}/pdf', [ClaimController::class, 'exportPdf'])->name('claims.pdf');
        Route::post('/claims/{claim}/comments', [ClaimController::class, 'storeComment'])->name('claims.comments.store');
    });

    Route::middleware('role:admin')->group(function (): void {
        Route::get('/vendors/pending', [UserController::class, 'index'])->name('vendors.index');
        Route::patch('/vendors/{user}/approve', [UserController::class, 'approve'])->name('vendors.approve');
        Route::patch('/vendors/{user}/reject', [UserController::class, 'reject'])->name('vendors.reject');
        Route::get('/claims/{claim}/review', [ClaimController::class, 'review'])->name('claims.review');
        Route::put('/claims/{claim}/review', [ClaimController::class, 'updateReview'])->name('claims.review.update');
    });

    Route::middleware('role:owner')->group(function (): void {
        Route::patch('/claims/{claim}/approve', [ClaimController::class, 'approve'])->name('claims.approve');
        Route::patch('/claims/{claim}/reject', [ClaimController::class, 'reject'])->name('claims.reject');
    });
});
