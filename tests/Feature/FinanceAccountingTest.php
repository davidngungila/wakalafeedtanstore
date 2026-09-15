<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceAccountingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::where('email', 'admin@moneyagent.local')->firstOrFail();
        $this->supervisor = User::where('email', 'supervisor@moneyagent.local')->firstOrFail();
        $this->cashier = User::where('email', 'cashier@moneyagent.local')->firstOrFail();

        $this->cash = Account::where('code', '1100')->firstOrFail();
        $this->commission = Account::where('code', '4000')->firstOrFail();
        $this->salaries = Account::where('code', '5100')->firstOrFail();
        $this->equity = Account::where('code', '3000')->firstOrFail();
    }

    public function test_seeder_provides_a_standard_chart_of_accounts(): void
    {
        $this->assertSame(23, Account::count());

        $this->assertSame('asset', $this->cash->type);
        $this->assertTrue($this->cash->isDebitNormal());
        $this->assertFalse($this->commission->isDebitNormal());
    }

    public function test_account_pages_are_visible_to_supervisors_and_admins_only(): void
    {
        $routable = [
            'finance.accounts.index',
            'finance.journals.index',
            'finance.ledger.index',
            'finance.statements.income',
            'finance.statements.balance',
        ];

        foreach ($routable as $route) {
            $this->actingAs($this->admin)->get(route($route))->assertOk();
            $this->actingAs($this->supervisor)->get(route($route))->assertOk();
            $this->actingAs($this->cashier)->get(route($route))->assertForbidden();
        }
    }

    public function test_admin_can_create_an_account(): void
    {
        $this->actingAs($this->admin)
            ->post(route('finance.accounts.store'), [
                'code' => '1250',
                'name' => 'Petty Cash',
                'type' => 'asset',
                'description' => 'Desk cash used for daily errands',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('chart_of_accounts', ['code' => '1250', 'name' => 'Petty Cash', 'type' => 'asset']);
    }

    public function test_cashier_cannot_manage_accounts(): void
    {
        $this->actingAs($this->cashier)
            ->post(route('finance.accounts.store'), [
                'code' => '1250',
                'name' => 'Petty Cash',
                'type' => 'asset',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('chart_of_accounts', ['code' => '1250']);
    }

    public function test_duplicate_account_code_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('finance.accounts.store'), [
                'code' => '1100',
                'name' => 'Duplicate',
                'type' => 'asset',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_admin_can_post_a_balanced_journal_entry(): void
    {
        $this->actingAs($this->admin)
            ->post(route('finance.journals.store'), [
                'entry_date' => today()->toDateString(),
                'description' => 'Opening float top-up',
                'status' => 'posted',
                'lines' => [
                    ['account_id' => $this->cash->id, 'description' => 'Float in', 'debit' => 1_000_000, 'credit' => 0],
                    ['account_id' => $this->equity->id, 'description' => 'Capital', 'debit' => 0, 'credit' => 1_000_000],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $entry = JournalEntry::where('description', 'Opening float top-up')->firstOrFail();

        $this->assertSame('posted', $entry->status);
        $this->assertNotNull($entry->posted_at);
        $this->assertSame('JE-', substr($entry->reference, 0, 3));
        $this->assertSame(2, $entry->lines()->count());
        $this->assertEqualsWithDelta(1_000_000, $entry->totalDebits(), 0.01);
        $this->assertEqualsWithDelta(1_000_000, $entry->totalCredits(), 0.01);
    }

    public function test_unbalanced_journal_entry_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('finance.journals.store'), [
                'entry_date' => today()->toDateString(),
                'description' => 'Bad entry',
                'status' => 'draft',
                'lines' => [
                    ['account_id' => $this->cash->id, 'debit' => 500_000, 'credit' => 0],
                    ['account_id' => $this->commission->id, 'debit' => 0, 'credit' => 400_000],
                ],
            ]);

        $response->assertSessionHasErrors('lines');
        $this->assertDatabaseMissing('journal_entries', ['description' => 'Bad entry']);
    }

    public function test_draft_can_be_posted_and_posted_entry_can_be_reversed(): void
    {
        $entry = JournalEntry::factory()->create(['created_by' => $this->admin->id, 'status' => 'draft']);
        JournalEntryLine::factory()->for($entry, 'entry')->for($this->cash, 'account')->debit(200_000)->create();
        JournalEntryLine::factory()->for($entry, 'entry')->for($this->equity, 'account')->credit(200_000)->create();

        $this->actingAs($this->admin)
            ->post(route('finance.journals.post', $entry))
            ->assertRedirect();

        $this->assertSame('posted', $entry->fresh()->status);
        $this->assertNotNull($entry->fresh()->posted_at);

        $this->actingAs($this->admin)
            ->post(route('finance.journals.reverse', $entry))
            ->assertRedirect();

        $this->assertSame('reversed', $entry->fresh()->status);

        $reversal = JournalEntry::where('description', 'like', 'Reversal of %')->firstOrFail();
        $this->assertSame('posted', $reversal->status);
        $this->assertEqualsWithDelta(200_000, $reversal->totalDebits(), 0.01);
        $this->assertEqualsWithDelta(200_000, $reversal->totalCredits(), 0.01);
    }

    public function test_income_statement_reflects_posted_entries_only(): void
    {
        $this->postBalanced($this->commission, $this->cash, 600_000, 'Commission earned');

        $draft = JournalEntry::factory()->create(['status' => 'draft']);
        JournalEntryLine::factory()->for($draft, 'entry')->for($this->commission, 'account')->credit(100_000)->create();
        JournalEntryLine::factory()->for($draft, 'entry')->for($this->cash, 'account')->debit(100_000)->create();

        $statement = app(LedgerService::class)->incomeStatement(now()->startOfMonth(), now());

        $this->assertSame('Commission Income', $statement['revenue'][0]['name']);
        $this->assertEqualsWithDelta(600_000, $statement['revenueTotal'], 0.01);
        $this->assertEqualsWithDelta(0.0, $statement['expenseTotal'], 0.01);
        $this->assertEqualsWithDelta(600_000, $statement['netIncome'], 0.01);
    }

    public function test_income_statement_netts_expenses_against_revenue(): void
    {
        $this->postBalanced($this->commission, $this->cash, 900_000, 'Commission earned');
        $this->postBalanced($this->cash, $this->salaries, 350_000, 'Staff salaries');

        $statement = app(LedgerService::class)->incomeStatement(now()->startOfMonth(), now());

        $this->assertEqualsWithDelta(900_000, $statement['revenueTotal'], 0.01);
        $this->assertEqualsWithDelta(350_000, $statement['expenseTotal'], 0.01);
        $this->assertEqualsWithDelta(550_000, $statement['netIncome'], 0.01);
    }

    public function test_balance_sheet_balances_after_posting(): void
    {
        $this->postBalanced($this->commission, $this->cash, 800_000, 'Commission earned');
        $this->postBalanced($this->cash, $this->salaries, 300_000, 'Salaries');
        $this->postBalanced($this->equity, $this->cash, 1_000_000, 'Opening capital');

        $sheet = app(LedgerService::class)->balanceSheet();

        $this->assertEqualsWithDelta(1_500_000, $sheet['assetTotal'], 0.01);
        $this->assertEqualsWithDelta($sheet['assetTotal'], $sheet['liabilityTotal'] + $sheet['equityTotal'] + $sheet['netIncome'], 0.01);
    }

    public function test_general_ledger_carries_opening_and_closing_balances(): void
    {
        $this->postBalanced($this->equity, $this->cash, 1_000_000, 'Opening capital', today()->subMonths(2)->startOfMonth()->toDateString());

        $this->postBalanced($this->commission, $this->cash, 500_000, 'Commission earned');
        $this->postBalanced($this->cash, $this->salaries, 200_000, 'Salaries');

        $ledgerReport = app(LedgerService::class)->generalLedger(
            $this->cash,
            now()->startOfMonth(),
            now()
        );

        $this->assertEqualsWithDelta(1_000_000, $ledgerReport['opening'], 0.01);
        $this->assertEqualsWithDelta(1_300_000, $ledgerReport['closing'], 0.01);
        $this->assertCount(2, $ledgerReport['rows']);
    }

    public function test_admin_can_delete_unused_account_but_not_a_used_one(): void
    {
        $unused = Account::create([
            'code' => '9999',
            'name' => 'Unused',
            'type' => 'asset',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('finance.accounts.destroy', $unused))
            ->assertRedirect();

        $this->assertDatabaseMissing('chart_of_accounts', ['id' => $unused->id]);

        $this->postBalanced($this->equity, $this->cash, 50_000, 'Capital');

        $this->actingAs($this->admin)
            ->delete(route('finance.accounts.destroy', $this->cash))
            ->assertRedirect()
            ->assertSessionHasErrors('account');

        $this->assertDatabaseHas('chart_of_accounts', ['id' => $this->cash->id]);
    }

    /**
     * Post a balanced entry: debit $debitAccount, credit $creditAccount.
     */
    private function postBalanced(
        Account $creditAccount,
        Account $debitAccount,
        float $amount,
        string $description,
        ?string $date = null
    ): JournalEntry {
        $entry = JournalEntry::create([
            'entry_date' => $date ?? today()->toDateString(),
            'reference' => 'JE-'.fake()->unique()->numerify('TEST-####'),
            'description' => $description,
            'status' => JournalEntry::STATUS_POSTED,
            'created_by' => $this->admin->id,
            'posted_by' => $this->admin->id,
            'posted_at' => now(),
        ]);

        $entry->lines()->createMany([
            ['account_id' => $debitAccount->id, 'description' => $description, 'debit' => $amount, 'credit' => 0],
            ['account_id' => $creditAccount->id, 'description' => $description, 'debit' => 0, 'credit' => $amount],
        ]);

        return $entry;
    }
}
