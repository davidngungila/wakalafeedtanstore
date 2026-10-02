<?php

namespace Tests\Feature;

use App\Models\Network;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Clicking a transaction row opens the centred, two-column details popup rather
 * than the generic shared popup.
 */
class TransactionDetailsPopupTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function transaction(array $overrides = []): Transaction
    {
        return Transaction::factory()->create(array_merge([
            'agent_id' => $this->cashPoint()->id,
            'network_id' => Network::factory()->create(['name' => 'Mixx by Yas', 'color' => '#ff8800'])->id,
            'type' => 'deposit',
            'status' => 'completed',
            'amount' => 20_000,
            'fee' => 0,
            'commission' => 200,
            'customer_name' => 'MAYASSA MDOE',
            'customer_phone' => '255658347515',
            'provider_reference' => '26218693587655',
            'running_cash_balance' => 782_500,
            'running_float_balance' => 2_853_643.2,
            'running_network_balance' => 510_880,
            'notes' => 'Via SMS approval (SMS #638)',
        ], $overrides));
    }

    public function test_the_transactions_page_renders_the_details_modal(): void
    {
        $this->transaction();

        $this->actingAs($this->admin())
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('id="txnModal"', false)
            ->assertSee('id="txnModalLeft"', false)
            ->assertSee('id="txnModalRight"', false)
            ->assertSee('id="txnReceiptLink"', false)
            ->assertSee('Transaction details')
            // Two columns, matching the audit entry popup.
            ->assertSee('txn-cols', false);
    }

    public function test_the_modal_is_built_from_the_row_data_not_a_shared_popup(): void
    {
        $this->transaction();

        $response = $this->actingAs($this->admin())->get(route('transactions.index'));

        $response->assertOk();
        $content = $response->getContent();

        // The row click goes through the shared binder, not the generic popup.
        $this->assertStringContainsString('bindTransactionRows(', $content);
        $this->assertStringContainsString('openTransactionDetails', $content);
        $this->assertStringNotContainsString(
            "bindRowClick('#transactionsBody",
            $content
        );
    }

    /**
     * The same popup must appear on any page that lists transactions, so the
     * cash point page uses the shared partial rather than its own.
     */
    public function test_the_cash_point_page_uses_the_shared_details_modal(): void
    {
        $agent = $this->cashPoint();

        Transaction::factory()->create([
            'agent_id' => $agent->id,
            'network_id' => Network::factory()->create(['name' => 'Mixx by Yas'])->id,
            'type' => 'deposit',
            'status' => 'completed',
            'amount' => 1_000,
            'fee' => 0,
            'commission' => 28,
            'notes' => 'Via SMS approval (SMS #639)',
        ]);

        $response = $this->actingAs($this->admin())->get(route('cash-point.index'));

        $response->assertOk()
            ->assertSee('id="txnModal"', false)
            ->assertSee('id="txnModalLeft"', false)
            ->assertSee('id="txnModalRight"', false)
            ->assertSee('txn-cols', false);

        $content = $response->getContent();

        $this->assertStringContainsString("bindTransactionRows('#cpTxnRows tr[data-id]'", $content);
        $this->assertStringNotContainsString("bindRowClick('#cpTxnRows", $content);

        // Labels and balances are supplied from PHP so the popup matches the
        // transactions page instead of showing the raw type.
        $this->assertStringContainsString('type_label', $content);
        $this->assertStringContainsString('running_network_balance', $content);
        $this->assertStringContainsString('receipt_url', $content);
    }

    public function test_the_modal_receives_the_receipt_link_and_balances(): void
    {
        $transaction = $this->transaction();

        $response = $this->actingAs($this->admin())->get(route('transactions.index'));

        $response->assertOk();
        $content = $response->getContent();

        // The receipt route is passed through so the modal can link to it.
        $this->assertStringContainsString('receipt_url', $content);
        $this->assertStringContainsString(
            route('transactions.receipt', $transaction),
            str_replace('&amp;', '&', $content)
        );
    }

    /**
     * Customer names, notes and references originate from SMS bodies. The modal
     * is built with textContent, so such a value can never become markup.
     */
    public function test_sms_sourced_values_cannot_inject_markup(): void
    {
        $this->transaction([
            'customer_name' => '<img src=x onerror=alert(1)>',
            'notes' => '</script><script>alert(2)</script>',
        ]);

        $response = $this->actingAs($this->admin())->get(route('transactions.index'));

        $response->assertOk();
        $content = $response->getContent();

        // @json escapes angle brackets, so the payload cannot close the script
        // tag and the value can never become live markup.
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $content);
        $this->assertStringContainsString('onerror=alert(1)', $content, 'The value is still present, just escaped.');
        $this->assertStringNotContainsString('</script><script>alert(2)', $content);

        // The details modal is assembled with textContent, never by interpolating
        // values into innerHTML.
        $this->assertStringContainsString('v.textContent = value', $content);
        $this->assertStringContainsString('left.appendChild(window.txnDetailRow(row[0], row[1]))', $content);
    }
}
