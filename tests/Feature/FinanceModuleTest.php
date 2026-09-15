<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Network;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->agent = Agent::create([
            'code' => 'DMN-FIN',
            'name' => 'Finance Cash Point',
            'phone' => '0712345678',
            'agent_level' => 'gold',
            'status' => 'active',
            'cash_balance' => 1_000_000,
        ]);

        $this->network = Network::create([
            'name' => 'VODACOM',
            'code' => 'VODA',
            'color' => '#e60000',
            'is_active' => true,
        ]);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@moneyagent.local')->firstOrFail();
    }

    private function supervisor(): User
    {
        return User::where('email', 'supervisor@moneyagent.local')->firstOrFail();
    }

    private function cashier(): User
    {
        return User::where('email', 'cashier@moneyagent.local')->firstOrFail();
    }

    private function makeTransactions(): void
    {
        Transaction::factory()->create([
            'agent_id' => $this->agent->id,
            'network_id' => $this->network->id,
            'type' => 'deposit',
            'amount' => 500_000,
            'fee' => 0,
            'commission' => 2_500,
            'status' => 'completed',
        ]);

        Transaction::factory()->create([
            'agent_id' => $this->agent->id,
            'network_id' => $this->network->id,
            'type' => 'withdrawal',
            'amount' => 200_000,
            'fee' => 500,
            'commission' => 1_000,
            'status' => 'completed',
        ]);

        Transaction::factory()->create([
            'agent_id' => $this->agent->id,
            'network_id' => $this->network->id,
            'type' => 'deposit',
            'amount' => 150_000,
            'fee' => 0,
            'commission' => 750,
            'status' => 'failed',
        ]);
    }

    public function test_finance_page_is_visible_to_supervisors_and_admins_only(): void
    {
        $this->makeTransactions();

        $this->actingAs($this->admin())
            ->get(route('finance.index'))
            ->assertOk()
            ->assertSee('Finance & Settlement');

        $this->actingAs($this->supervisor())
            ->get(route('finance.index'))
            ->assertOk();

        $this->actingAs($this->cashier())
            ->get(route('finance.index'))
            ->assertForbidden();
    }

    public function test_pnl_tab_shows_period_totals_excluding_non_completed(): void
    {
        $this->makeTransactions();

        $this->actingAs($this->admin())
            ->get(route('finance.index', ['range' => 'all']))
            ->assertOk()
            ->assertSee('TZS 700,000')
            ->assertSee('TZS 500,000')
            ->assertSee('TZS 200,000')
            ->assertSee('TZS 3,500')
            ->assertSee('TZS 3,000');
    }

    public function test_tabs_by_network_and_by_type_render(): void
    {
        $this->makeTransactions();

        $this->actingAs($this->admin())
            ->get(route('finance.index', ['tab' => 'network', 'range' => 'all']))
            ->assertOk()
            ->assertSee('VODACOM')
            ->assertSee('TZS 700,000');

        $this->actingAs($this->admin())
            ->get(route('finance.index', ['tab' => 'type', 'range' => 'all']))
            ->assertOk()
            ->assertSee('Customer Deposit')
            ->assertSee('Customer Withdrawal');
    }

    public function test_invalid_tab_and_range_fall_back_to_defaults(): void
    {
        $this->makeTransactions();

        $this->actingAs($this->admin())
            ->get(route('finance.index', ['tab' => 'bogus', 'range' => 'bogus']))
            ->assertOk()
            ->assertSee('Finance & Settlement')
            ->assertSee('P&L');
    }

    public function test_csv_export_returns_downloadable_attachment(): void
    {
        $this->makeTransactions();

        $response = $this->actingAs($this->admin())
            ->get(route('finance.index', ['tab' => 'pnl', 'range' => 'all', 'export' => 1]));

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString(
            'attachment',
            $response->headers->get('content-disposition', '')
        );

        $content = $response->streamedContent();

        $this->assertStringContainsString('Label,Count,"TZS Amount","TZS Commission"', $content);
        $this->assertStringContainsString('"Sep 2026",2,700000,3500', $content);
    }
}
