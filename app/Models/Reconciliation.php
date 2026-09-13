<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'agent_id',
    'reconciliation_date',
    'opening_cash',
    'expected_cash',
    'counted_cash',
    'cash_variance',
    'total_float',
    'float_variance',
    'network_balances',
    'status',
    'notes',
    'reconciled_by',
])]
class Reconciliation extends Model
{
    protected function casts(): array
    {
        return [
            'reconciliation_date' => 'date',
            'opening_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'counted_cash' => 'decimal:2',
            'cash_variance' => 'decimal:2',
            'total_float' => 'decimal:2',
            'float_variance' => 'decimal:2',
            'network_balances' => 'array',
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
     * @return BelongsTo<User, $this>
     */
    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}
