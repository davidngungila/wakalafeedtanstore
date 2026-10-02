<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SmsTemplates
{
    /**
     * Default auto-SMS templates. Keys map to system events; text is
     * overridable via the `sms_templates` Setting (value is an array of
     * key => text pairs).
     *
     * @var array<string, array{label: string, text: string}>
     */
    public const DEFAULTS = [
        'credentials' => [
            'label' => 'Login credentials',
            'text' => 'Your Wakala Feedtan Store account is ready. Login: {email} | Password: {password}. Please change your password after first login.',
        ],
        'phone_verified' => [
            'label' => 'Phone verified',
            'text' => 'Your phone number {phone} has been verified successfully.',
        ],
        'two_factor_enabled' => [
            'label' => '2FA enabled',
            'text' => 'Two-factor authentication is now enabled on your account ({method}).',
        ],
        'two_factor_disabled' => [
            'label' => '2FA disabled',
            'text' => 'Two-factor authentication has been disabled on your account. If you did not do this, contact support.',
        ],
        'phone_changed' => [
            'label' => 'Phone changed',
            'text' => 'Your phone number on file has been updated. Please verify your new number.',
        ],
    ];

    /**
     * @return array<string, array{label: string, text: string}>
     */
    public function all(): array
    {
        $stored = Setting::value('sms_templates', []);
        $stored = is_array($stored) ? $stored : [];

        $templates = self::DEFAULTS;

        foreach ($templates as $key => $template) {
            if (isset($stored[$key]) && is_string($stored[$key]) && $stored[$key] !== '') {
                $templates[$key]['text'] = $stored[$key];
            }
        }

        // Custom keys added later via settings
        foreach ($stored as $key => $text) {
            if (! isset($templates[$key]) && is_string($text) && $text !== '') {
                $templates[$key] = ['label' => ucwords(str_replace('_', ' ', (string) $key)), 'text' => $text];
            }
        }

        return $templates;
    }

    /**
     * @param  array<string, string>  $vars
     */
    public function render(string $key, array $vars = []): string
    {
        $templates = $this->all();
        $text = $templates[$key]['text'] ?? '';

        foreach ($vars as $name => $value) {
            $text = str_replace('{'.$name.'}', (string) $value, $text);
        }

        return trim($text);
    }

    /**
     * Send a rendered template to a user's phone, best-effort.
     *
     * @param  array<string, string>  $vars
     */
    public function sendToUser(User $user, string $key, array $vars = []): void
    {
        try {
            if (blank($user->phone)) {
                return;
            }

            $sender = app(SmsSender::class);

            if (! $sender->isConfigured()) {
                return;
            }

            $text = $this->render($key, $vars + ['name' => $user->name ?? '', 'email' => $user->email ?? '', 'phone' => $user->phone ?? '']);

            if ($text === '') {
                return;
            }

            $sender->sendSingle($sender->normalizeRecipient((string) $user->phone), $text);
        } catch (\Throwable $exception) {
            Log::warning('Template SMS failed', ['template' => $key, 'user_id' => $user->id, 'error' => $exception->getMessage()]);
        }
    }
}
