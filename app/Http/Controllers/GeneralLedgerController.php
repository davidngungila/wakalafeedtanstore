<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\LedgerService;
use App\Services\TransactionJournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class GeneralLedgerController extends Controller
{
    public function __invoke(Request $request, LedgerService $ledger): View
    {
        app(TransactionJournalService::class)->ensureSynced();

        $account = $this->resolveAccount($request->input('account_id'));

        if ($account === null) {
            $account = Account::query()->first();
        }

        if ($account === null) {
            return view('finance.general-ledger', [
                'account' => null,
                'selectedAccountId' => 0,
                'ledgerReport' => null,
                'accounts' => collect(),
                'range' => $request->string('range', 'month')->toString(),
            ]);
        }

        $range = $request->string('range', 'month')->toString();
        $from = $this->startOf($range);
        $to = $this->endOf($range);

        $ledgerReport = $ledger->generalLedger($account, $from, $to);

        $accounts = Account::query()
            ->active()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        // Provide encrypted ids for the view so URLs stay encrypted
        $accountsForView = $accounts->map(function (Account $a) {
            return [
                'id' => $a->id,
                'encrypted' => $a->getRouteKey(),
                'code' => $a->code,
                'name' => $a->name,
            ];
        });

        $selectedEncrypted = $account ? $account->getRouteKey() : '';

        return view('finance.general-ledger', compact('account', 'ledgerReport', 'accounts', 'range', 'accountsForView', 'selectedEncrypted') + ['selectedAccountId' => $account->id]);
    }

    private function startOf(string $range): ?Carbon
    {
        return match ($range) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(6)->startOfDay(),
            'month' => now()->startOfMonth(),
            default => null,
        };
    }

    private function endOf(string $range): ?Carbon
    {
        return match ($range) {
            'today' => now(),
            '7d' => now(),
            'month' => now(),
            default => null,
        };
    }

    private function resolveAccount(mixed $value): ?Account
    {
        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        // Try encrypted first
        try {
            $id = (int) Crypt::decryptString((string) $value);
            $found = Account::find($id);
            if ($found) {
                return $found;
            }
        } catch (\Throwable) {
            // fall through to numeric check
        }

        if (is_numeric($value)) {
            return Account::find((int) $value);
        }

        return null;
    }
}
