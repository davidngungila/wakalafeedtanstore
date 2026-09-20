<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;

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
     * The single cash point this system manages. Returns the first agent when
     * one exists, otherwise provisions DMN-001 so a fresh install is never
     * left broken. Safe to call from seeders and request code alike.
     */
    public static function defaultCashPoint(): self
    {
        $agent = static::query()->orderBy('id')->first();

        if ($agent !== null) {
            return $agent;
        }

        try {
            return static::updateOrCreate(
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
        } catch (UniqueConstraintViolationException $e) {
            $agent = static::where('code', 'DMN-001')->first();

            return $agent ?? throw $e;
        }
    }

    /**
     * Total float balance across all networks.
     */
    public function totalFloat(): float
    {
        return (float) $this->balances()->sum('balance');
    }
}
