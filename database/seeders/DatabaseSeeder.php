<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\CommissionRate;
use App\Models\FloatTransaction;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\Reconciliation;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private int $agentOneId;

    /**
     * The networks this system integrates with.
     *
     * @var array<int, array{name: string, code: string, color: string}>
     */
    private array $networkData = [
        ['name' => 'Vodacom M-Pesa', 'code' => 'VODACOM', 'color' => '#2FA335'],
        ['name' => 'Airtel Money', 'code' => 'AIRTEL', 'color' => '#ED1C24'],
        ['name' => 'Mixx by Yas', 'code' => 'MIXX', 'color' => '#002F87'],
        ['name' => 'HaloPesa', 'code' => 'HALOPESA', 'color' => '#F47920'],
    ];

    public function run(): void
    {
        $this->purgeOperationalData();
        $this->seedNetworks();
        $this->seedCashPointAgent();
        $this->seedUsers();
        $this->deleteExtraAgents();
        $this->seedSettings();
    }

    /**
     * Remove every operational/demo record so the system starts clean.
     */
    private function purgeOperationalData(): void
    {
        foreach ([Transaction::class, FloatTransaction::class, Reconciliation::class, AuditLog::class, NetworkBalance::class, CommissionRate::class] as $model) {
            $model::query()->delete();
        }
    }

    private function seedNetworks(): void
    {
        foreach ($this->networkData as $network) {
            Network::updateOrCreate(
                ['code' => $network['code']],
                $network + ['is_active' => true]
            );
        }
    }

    /**
     * The single cash point (wakala) this system manages.
     */
    private function seedCashPointAgent(): void
    {
        $cashPoint = [
            'code' => 'DMN-001',
            'name' => 'Kilimani Money Point',
            'owner_name' => 'Baraka Mwenda',
            'phone' => '0712345678',
            'national_id' => '19840514-12345-00001-23',
            'region' => 'Dar es Salaam',
            'district' => 'Kinondoni',
            'ward' => 'Mikocheni',
            'street' => 'Old Bagamoyo Rd',
            'agent_level' => 'platinum',
            'status' => 'active',
            'cash_balance' => 0.0,
        ];

        $this->agentOneId = Agent::updateOrCreate(['code' => 'DMN-001'], $cashPoint)->id;
    }

    private function deleteExtraAgents(): void
    {
        Agent::where('code', '!=', 'DMN-001')->delete();
    }

    private function seedUsers(): void
    {
        $users = [
            [
                'name' => 'Admin System',
                'email' => 'admin@moneyagent.local',
                'phone' => '0711111111',
                'role' => 'admin',
                'agent_id' => null,
            ],
            [
                'name' => 'Halima Omary',
                'email' => 'supervisor@moneyagent.local',
                'phone' => '0722222222',
                'role' => 'supervisor',
                'agent_id' => null,
            ],
            [
                'name' => 'Baraka Mwenda',
                'email' => 'cashier@moneyagent.local',
                'phone' => '0712345678',
                'role' => 'cashier',
                'agent_id' => $this->agentOneId,
            ],
            [
                'name' => 'Neema Kimaro',
                'email' => 'neema@moneyagent.local',
                'phone' => '0722123456',
                'role' => 'cashier',
                'agent_id' => $this->agentOneId,
            ],
        ];

        foreach ($users as $index => $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'phone' => $user['phone'],
                    'role' => $user['role'],
                    'agent_id' => $user['agent_id'],
                    'is_active' => true,
                    'last_login_at' => now()->subMinutes($index * 45),
                    'password' => Hash::make('password'),
                    'two_factor_enabled' => false,
                    'two_factor_secret' => null,
                    'two_factor_recovery_codes' => null,
                    'profile_photo_path' => null,
                ]
            );
        }
    }

    private function seedSettings(): void
    {
        $defaults = [
            'general' => [
                'business_name' => 'Wakala Feed Tan Store',
                'address' => 'Kiborilon Moshi Kilimanjaro',
                'contact_email' => 'wakala@feedtanstore.com',
                'contact_phone' => '+255 7xx xxx xxx',
                'currency' => 'TZS',
            ],
            'commissions' => [
                'default_rate' => 0.5,
                'min_agent_rate' => 100,
                'topup_fee' => 1500,
                'reversal_fee' => 0,
            ],
            'security' => [
                'max_transaction_limit' => 3_000_000,
                'min_withdrawal_limit' => 1_000,
                'require_approval_above' => 1_000_000,
                'session_timeout_minutes' => 30,
            ],
            'notifications' => [
                'email_daily_summary' => '1',
                'sms_float_alerts' => '0',
                'whatsapp_reports' => '0',
                'email_failed_txns' => '1',
            ],
        ];

        foreach ($defaults as $key => $values) {
            Setting::updateOrCreate(['key' => $key], ['value' => $values]);
        }
    }
}
