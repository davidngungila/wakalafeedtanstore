<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\CashPointController;
use App\Http\Controllers\ChartOfAccountsController;
use App\Http\Controllers\DailyOpeningController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\FinancialStatementController;
use App\Http\Controllers\FloatController;
use App\Http\Controllers\GeneralLedgerController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : redirect()->route('login'));

Route::get('/avatars/{file}', [AvatarController::class, 'show'])
    ->name('avatar.show')
    ->where('file', '.*');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/two-factor', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
    Route::post('/two-factor/cancel', [TwoFactorController::class, 'cancel'])->name('two-factor.cancel');
});

Route::middleware(['auth', 'two.factor', 'daily.opening'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/daily-opening', [DailyOpeningController::class, 'index'])->name('daily-opening.index');
    Route::get('/daily-opening/create', [DailyOpeningController::class, 'create'])->name('daily-opening.create');
    Route::post('/daily-opening', [DailyOpeningController::class, 'store'])->name('daily-opening.store');
    Route::get('/daily-opening/{dailyOpening}', [DailyOpeningController::class, 'show'])->name('daily-opening.show');
    Route::put('/daily-opening/{dailyOpening}/close', [DailyOpeningController::class, 'close'])->name('daily-opening.close');

    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');
    Route::post('/account/two-factor/confirm', [AccountController::class, 'confirmTwoFactor'])->name('account.two-factor.confirm');
    Route::post('/account/two-factor/disable', [AccountController::class, 'disableTwoFactor'])->name('account.two-factor.disable');
    Route::post('/account/recovery-codes', [AccountController::class, 'refreshRecoveryCodes'])->name('account.recovery-codes');
    Route::delete('/account/sessions/{session}', [AccountController::class, 'revokeSession'])->name('account.sessions.destroy');

    Route::get('/cash-point', [CashPointController::class, 'index'])->name('cash-point.index');
    Route::get('/cash-point/{cashPoint}', [CashPointController::class, 'show'])->name('cash-point.show');
    Route::get('/cash-point/{cashPoint}/edit', [CashPointController::class, 'edit'])->name('cash-point.edit');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('transactions.receipt');
    Route::get('/transactions/{transaction}/receipt/pdf', [TransactionController::class, 'receiptPdf'])->name('transactions.receipt.pdf');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');

    Route::get('/networks', [NetworkController::class, 'index'])->name('networks.index');
    Route::get('/networks/{network}', [NetworkController::class, 'show'])->name('networks.show');

    Route::get('/float', [FloatController::class, 'index'])->name('float.index');
    Route::post('/float', [FloatController::class, 'store'])->name('float.store');

    Route::get('/reconciliation', [ReconciliationController::class, 'index'])->name('reconciliation.index');
    Route::post('/reconciliation', [ReconciliationController::class, 'store'])->name('reconciliation.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::middleware('role:supervisor,admin')->group(function () {
        Route::put('/transactions/{transaction}/reverse', [TransactionController::class, 'reverse'])->name('transactions.reverse');
        Route::get('/reports', ReportController::class)->name('reports.index');
        Route::get('/finance', FinanceController::class)->name('finance.index');
        Route::get('/finance/chart-of-accounts', [ChartOfAccountsController::class, 'index'])->name('finance.accounts.index');
        Route::get('/finance/journal-entries', [JournalEntryController::class, 'index'])->name('finance.journals.index');
        Route::get('/finance/general-ledger', GeneralLedgerController::class)->name('finance.ledger.index');
        Route::get('/finance/statements/income', [FinancialStatementController::class, 'income'])->name('finance.statements.income');
        Route::get('/finance/statements/balance', [FinancialStatementController::class, 'balance'])->name('finance.statements.balance');
        Route::get('/audit', AuditLogController::class)->name('audit.index');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');

        Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
        Route::get('/devices/register', [DeviceController::class, 'register'])->name('devices.register');
        Route::get('/devices/{device}', [DeviceController::class, 'show'])->name('devices.show');
        Route::get('/devices/{device}/edit', [DeviceController::class, 'edit'])->name('devices.edit');
        Route::get('/devices/{device}/phones/status', [DeviceController::class, 'phonesStatus'])->name('devices.phones.status');
        Route::post('/devices/{device}/approve', [DeviceController::class, 'approve'])->name('devices.approve');
        Route::post('/devices/{device}/suspend', [DeviceController::class, 'suspend'])->name('devices.suspend');
        Route::post('/devices/{device}/block', [DeviceController::class, 'block'])->name('devices.block');
        Route::post('/devices/{device}/revoke', [DeviceController::class, 'revoke'])->name('devices.revoke');

        Route::get('/sms', [SmsController::class, 'index'])->name('sms.index');
        Route::get('/sms/stream', [SmsController::class, 'stream'])->name('sms.stream');
        Route::get('/sms/{smsMessage}', [SmsController::class, 'show'])->name('sms.show');
        Route::post('/sms/{smsMessage}/process', [SmsController::class, 'process'])->name('sms.process');
        Route::post('/sms/{smsMessage}/force', [SmsController::class, 'forceProcess'])->name('sms.force');
    });

    Route::middleware('role:admin')->group(function () {
        Route::put('/cash-point', [CashPointController::class, 'update'])->name('cash-point.update');
        Route::put('/cash-point/{cashPoint}', [CashPointController::class, 'update'])->name('cash-point.update.id');

        Route::post('/finance/chart-of-accounts', [ChartOfAccountsController::class, 'store'])->name('finance.accounts.store');
        Route::put('/finance/chart-of-accounts/{account}', [ChartOfAccountsController::class, 'update'])->name('finance.accounts.update');
        Route::delete('/finance/chart-of-accounts/{account}', [ChartOfAccountsController::class, 'destroy'])->name('finance.accounts.destroy');

        Route::post('/finance/journal-entries', [JournalEntryController::class, 'store'])->name('finance.journals.store');
        Route::post('/finance/journal-entries/{journalEntry}/post', [JournalEntryController::class, 'post'])->name('finance.journals.post');
        Route::post('/finance/journal-entries/{journalEntry}/reverse', [JournalEntryController::class, 'reverse'])->name('finance.journals.reverse');

        Route::post('/networks', [NetworkController::class, 'store'])->name('networks.store');
        Route::put('/networks/{network}', [NetworkController::class, 'update'])->name('networks.update');
        Route::delete('/networks/{network}', [NetworkController::class, 'destroy'])->name('networks.destroy');
        Route::post('/networks/rates', [NetworkController::class, 'updateRates'])->name('networks.updateRates');

        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::put('/devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
        Route::post('/devices/{device}/code', [DeviceController::class, 'regenerateCode'])->name('devices.code');
        Route::get('/devices/{device}/connect-status', [DeviceController::class, 'connectStatus'])->name('devices.connect-status');
        Route::post('/devices/{device}/lines', [DeviceController::class, 'storeLine'])->name('devices.lines.store');
        Route::delete('/devices/{device}/lines/{line}', [DeviceController::class, 'destroyLine'])->name('devices.lines.destroy');
        Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
    });
});
