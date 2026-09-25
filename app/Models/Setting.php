<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'sms_authorization_token'])]
class Setting extends Model
{
    protected $hidden = [
        'sms_authorization_token',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'sms_authorization_token' => 'encrypted',
        ];
    }

    public static function value(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }
}
