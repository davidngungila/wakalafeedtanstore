<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\SmsMessage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashToFloatSmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function approver(): User
    {
        return User::where('email', 'admin@moneyagent.local')->firstOrFail();
    }

    public function test_halopesa_union_financial_message_is_recorded_as_cash_to_float(): void
    {
        $agent = $this->cashPoint();
        $agent->update(['cash_balance' => 2_000_000]);
        $network = Network::where('code', 'HALOPESA')->firstOrFail();
        $balance = NetworkBalance::create([
            'agent_id' => $agent->id,
            'network_id' => $network->id,
            'opening_balance' => 100_000,
            'balance' => 100_000,
        ]);
        $device = Device::create([
            'name' => 'HaloPesa phone',
            'agent_id' => $agent->id,
            'network_id' => $network->id,
            'device_code' => Device::generateDeviceCode(),
            'status' => 'active',
        ]);
        $sms = SmsMessage::create([
            'device_id' => $device->id,
            'agent_id' => $agent->id,
            'network_id' => $network->id,
            'sender' => 'HaloPesa',
            'provider' => 'halopesa',
            'message_body' => 'HaloPesa'.chr(10).'Tnx 6267378015120623. Umewekewa 1,000,000 TZS kutoka UNION FINANCIAL BUREAUX CO. (ID 5036) tarehe 25/09/2026 10:30:08.'.chr(10).'Salio jipya: 1,209,000.00 TZS.',
            'received_at' => now(),
            'sms_hash' => hash('sha256', 'cash-to-float-test'),
            'processing_status' => 'APPROVAL_PENDING',
            'transaction_reference' => '6267378015120623',
            'server_received_at' => now(),
        ]);

        $this->actingAs($this->approver())
            ->postJson(route('sms.approve', $sms))
            ->assertOk();

        $transaction = Transaction::firstOrFail();
        $balance->refresh();
        $agent->refresh();
        $sms->refresh();

        $this->assertSame('cash_to_float', $transaction->type);
        $this->assertSame(1_000_000.0, (float) $transaction->amount);
        $this->assertSame('6267378015120623', $transaction->provider_reference);
        $this->assertSame('UNION FINANCIAL BUREAUX CO.', $transaction->customer_name);
        $this->assertSame(1_100_000.0, (float) $balance->balance);
        $this->assertSame(1_000_000.0, (float) $agent->cash_balance);
        $this->assertSame('RECORDED', $sms->processing_status);
        $this->assertSame(0, Transaction::where('type', 'deposit')->count());
    }
}
