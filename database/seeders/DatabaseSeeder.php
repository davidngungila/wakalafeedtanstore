<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\CommissionRate;
use App\Models\FloatTransaction;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\Reconciliation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private int $agentOneId;

    private int $adminId;

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

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $agentRecords;

    /**
     * Commission schedule, keyed by transaction type -> agent level -> rate.
     *
     * @var array<string, array<string, float>>
     */
    private array $rateSchedule = [
        'deposit' => ['bronze' => 0.5, 'silver' => 0.6, 'gold' => 0.8, 'platinum' => 0.9],
        'withdrawal' => ['bronze' => 0.6, 'silver' => 0.7, 'gold' => 0.9, 'platinum' => 1.1],
        'send_money' => ['bronze' => 0.8, 'silver' => 0.9, 'gold' => 1.0, 'platinum' => 1.2],
        'bill_payment' => ['bronze' => 0.7, 'silver' => 0.8, 'gold' => 0.9, 'platinum' => 1.0],
        'airtime' => ['bronze' => 4.0, 'silver' => 4.5, 'gold' => 5.0, 'platinum' => 5.5],
        'data' => ['bronze' => 3.0, 'silver' => 3.5, 'gold' => 4.0, 'platinum' => 4.5],
        'bank_transfer' => ['bronze' => 0.4, 'silver' => 0.5, 'gold' => 0.6, 'platinum' => 0.7],
    ];

    public function run(): void
    {
        $this->seedNetworks();
        $this->seedAgents();
        $this->seedCommissionRates();
        $this->seedUsers();
        $this->seedNetworkBalances();
        $this->seedTransactions();
        $this->seedFloatTransactions();
        $this->seedReconciliations();
        $this->seedAuditLogs();
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

    private function seedAgents(): void
    {
        $this->agentRecords = [
            ['code' => 'DMN-001', 'name' => 'Kilimani Money Point', 'owner_name' => 'Baraka Mwenda', 'phone' => '0712345678', 'national_id' => '19840514-12345-00001-23', 'region' => 'Dar es Salaam', 'district' => 'Kinondoni', 'ward' => 'Mikocheni', 'street' => 'Old Bagamoyo Rd', 'agent_level' => 'platinum', 'status' => 'active', 'cash_balance' => 8425000.00],
            ['code' => 'DMN-002', 'name' => 'Tegeta Cash Centre', 'owner_name' => 'Neema Kimaro', 'phone' => '0722123456', 'national_id' => '19910111-67890-00002-23', 'region' => 'Dar es Salaam', 'district' => 'Kinondoni', 'ward' => 'Tegeta', 'street' => 'Mbezi Louis', 'agent_level' => 'gold', 'status' => 'inactive', 'cash_balance' => 3100000.00],
            ['code' => 'DMN-003', 'name' => 'Kariakoo Floating Services', 'owner_name' => 'Juma Hassan', 'phone' => '0735112233', 'national_id' => '19881203-11223-00003-23', 'region' => 'Dar es Salaam', 'district' => 'Ilala', 'ward' => 'Kariakoo', 'street' => 'Morogoro Rd', 'agent_level' => 'silver', 'status' => 'inactive', 'cash_balance' => 1250000.00],
            ['code' => 'DMN-004', 'name' => 'Arusha Clocktower Agent', 'owner_name' => 'Upendo Mushi', 'phone' => '0744556677', 'national_id' => '19950722-44556-00004-23', 'region' => 'Arusha', 'district' => 'Arusha City', 'ward' => 'Sokoni I', 'street' => 'Old Moshi Rd', 'agent_level' => 'gold', 'status' => 'inactive', 'cash_balance' => 5230000.00],
            ['code' => 'DMN-005', 'name' => 'Mwanza Posta Kiosk', 'owner_name' => 'Emmanuel Sanga', 'phone' => '0766778899', 'national_id' => '19900315-77889-00005-23', 'region' => 'Mwanza', 'district' => 'Ilemela', 'ward' => 'Nyamagana', 'street' => 'Station Rd', 'agent_level' => 'bronze', 'status' => 'inactive', 'cash_balance' => 480000.00],
            ['code' => 'DMN-006', 'name' => 'Mbeya Soweto Agent', 'owner_name' => 'Zawadi Mwakalukwa', 'phone' => '0777889900', 'national_id' => '19941209-99001-00006-23', 'region' => 'Mbeya', 'district' => 'Mbeya City', 'ward' => 'Soweto', 'street' => 'Kandasa Rd', 'agent_level' => 'silver', 'status' => 'inactive', 'cash_balance' => 1950000.00],
        ];

        foreach ($this->agentRecords as $agent) {
            $created = Agent::updateOrCreate(['code' => $agent['code']], $agent);
            if ($agent['code'] === 'DMN-001') {
                $this->agentOneId = $created->id;
            }
        }
    }

    private function seedCommissionRates(): void
    {
        $levels = ['bronze', 'silver', 'gold', 'platinum'];
        $ranges = [
            'deposit' => [1, 5_000_000],
            'withdrawal' => [1, 5_000_000],
            'send_money' => [1, 2_500_000],
            'bill_payment' => [1, 1_000_000],
            'airtime' => [100, 200_000],
            'data' => [100, 200_000],
            'bank_transfer' => [1, 5_000_000],
        ];

        foreach (Network::all() as $network) {
            foreach ($levels as $level) {
                foreach ($this->rateSchedule as $type => $levelRates) {
                    [$min, $max] = $ranges[$type];
                    CommissionRate::updateOrCreate(
                        [
                            'network_id' => $network->id,
                            'agent_level' => $level,
                            'transaction_type' => $type,
                        ],
                        [
                            'min_amount' => $min,
                            'max_amount' => $max,
                            'rate' => $levelRates[$level],
                            'rate_type' => 'percent',
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
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
                'agent_id' => Agent::where('code', 'DMN-002')->value('id'),
            ],
        ];

        foreach ($users as $index => $user) {
            $created = User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'phone' => $user['phone'],
                    'role' => $user['role'],
                    'agent_id' => $user['agent_id'],
                    'is_active' => true,
                    'last_login_at' => now()->subMinutes($index * 45),
                    'password' => Hash::make('password'),
                ]
            );

            if ($created->role === 'admin') {
                $this->adminId = $created->id;
            }
        }
    }

    private function seedNetworkBalances(): void
    {
        $opening = [
            'VODACOM' => 8_200_000,
            'AIRTEL' => 5_600_000,
            'MIXX' => 3_400_000,
            'HALOPESA' => 1_900_000,
        ];

        $current = [
            'VODACOM' => 6_450_000,
            'AIRTEL' => 4_120_000,
            'MIXX' => 2_890_000,
            'HALOPESA' => 1_540_000,
        ];

        foreach (Agent::all() as $agent) {
            foreach (Network::all() as $network) {
                $multiplier = match ($agent->agent_level) {
                    'platinum' => 1.6,
                    'gold' => 1.15,
                    'silver' => 0.7,
                    default => 0.35,
                };

                NetworkBalance::updateOrCreate(
                    ['agent_id' => $agent->id, 'network_id' => $network->id],
                    [
                        'opening_balance' => round($opening[$network->code] * $multiplier, 2),
                        'balance' => round($current[$network->code] * $multiplier, 2),
                    ]
                );
            }
        }
    }

    private function seedTransactions(): void
    {
        $rows = [
            // [type, network, customer, phone, amount, status, daysAgo, commission, fee, provider]
            ['deposit', 'VODACOM', 'Mwananchi Communications', '0755123456', 1500000, 'completed', 0, 9000, 1500, 'SR9137201'],
            ['withdrawal', 'AIRTEL', 'Shida Kapemba', '0766112233', 500000, 'completed', 0, 3000, 1000, 'SR9137202'],
            ['send_money', 'HALOPESA', 'Salma Mzee', '0755987421', 200000, 'completed', 0, 1600, 600, 'SR9137203'],
            ['airtime', 'VODACOM', 'Omari Mlinga', '0712345567', 50000, 'completed', 0, 2500, 0, 'SR9137204'],
            ['data', 'AIRTEL', 'Rehema Seleman', '0766778801', 75000, 'completed', 0, 3000, 0, 'SR9137205'],
            ['bill_payment', 'MIXX', 'TANESCO', '0735333444', 250000, 'completed', 0, 2000, 800, 'SR9137206'],
            ['deposit', 'VODACOM', 'Juma Athumani', '0744556677', 1000000, 'completed', 0, 6000, 1000, 'SR9137207'],
            ['withdrawal', 'MIXX', 'Anna Richard', '0788990011', 300000, 'failed', 0, 0, 0, null],
            ['send_money', 'AIRTEL', 'Peter Johnson', '0755332244', 100000, 'pending', 0, 0, 0, null],
            ['deposit', 'AIRTEL', 'Halima Binti Saidi', '0711223344', 800000, 'completed', 1, 4800, 800, 'SR9137208'],
            ['withdrawal', 'VODACOM', 'Daniel Mushi', '0766445567', 450000, 'completed', 1, 2700, 900, 'SR9137209'],
            ['bill_payment', 'VODACOM', 'TANESCO', '0755001122', 180000, 'completed', 1, 1440, 600, 'SR9137210'],
            ['send_money', 'MIXX', 'Grace Lema', '0744667788', 120000, 'completed', 1, 960, 400, 'SR9137211'],
            ['airtime', 'HALOPESA', 'Moses Mwakalinga', '0788990022', 30000, 'completed', 1, 1500, 0, 'SR9137212'],
            ['data', 'VODACOM', 'Amina Ndosi', '0733112233', 50000, 'reversed', 1, 0, 0, 'SR9137213'],
            ['withdrawal', 'HALOPESA', 'Stella Mahenge', '0711998877', 350000, 'completed', 2, 2100, 700, 'SR9137214'],
            ['deposit', 'MIXX', 'Saidi Mbwana', '0766554433', 1200000, 'completed', 2, 7200, 1200, 'SR9137215'],
            ['bill_payment', 'AIRTEL', 'DAWASCO', '0733665544', 95000, 'completed', 2, 760, 300, 'SR9137216'],
            ['send_money', 'VODACOM', 'Doreen Shayo', '0755112233', 300000, 'failed', 2, 0, 0, null],
            ['airtime', 'AIRTEL', 'Emmanuel Kavishe', '0788445566', 20000, 'completed', 2, 1000, 0, 'SR9137217'],
            ['data', 'HALOPESA', 'Paschal Rutta', '0744778899', 100000, 'completed', 3, 4000, 0, 'SR9137218'],
            ['withdrawal', 'VODACOM', 'Lilian Mwaikambo', '0711223344', 600000, 'completed', 3, 3600, 1200, 'SR9137219'],
            ['deposit', 'AIRTEL', 'Yusuph Kipanga', '0755443322', 950000, 'completed', 3, 5700, 950, 'SR9137220'],
            ['bank_transfer', 'VODACOM', 'NMB Bank', '0766123456', 2000000, 'completed', 3, 12000, 2000, 'SR9137221'],
            ['withdrawal', 'MIXX', 'Pili Mkunde', '0733221155', 250000, 'reversed', 4, 0, 0, 'SR9137222'],
            ['send_money', 'HALOPESA', 'Jackson Mwakyusa', '0788991122', 150000, 'completed', 4, 1200, 500, 'SR9137223'],
            ['deposit', 'VODACOM', 'BEA Doula Group', '0711556677', 2500000, 'completed', 4, 15000, 2500, 'SR9137224'],
            ['airtime', 'MIXX', 'Furaha Kessy', '0755203040', 25000, 'completed', 4, 1250, 0, 'SR9137225'],
            ['data', 'VODACOM', 'Neema Ruben', '0766332211', 15000, 'completed', 5, 600, 0, 'SR9137226'],
            ['withdrawal', 'AIRTEL', 'Cosmas Mwinuka', '0733778899', 700000, 'completed', 5, 4200, 1400, 'SR9137227'],
            ['bill_payment', 'HALOPESA', 'UWASA', '0788112233', 140000, 'completed', 5, 1120, 450, 'SR9137228'],
            ['send_money', 'VODACOM', 'Happiness Njau', '0711889977', 220000, 'completed', 5, 1760, 700, 'SR9137229'],
            ['deposit', 'AIRTEL', 'Mikocheni Hardware', '0744551199', 780000, 'completed', 5, 4680, 780, 'SR9137230'],
            ['withdrawal', 'VODACOM', 'Ramadhani Mkomwa', '0766558877', 320000, 'completed', 6, 1920, 640, 'SR9137231'],
            ['airtime', 'AIRTEL', 'Christina Bwana', '0733667788', 40000, 'completed', 6, 2000, 0, 'SR9137232'],
            ['data', 'HALOPESA', 'Abdallah Omar', '0711223344', 60000, 'completed', 6, 2400, 0, 'SR9137233'],
            ['deposit', 'VODACOM', 'Kariakoo Spices Ltd', '0755778899', 3100000, 'completed', 6, 18600, 3100, 'SR9137234'],
            ['send_money', 'MIXX', 'Zena Hassan', '0788996655', 400000, 'pending', 6, 0, 0, null],
        ];

        $performedBy = $this->adminId;
        $agentId = $this->agentOneId;
        $networkIds = [];

        foreach (Network::all() as $network) {
            $networkIds[$network->code] = $network->id;
        }

        foreach ($rows as $index => $row) {
            [$type, $networkCode, $customer, $phone, $amount, $status, $daysAgo, $commission, $fee, $provider] = $row;

            $created = now()->subDays($daysAgo)->setTime(9 + ($index % 8), ($index * 7) % 60);

            $payload = [
                'reference' => 'TXN-'.$created->format('ymd').sprintf('-%04d', $index + 1),
                'agent_id' => $agentId,
                'network_id' => $networkIds[$networkCode],
                'type' => $type,
                'customer_name' => $customer,
                'customer_phone' => $phone,
                'amount' => $amount,
                'fee' => $fee,
                'commission' => $commission,
                'status' => $status,
                'provider_reference' => $provider,
                'performed_by' => $status === 'pending' ? null : $performedBy,
                'created_at' => $created,
                'updated_at' => $created,
            ];

            if ($status === 'reversed') {
                $payload['reversed_by'] = $performedBy;
                $payload['reversed_at'] = now()->subDays($daysAgo)->addHours(2);
                $payload['reversal_reason'] = 'Customer dispute - duplicate charge';
                $payload['notes'] = 'Reversed via admin approval';
            }

            Transaction::create($payload);
        }
    }

    private function seedFloatTransactions(): void
    {
        $rows = [
            ['FLT-20260914-001', 'VODACOM', 'float_topup', 2000000, 'completed', 0, 'Float top-up via M-Pesa'],
            ['FLT-20260914-002', 'AIRTEL', 'cash_in', 1500000, 'completed', 0, 'Cash in from till'],
            ['FLT-20260913-001', 'MIXX', 'float_pull', 500000, 'completed', 1, 'Float pulled to head office'],
            ['FLT-20260913-002', 'VODACOM', 'cash_in', 800000, 'completed', 1, 'Cash in from till'],
            ['FLT-20260912-001', 'HALOPESA', 'float_topup', 750000, 'pending', 2, 'Top-up awaiting confirmation'],
            ['FLT-20260912-002', 'AIRTEL', 'cash_out', 600000, 'completed', 2, 'Cash out to till'],
            ['FLT-20260911-001', 'VODACOM', 'cash_out', 1200000, 'failed', 3, 'Rejected by network'],
            ['FLT-20260910-001', 'MIXX', 'float_topup', 300000, 'completed', 4, 'Top-up via Yas'],
        ];

        $agentId = $this->agentOneId;
        $performedBy = $this->adminId;
        $networkIds = [];

        foreach (Network::all() as $network) {
            $networkIds[$network->code] = $network->id;
        }

        foreach ($rows as [$reference, $networkCode, $type, $amount, $status, $daysAgo, $notes]) {
            FloatTransaction::create([
                'reference' => $reference,
                'agent_id' => $agentId,
                'network_id' => $networkIds[$networkCode],
                'type' => $type,
                'amount' => $amount,
                'fee' => $type === 'float_topup' ? 1500 : 0,
                'status' => $status,
                'performed_by' => $performedBy,
                'notes' => $notes,
                'created_at' => now()->subDays($daysAgo)->setTime(10, ($daysAgo * 13) % 60),
            ]);
        }
    }

    private function seedReconciliations(): void
    {
        $agentId = $this->agentOneId;
        $adminId = $this->adminId;

        $records = [
            [
                'date' => now()->subDays(1)->toDateString(),
                'opening_cash' => 8000000,
                'expected_cash' => 9310000,
                'counted_cash' => 9310000,
                'total_float' => 14805000,
                'float_variance' => 0,
                'status' => 'reconciled',
                'notes' => 'All good',
            ],
            [
                'date' => now()->subDays(2)->toDateString(),
                'opening_cash' => 7600000,
                'expected_cash' => 8720000,
                'counted_cash' => 8695000,
                'total_float' => 14620000,
                'float_variance' => 25000,
                'status' => 'variance',
                'notes' => 'Cash short by TZS 25,000',
            ],
            [
                'date' => now()->subDays(3)->toDateString(),
                'opening_cash' => 7000000,
                'expected_cash' => 8230000,
                'counted_cash' => 8230000,
                'total_float' => 15100000,
                'float_variance' => 0,
                'status' => 'reconciled',
                'notes' => '',
            ],
        ];

        foreach ($records as $record) {
            Reconciliation::create([
                'agent_id' => $agentId,
                'reconciliation_date' => $record['date'],
                'opening_cash' => $record['opening_cash'],
                'expected_cash' => $record['expected_cash'],
                'counted_cash' => $record['counted_cash'],
                'cash_variance' => $record['counted_cash'] - $record['expected_cash'],
                'total_float' => $record['total_float'],
                'float_variance' => $record['float_variance'],
                'network_balances' => [
                    ['network' => 'Vodacom M-Pesa', 'system' => 6450000, 'counted' => 6450000],
                    ['network' => 'Airtel Money', 'system' => 4120000, 'counted' => 4120000],
                    ['network' => 'Mixx by Yas', 'system' => 2890000, 'counted' => 2890000],
                ],
                'status' => $record['status'],
                'notes' => $record['notes'],
                'reconciled_by' => $adminId,
                'created_at' => Carbon::parse($record['date'])->setTime(17, 30),
            ]);
        }
    }

    private function seedAuditLogs(): void
    {
        $actions = [
            ['Transaction processed', 'Transaction', 12],
            ['Agent registered', 'Agent', 2],
            ['Commission rate updated', 'CommissionRate', 3],
            ['User logged in', 'User', 1],
            ['Float top-up approved', 'FloatTransaction', 1],
            ['Reconciliation completed', 'Reconciliation', 1],
            ['Transaction reversed', 'Transaction', 2],
        ];

        foreach ($actions as $index => [$action, $entity, $count]) {
            AuditLog::create([
                'user_id' => $this->adminId,
                'action' => $action,
                'entity_type' => $entity,
                'entity_id' => $index + 1,
                'details' => ['count' => $count, 'level' => 'info'],
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subHours($index * 3),
            ]);
        }
    }
}
