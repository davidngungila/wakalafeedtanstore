<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Agent;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Network;
use App\Models\Transaction;
use App\Services\TransactionJournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionJournalAutoPostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->agent = Agent::create([
            'code' => 'DMN-JRNL',
            'name' => 'Journal Cash Point',
            'phone' => '0711111111',
            'agent_level' => 'gold',
            'status' => 'active',
            'cash_balance' => 2_000_000,
        ]);
    }

    public function test_deposit_posts_three_balanced_lines_with_exact_cash_debit(): void
    {
        $halopesa = Network::where('code', 'HALOPESA')->firstOrFail();
        $amount = 55_000.0;
        $commission = 307.0;
        $fee = 0.0;

        $agent = $this->agent;
        $reference = 'TXN-260922-5126';

        $this->processDeposit($halopesa, $amount, $commission, $fee, $reference);

        $entry = JournalEntry::where('reference', $reference)->firstOrFail();

        $this->assertSame('posted', $entry->status);
        $this->assertCount(3, $entry->lines);

        $cash = $entry->lines->firstWhere('account.code', '1000');
        $float = $entry->lines->firstWhere('account.code', '1230');
        $commissionLine = $entry->lines->firstWhere('account.code', '4000');

        $this->assertNotNull($cash, 'Expected a Cash on Hand (1000) line');
        $this->assertNotNull($float, 'Expected per-network Float (1230) line');
        $this->assertNotNull($commissionLine, 'Expected Commission Income (4000) line');

        $this->assertEqualsWithDelta($amount, (float) $cash->debit, 0.01, 'Cash debit must be the exact deposit amount');
        $this->assertEqualsWithDelta(0.0, (float) $cash->credit, 0.01);
        $this->assertStringContainsString('Cash received', (string) $cash->description);

        $this->assertEqualsWithDelta(0.0, (float) $float->debit, 0.01);
        $this->assertEqualsWithDelta($amount - $commission - $fee, (float) $float->credit, 0.01, 'Float credit = amount minus commission');
        $this->assertStringContainsString('Float out', (string) $float->description);

        $this->assertEqualsWithDelta(0.0, (float) $commissionLine->debit, 0.01);
        $this->assertEqualsWithDelta($commission, (float) $commissionLine->credit, 0.01);
        $this->assertStringContainsString('Commission', (string) $commissionLine->description);

        $this->assertEqualsWithDelta($amount, $entry->totalDebits(), 0.01);
        $this->assertEqualsWithDelta($amount, $entry->totalCredits(), 0.01);
        $this->assertEqualsWithDelta($entry->totalCredits(), $entry->totalDebits(), 0.01, 'Double entry must balance');
    }

    public function test_running_float_is_independent_per_network(): void
    {
        $halopesa = Network::where('code', 'HALOPESA')->firstOrFail();
        $vodacom = Network::where('code', 'VODACOM')->firstOrFail();

        $this->processDeposit($halopesa, 100_000.0, 500.0, 0.0, 'TXN-HALO-0001');
        $this->processDeposit($vodacom, 100_000.0, 500.0, 0.0, 'TXN-VODA-0001');

        $haloFloat = Account::where('code', '1230')->firstOrFail();
        $vodaFloat = Account::where('code', '1210')->firstOrFail();

        $this->assertNotEquals($haloFloat->id, $vodaFloat->id, 'Each network must have its own float account');

        $haloLines = JournalEntryLine::where('account_id', $haloFloat->id)->get();
        $vodaLines = JournalEntryLine::where('account_id', $vodaFloat->id)->get();

        $this->assertTrue($haloLines->isNotEmpty(), 'HaloPesa float account should be used');
        $this->assertTrue($vodaLines->isNotEmpty(), 'Vodacom float account should be used');

        $haloBalance = (float) $haloLines->sum(fn ($line) => $line->rawDelta());
        $vodaBalance = (float) $vodaLines->sum(fn ($line) => $line->rawDelta());

        $this->assertEqualsWithDelta(-99_500.0, $haloBalance, 0.01, 'Float credit makes rawDelta negative (debit - credit)');
        $this->assertEqualsWithDelta(-99_500.0, $vodaBalance, 0.01, 'Float credit makes rawDelta negative (debit - credit)');
    }

    public function test_failed_transaction_posts_no_journal(): void
    {
        $halopesa = Network::where('code', 'HALOPESA')->firstOrFail();

        Transaction::factory()->create([
            'agent_id' => $this->agent->id,
            'network_id' => $halopesa->id,
            'type' => 'deposit',
            'amount' => 50_000,
            'fee' => 0,
            'commission' => 250,
            'status' => 'failed',
            'reference' => 'TXN-FAIL-0001',
        ]);

        $this->assertDatabaseMissing('journal_entries', ['reference' => 'TXN-FAIL-0001']);
    }

    public function test_reversed_transaction_reverses_posted_journal(): void
    {
        $halopesa = Network::where('code', 'HALOPESA')->firstOrFail();

        $reference = 'TXN-REV-0001';

        $txn = $this->processDeposit($halopesa, 50_000.0, 250.0, 0.0, $reference);

        $this->assertDatabaseHas('journal_entries', ['reference' => $reference, 'status' => 'posted']);

        app(TransactionJournalService::class)->reverseForTransaction($txn, $this->agent->id);

        $this->assertDatabaseHas('journal_entries', ['reference' => $reference, 'status' => 'reversed']);

        $reversal = JournalEntry::where('reference', 'like', 'RVS-%')->where('description', 'like', 'Reversal of %')->firstOrFail();

        $this->assertEqualsWithDelta(50_000.0, $reversal->totalDebits(), 0.01);
        $this->assertEqualsWithDelta(50_000.0, $reversal->totalCredits(), 0.01);
    }

    public function test_bank_to_wallet_posts_float_in_balanced_lines_per_network(): void
    {
        $halopesa = Network::where('code', 'HALOPESA')->firstOrFail();
        $amount = 55_000.0;
        $commission = 307.0;
        $fee = 0.0;
        $reference = 'TXN-B2W-0001';

        $txn = Transaction::create([
            'agent_id' => $this->agent->id,
            'network_id' => $halopesa->id,
            'type' => 'bank_to_wallet',
            'amount' => $amount,
            'fee' => $fee,
            'commission' => $commission,
            'status' => 'completed',
            'reference' => $reference,
            'customer_name' => 'Test Customer',
            'customer_phone' => '0755001234',
            'provider_reference' => 'SR'.$reference,
        ]);

        $entry = JournalEntry::where('reference', $reference)->firstOrFail();

        $this->assertSame('posted', $entry->status);
        $this->assertCount(3, $entry->lines);

        $float = $entry->lines->firstWhere('account.code', '1230');
        $liability = $entry->lines->firstWhere('account.code', '2100');
        $commissionLine = $entry->lines->firstWhere('account.code', '4000');

        $this->assertNotNull($float, 'Expected per-network Float (1230) line');
        $this->assertNotNull($liability, 'Expected Customer Float Liability (2100) line');
        $this->assertNotNull($commissionLine, 'Expected Commission Income (4000) line');

        // Float-in: Dr Float full amount
        $this->assertEqualsWithDelta($amount, (float) $float->debit, 0.01, 'Float debit = full bank_to_wallet amount');
        $this->assertEqualsWithDelta(0.0, (float) $float->credit, 0.01);
        $this->assertStringContainsString('Float in', (string) $float->description);

        // Cr Liability amount minus commission
        $this->assertEqualsWithDelta(0.0, (float) $liability->debit, 0.01);
        $this->assertEqualsWithDelta($amount - $commission - $fee, (float) $liability->credit, 0.01, 'Liability credit = amount minus commission');

        // Cr Commission
        $this->assertEqualsWithDelta($commission, (float) $commissionLine->credit, 0.01);

        $this->assertEqualsWithDelta($amount, $entry->totalDebits(), 0.01);
        $this->assertEqualsWithDelta($amount, $entry->totalCredits(), 0.01);
        $this->assertEqualsWithDelta($entry->totalCredits(), $entry->totalDebits(), 0.01, 'Double entry must balance');

        // Cash must be untouched: no Cash on Hand line at all for bank_to_wallet
        $this->assertNull($entry->lines->firstWhere('account.code', '1000'), 'bank_to_wallet must not touch cash');
    }

    private function processDeposit(Network $network, float $amount, float $commission, float $fee, string $reference): Transaction
    {
        return Transaction::create([
            'agent_id' => $this->agent->id,
            'network_id' => $network->id,
            'type' => 'deposit',
            'amount' => $amount,
            'fee' => $fee,
            'commission' => $commission,
            'status' => 'completed',
            'reference' => $reference,
            'customer_name' => 'Test Customer',
            'customer_phone' => '0755001234',
            'provider_reference' => 'SR'.$reference,
        ]);
    }
}
