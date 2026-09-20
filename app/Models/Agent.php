<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'owner_name',
    'phone',
    'national_id',
    'region',
    'district',
    'ward',
    'street',
    'agent_level',
    'status',
    'cash_balance',
])]
class Agent extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'cash_balance' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasMany<NetworkBalance, $this>
     */
    public function balances(): HasMany
    {
        return $this->hasMany(NetworkBalance::class);
    }

    /**
     * @return HasMany<FloatTransaction, $this>
     */
    public function floatTransactions(): HasMany
    {
        return $this->hasMany(FloatTransaction::class);
    }

    /**
     * @return HasMany<Reconciliation, $this>
     */
    public function reconciliations(): HasMany
    {
        return $this->hasMany(Reconciliation::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Device, $this>
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Total float balance across all networks.
     */
    public function totalFloat(): float
    {
        return (float) $this->balances()->sum('balance');
    }

    /**
     * @return HasMany<DailyOpening, $this>
     */
    public function dailyOpenings(): HasMany
    {
        return $this->hasMany(DailyOpening::class);
    }
}
