<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reference',
    'agent_id',
    'network_id',
    'type',
    'amount',
    'fee',
    'commission',
    'status',
    'performed_by',
    'notes',
    'daily_opening_id',
])]
class FloatTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'commission' => 'decimal:2',
        ];
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

    public function floatDelta(): float
    {
        $amount = (float) $this->amount;

        return match ($this->type) {
            'cash_in', 'float_topup' => $amount,
            'cash_out', 'float_pull' => -$amount,
            'cash_to_float' => $amount - (float) $this->commission,
            default => 0.0,
        };
    }

    public function cashDelta(): float
    {
        $amount = (float) $this->amount;

        return match ($this->type) {
            'cash_in' => $amount,
            'cash_out', 'float_pull', 'cash_to_float' => -$amount,
            default => 0.0,
        };
    }
}
