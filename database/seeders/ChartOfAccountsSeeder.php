<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * The standard chart of accounts a Tanzanian mobile-money wakala needs to
     * start double-entry bookkeeping. Rows are idempotently upserted by code so
     * re-seeding never duplicates accounts.
     */
    public function run(): void
    {
        $accounts = [
            // Assets
            ['code' => '1000', 'name' => 'Cash on Hand', 'type' => Account::TYPE_ASSET],
            ['code' => '1100', 'name' => 'Bank Account', 'type' => Account::TYPE_ASSET],
            ['code' => '1200', 'name' => 'Mobile Money Float', 'type' => Account::TYPE_ASSET],
            ['code' => '1210', 'name' => 'Float — Vodacom', 'type' => Account::TYPE_ASSET, 'parent' => '1200'],
            ['code' => '1220', 'name' => 'Float — Airtel', 'type' => Account::TYPE_ASSET, 'parent' => '1200'],
            ['code' => '1230', 'name' => 'Float — Mixx (HaloPesa)', 'type' => Account::TYPE_ASSET, 'parent' => '1200'],
            ['code' => '1240', 'name' => 'Float — Tigo', 'type' => Account::TYPE_ASSET, 'parent' => '1200'],
            ['code' => '1300', 'name' => 'Accounts Receivable', 'type' => Account::TYPE_ASSET],
            ['code' => '1400', 'name' => 'Prepayments & Advances', 'type' => Account::TYPE_ASSET],

            // Liabilities
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => Account::TYPE_LIABILITY],
            ['code' => '2100', 'name' => 'Customer Float Liability', 'type' => Account::TYPE_LIABILITY],
            ['code' => '2200', 'name' => 'Accrued Expenses', 'type' => Account::TYPE_LIABILITY],

            // Equity
            ['code' => '3000', 'name' => 'Owner’s Capital', 'type' => Account::TYPE_EQUITY],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => Account::TYPE_EQUITY],

            // Income
            ['code' => '4000', 'name' => 'Commission Income', 'type' => Account::TYPE_INCOME],
            ['code' => '4100', 'name' => 'Fee Income', 'type' => Account::TYPE_INCOME],
            ['code' => '4200', 'name' => 'Other Income', 'type' => Account::TYPE_INCOME],

            // Expenses
            ['code' => '5000', 'name' => 'Cost of Sales', 'type' => Account::TYPE_EXPENSE],
            ['code' => '5100', 'name' => 'Staff Salaries', 'type' => Account::TYPE_EXPENSE],
            ['code' => '5200', 'name' => 'Rent & Utilities', 'type' => Account::TYPE_EXPENSE],
            ['code' => '5300', 'name' => 'Network Charges', 'type' => Account::TYPE_EXPENSE],
            ['code' => '5400', 'name' => 'Bank & Transaction Charges', 'type' => Account::TYPE_EXPENSE],
            ['code' => '5500', 'name' => 'General & Administrative', 'type' => Account::TYPE_EXPENSE],
        ];

        $ids = [];

        foreach ($accounts as $account) {
            [$parent, $withParent] = array_key_exists('parent', $account)
                ? [$account['parent'], true]
                : [null, false];

            $created = Account::updateOrCreate(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'description' => null,
                    'is_active' => true,
                ]
            );

            $ids[$account['code']] = $created->id;

            if ($withParent) {
                $created->update(['parent_id' => $ids[$parent]]);
            }
        }
    }
}
