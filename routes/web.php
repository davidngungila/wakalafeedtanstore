<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashPointController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FloatController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/two-factor', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
    Route::post('/two-factor/cancel', [TwoFactorController::class, 'cancel'])->name('two-factor.cancel');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Personal account & security (available to all roles)
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');
    Route::post('/account/two-factor/confirm', [AccountController::class, 'confirmTwoFactor'])->name('account.two-factor.confirm');
    Route::post('/account/two-factor/disable', [AccountController::class, 'disableTwoFactor'])->name('account.two-factor.disable');
    Route::post('/account/recovery-codes', [AccountController::class, 'refreshRecoveryCodes'])->name('account.recovery-codes');
    Route::delete('/account/sessions/{session}', [AccountController::class, 'revokeSession'])->name('account.sessions.destroy');

    // Cashier + (everything above is available to all roles)
    Route::get('/cash-point', [CashPointController::class, 'index'])->name('cash-point.index');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');

    Route::get('/networks', [NetworkController::class, 'index'])->name('networks.index');

    Route::get('/float', [FloatController::class, 'index'])->name('float.index');
    Route::post('/float', [FloatController::class, 'store'])->name('float.store');

    Route::get('/reconciliation', [ReconciliationController::class, 'index'])->name('reconciliation.index');
    Route::post('/reconciliation', [ReconciliationController::class, 'store'])->name('reconciliation.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Supervisor + Admin: monitor, reconcile, report, investigate, manage staff
    Route::middleware('role:supervisor,admin')->group(function () {
        Route::put('/transactions/{transaction}/reverse', [TransactionController::class, 'reverse'])->name('transactions.reverse');
        Route::get('/reports', ReportController::class)->name('reports.index');
        Route::get('/audit', AuditLogController::class)->name('audit.index');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
    });

    // Admin: configuration and management
    Route::middleware('role:admin')->group(function () {
        Route::put('/cash-point', [CashPointController::class, 'update'])->name('cash-point.update');

        Route::post('/networks', [NetworkController::class, 'store'])->name('networks.store');
        Route::put('/networks/{network}', [NetworkController::class, 'update'])->name('networks.update');
        Route::post('/networks/rates', [NetworkController::class, 'updateRates'])->name('networks.updateRates');

        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
    });
});
