<?php

namespace Tests\Feature;

use App\Mail\DailyReportMail;
use App\Mail\TransactionDetectedMail;
use App\Models\Agent;
use App\Models\Network;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Mail::fake();

        $this->agent = Agent::factory()->create([
            'code' => 'DMN-EMAIL',
            'name' => 'Email Cash Point',
            'phone' => '0711111111',
            'email' => 'cashpoint@example.com',
            'agent_level' => 'gold',
            'status' => 'active',
            'cash_balance' => 1_000_000,
        ]);
    }

    private function completeTransaction(string $reference = 'TXN-EMAIL-0001'): Transaction
    {
        $halopesa = Network::where('code', 'HALOPESA')->firstOrFail();

        return Transaction::create([
            'agent_id' => $this->agent->id,
            'network_id' => $halopesa->id,
            'type' => 'deposit',
            'amount' => 50_000,
            'fee' => 0,
            'commission' => 250,
            'status' => 'completed',
            'reference' => $reference,
            'customer_name' => 'Email Test',
            'customer_phone' => '0755001234',
            'provider_reference' => 'SR'.$reference,
        ]);
    }

    public function test_completed_transaction_sends_detected_email_to_cash_point_when_setting_enabled(): void
    {
        $txn = $this->completeTransaction();

        Mail::assertSent(TransactionDetectedMail::class, function (TransactionDetectedMail $mail) use ($txn) {
            return $mail->hasTo($this->agent->email)
                && $mail->transaction->is($txn)
                && str_contains($mail->envelope()->subject, $txn->reference);
        });
    }

    public function test_detected_email_is_skipped_when_transaction_is_not_completed(): void
    {
        $halopesa = Network::where('code', 'HALOPESA')->firstOrFail();

        Transaction::create([
            'agent_id' => $this->agent->id,
            'network_id' => $halopesa->id,
            'type' => 'deposit',
            'amount' => 50_000,
            'fee' => 0,
            'commission' => 250,
            'status' => 'failed',
            'reference' => 'TXN-EMAIL-FAIL',
            'customer_name' => 'Email Test',
            'customer_phone' => '0755001234',
        ]);

        Mail::assertNothingSent();
    }

    public function test_detected_email_is_skipped_when_cash_point_has_no_email(): void
    {
        $this->agent->update(['email' => null]);

        $this->completeTransaction();

        Mail::assertNothingSent();
    }

    public function test_detected_email_is_skipped_when_transaction_setting_disabled(): void
    {
        Setting::updateOrCreate(['key' => 'notifications'], ['value' => ['email_transactions' => '0']]);

        $this->completeTransaction();

        Mail::assertNothingSent();
    }

    public function test_daily_report_command_emails_cash_point_when_enabled(): void
    {
        $this->completeTransaction();

        $this->artisan('reports:daily-email')->assertSuccessful();

        Mail::assertSent(DailyReportMail::class, function (DailyReportMail $mail) {
            return $mail->hasTo($this->agent->email)
                && $mail->summary['count'] === 1
                && str_contains($mail->envelope()->subject, today()->format('D, j M Y'));
        });
    }

    public function test_daily_report_command_skips_when_daily_summary_disabled(): void
    {
        Setting::updateOrCreate(['key' => 'notifications'], ['value' => ['email_daily_summary' => '0']]);

        $this->artisan('reports:daily-email')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_cash_point_form_accepts_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'agent_id' => $this->agent->id]);

        $this->actingAs($admin)
            ->put(route('cash-point.update'), [
                'code' => $this->agent->code,
                'name' => $this->agent->name,
                'phone' => $this->agent->phone,
                'email' => 'new-email@example.com',
                'agent_level' => $this->agent->agent_level,
                'status' => $this->agent->status,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('agents', [
            'id' => $this->agent->id,
            'email' => 'new-email@example.com',
        ]);
    }
}
