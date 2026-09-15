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
    /**
     * The cash-point agent we actually operate (a single wakala).
     */
    private int $agentOneId;

    /**
     * Seed the application with the absolute minimum it needs to run: the
     * business settings, the networks we transact on, the single cash-point
     * agent, and the staff accounts. Everything else (transactions, float,
     * reconciliations, audit trail, commission configurations) is intentionally
     * left empty and reset whenever this seeder runs, so this is a single
     * source of truth for "how a brand-new deployment looks".
     */
    public function run(): void
    {
        $this->resetOperationalData();
        $this->seedNetworks();
        $this->agentOneId = $this->seedCashPoint()->id;
        $this->pruneOtherAgents();
        $this->seedUsers();
        $this->seedSettings();
        $this->call(ChartOfAccountsSeeder::class);
    }

    /**
     * Wipe every row that is derived or recorded at runtime. We keep only the
     * configuration + identity tables so a fresh seed is indistinguishable
     * from a clean install.
     */
    private function resetOperationalData(): void
    {
        foreach ([
            Transaction::class,
            FloatTransaction::class,
            Reconciliation::class,
            AuditLog::class,
            NetworkBalance::class,
            CommissionRate::class,
        ] as $model) {
            $model::query()->delete();
        }
    }

    private function seedNetworks(): void
    {
        $networks = [
            ['name' => 'Vodacom M-Pesa', 'code' => 'VODACOM', 'color' => '#E60000'],
            ['name' => 'Airtel Money', 'code' => 'AIRTEL', 'color' => '#ED1C24'],
            ['name' => 'Mixx by Yas (HaloPesa)', 'code' => 'HALOPESA', 'color' => '#F7931E'],
            ['name' => 'Tigo Pesa', 'code' => 'TIGOPESA', 'color' => '#0033A0'],
        ];

        foreach ($networks as $network) {
            Network::updateOrCreate(['code' => $network['code']], $network);
        }
    }

    private function seedCashPoint(): Agent
    {
        return Agent::updateOrCreate(
            ['code' => 'DMN-001'],
            [
                'name' => 'Kilimani Cash Point',
                'owner_name' => 'Wakala Feed Tan Store',
                'phone' => '0712345678',
                'national_id' => '19840514-601210-00121-1',
                'region' => 'Kilimanjaro',
                'district' => 'Moshi',
                'ward' => 'Mfumuni',
                'street' => 'Bondeni Street',
                'agent_level' => 'platinum',
                'status' => 'active',
                'cash_balance' => 0,
            ]
        );
    }

    /**
     * The demo installer used to ship DMN-001..006 placeholders. Production
     * should only ever have DMN-001 (the one cash point).
     */
    private function pruneOtherAgents(): void
    {
        Agent::where('code', '!=', 'DMN-001')->where('code', 'like', 'DMN-%')->delete();
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

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'phone' => $user['phone'],
                    'role' => $user['role'],
                    'agent_id' => $user['agent_id'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'two_factor_secret' => null,
                    'two_factor_enabled' => false,
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
                'max_transaction_limit' => 3000000,
                'min_withdrawal_limit' => 1000,
                'require_approval_above' => 1000000,
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
