<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'daily_opening_id',
    'reference',
    'agent_id',
    'network_id',
    'type',
    'customer_name',
    'customer_phone',
    'amount',
    'fee',
    'commission',
    'status',
    'provider_reference',
    'performed_by',
    'reversed_by',
    'reversed_at',
    'reversal_reason',
    'notes',
    'running_cash_balance',
    'running_float_balance',
])]
class Transaction extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'commission' => 'decimal:2',
            'running_cash_balance' => 'decimal:2',
            'running_float_balance' => 'decimal:2',
            'reversed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DailyOpening, $this>
     */
    public function dailyOpening(): BelongsTo
    {
        return $this->belongsTo(DailyOpening::class);
    }

    /**
     * @return BelongsTo<Agent, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * @return BelongsTo<Network, $this>
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /**
     * @return HasMany<SmsMessage, $this>
     */
    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }
}
