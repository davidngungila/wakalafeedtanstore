<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'device_id',
    'device_line_id',
    'sim_slot',
    'agent_id',
    'network_id',
    'transaction_id',
    'sender',
    'provider',
    'message_body',
    'received_at',
    'sms_hash',
    'transaction_reference',
    'amount',
    'transaction_type',
    'customer_phone',
    'customer_name',
    'balance',
    'processing_status',
    'processing_error',
    'is_duplicate',
    'server_received_at',
])]
class SmsMessage extends Model
{
    protected function casts(): array
    {
        return [
            'sim_slot' => 'integer',
            'amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'received_at' => 'datetime',
            'server_received_at' => 'datetime',
            'is_duplicate' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * @return BelongsTo<DeviceLine, $this>
     */
    public function deviceLine(): BelongsTo
    {
        return $this->belongsTo(DeviceLine::class);
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
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
