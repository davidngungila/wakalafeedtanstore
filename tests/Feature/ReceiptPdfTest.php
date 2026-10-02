<?php

namespace Tests\Feature;

use App\Http\Controllers\TransactionController;
use App\Models\Device;
use App\Models\Network;
use App\Models\Setting;
use App\Models\SmsMessage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptPdfTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function deposit(array $overrides = []): Transaction
    {
        return Transaction::factory()->create(array_merge([
            'agent_id' => $this->cashPoint()->id,
            'network_id' => Network::factory()->create(['name' => 'Mixx by Yas', 'code' => 'MIXX'])->id,
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

    public function test_the_pdf_is_generated(): void
    {
        $transaction = $this->deposit();

        $response = $this->actingAs($this->admin())->get(route('transactions.receipt.pdf', $transaction));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_the_receipt_html_carries_the_new_sections(): void
    {
        $transaction = $this->deposit();

        // The PDF template is the source of truth for the rendered markup, so
        // assert against it directly rather than parsing a PDF binary.
        $html = view('transactions.receipt-pdf', $this->receiptViewData($transaction))->render();

        // Amount in words: 20,000 reads "elfu ishirini".
        $this->assertStringContainsString('Elfu ishirini Shillings Only', $html);

        // Cash and float effects, with the network named.
        $this->assertStringContainsString('Effect on cash at till', $html);
        $this->assertStringContainsString('Effect on Mixx by Yas float', $html);
        $this->assertStringContainsString('+TZS 20,000', $html);
        $this->assertStringContainsString('−TZS 20,000', $html); // float went down

        // Shift context.
        $this->assertStringContainsString('Business date (shift)', $html);
        $this->assertStringContainsString('Shift window', $html);

        // Verification code and signature handover.
        $this->assertStringContainsString('Verification code', $html);
        $this->assertStringContainsString('Customer signature', $html);
        $this->assertStringContainsString('Agent signature', $html);

        // Status pill.
        $this->assertStringContainsString('status-completed', $html);
    }

    public function test_an_unusual_transaction_is_flagged_on_the_receipt(): void
    {
        $transaction = $this->deposit();
        $transaction->markUnusual('Amount far above the customer usual pattern');

        $html = view('transactions.receipt-pdf', $this->receiptViewData($transaction->fresh()))->render();

        $this->assertStringContainsString('FLAGGED AS UNUSUAL', $html);
        $this->assertStringContainsString('Amount far above the customer usual pattern', $html);
    }

    public function test_a_reversed_transaction_says_so_and_hides_the_signature_block(): void
    {
        $transaction = $this->deposit();
        $transaction->update([
            'status' => 'reversed',
            'reversal_reason' => 'Duplicate SMS approval',
            'reversed_at' => now(),
        ]);

        $html = view('transactions.receipt-pdf', $this->receiptViewData($transaction->fresh()))->render();

        $this->assertStringContainsString('THIS TRANSACTION HAS BEEN REVERSED', $html);
        $this->assertStringContainsString('Duplicate SMS approval', $html);
        $this->assertStringNotContainsString('Customer signature', $html);
    }

    public function test_the_sms_evidence_block_includes_the_raw_message(): void
    {
        $transaction = $this->deposit();

        $device = Device::create([
            'name' => 'Redmi Note 12',
            'agent_id' => $this->cashPoint()->id,
            'network_id' => Network::first()->id,
            'device_code' => Device::generateDeviceCode(),
            'status' => 'active',
        ]);

        SmsMessage::create([
            'device_id' => $device->id,
            'sender' => 'MPESA',
            'message_body' => 'Money Received. Amount 20,000. Ref 26218693587655',
            'sms_hash' => hash('sha256', 'Money Received. Amount 20,000. Ref 26218693587655'),
            'transaction_reference' => $transaction->provider_reference,
            'processing_status' => 'approved',
            'server_received_at' => now(),
        ]);

        $html = view('transactions.receipt-pdf', $this->receiptViewData($transaction->fresh()))->render();

        $this->assertStringContainsString('Source SMS evidence', $html);
        $this->assertStringContainsString('Money Received. Amount 20,000', $html);
        $this->assertStringContainsString('Approved', $html);
    }

    public function test_the_business_details_come_from_settings_not_a_placeholder(): void
    {
        $transaction = $this->deposit();

        Setting::updateOrCreate(['key' => 'general'], ['value' => [
            'business_name' => 'Chalinje Mobile Money',
            'address' => 'Sengerema Street, Mwanza',
            'contact_phone' => '+255 754 000 111',
            'contact_email' => 'hello@chalinje.co.tz',
        ]]);

        $html = view('transactions.receipt-pdf', $this->receiptViewData($transaction))->render();

        $this->assertStringContainsString('Chalinje Mobile Money', $html);
        $this->assertStringContainsString('Sengerema Street, Mwanza', $html);
        $this->assertStringContainsString('+255 754 000 111', $html);
        // The old hard-coded placeholder must be gone.
        $this->assertStringNotContainsString('7xx xxx xxx', $html);
    }

    /**
     * Builds the PDF template's view data through the controller itself, so the
     * tests exercise the same preparation the route uses.
     *
     * @return array<string, mixed>
     */
    private function receiptViewData(Transaction $transaction): array
    {
        $controller = app(TransactionController::class);

        $load = new \ReflectionMethod($controller, 'loadReceiptRelations');
        $load->setAccessible(true);
        $transaction = $load->invoke($controller, $transaction);

        $data = new \ReflectionMethod($controller, 'receiptData');
        $data->setAccessible(true);

        $viewData = $data->invoke($controller, $transaction);

        $this->assertIsArray($viewData);

        return $viewData;
    }
}
