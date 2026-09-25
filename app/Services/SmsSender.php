<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class SmsSender
{
    public function sendSingle(string $to, string $text, int $flash = 0, ?string $reference = null): Response
    {
        $text = trim($text);
        $this->validateFlash($flash);

        if ($text === '') {
            throw new InvalidArgumentException('SMS text is required.');
        }

        $settings = $this->settings();

        return $this->post((string) config('sms.outbound.single_endpoint', '/api/sms/v2/text/single'), [
            'from' => $settings['sender_id'],
            'to' => $this->normalizeRecipient($to),
            'text' => $text,
            'flash' => $flash,
            'reference' => $this->reference($reference),
        ], $settings);
    }

    public function sendMultiple(array $messages, int $flash = 0, ?string $reference = null): Response
    {
        $this->validateFlash($flash);

        $maxRecipients = max(1, (int) config('sms.outbound.max_bulk_recipients', 100));
        if ($messages === [] || count($messages) > $maxRecipients) {
            throw new InvalidArgumentException("Bulk SMS accepts between 1 and {$maxRecipients} recipients.");
        }

        $payloadMessages = [];
        foreach ($messages as $message) {
            if (! is_array($message) || ! isset($message['to'], $message['text']) || ! is_string($message['to']) || ! is_string($message['text'])) {
                throw new InvalidArgumentException('Each bulk SMS message must include a recipient and text.');
            }

            $text = trim($message['text']);
            if ($text === '') {
                throw new InvalidArgumentException('SMS text is required.');
            }

            $payloadMessages[] = [
                'to' => $this->normalizeRecipient($message['to']),
                'text' => $text,
            ];
        }

        return $this->post((string) config('sms.outbound.multiple_endpoint', '/api/sms/v2/text/multi'), [
            'messages' => $payloadMessages,
            'flash' => $flash,
            'reference' => $this->reference($reference),
        ], $this->settings());
    }

    public function checkConnection(): Response
    {
        return $this->client($this->settings())
            ->get((string) config('sms.outbound.balance_endpoint', '/api/v2/balance'))
            ->throw();
    }

    public function isConfigured(): bool
    {
        try {
            $this->settings();
        } catch (RuntimeException) {
            return false;
        }

        return true;
    }

    public function sendLoginCode(string $phone, string $code): Response
    {
        return $this->sendSingle(
            $phone,
            "Your Wakala Feedtan Store login code is {$code}. It expires in 5 minutes.",
            0,
            'login-'.Str::lower(Str::random(12)),
        );
    }

    public function normalizeRecipient(string $phone): string
    {
        $phone = preg_replace('/[\s().-]+/', '', trim($phone)) ?? trim($phone);
        $phone = ltrim($phone, '+');
        if (str_starts_with($phone, '0')) {
            $phone = '255'.substr($phone, 1);
        }

        if (preg_match('/^[1-9][0-9]{7,14}$/', $phone) !== 1) {
            throw new InvalidArgumentException('SMS recipient must be a valid phone number.');
        }

        return $phone;
    }

    private function post(string $endpoint, array $payload, array $settings): Response
    {
        return $this->client($settings)
            ->asJson()
            ->post($endpoint, $payload)
            ->throw();
    }

    private function client(array $settings): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('sms.outbound.base_url', 'https://messaging-service.co.tz'), '/'))
            ->acceptJson()
            ->withToken($settings['token'])
            ->connectTimeout((int) config('sms.outbound.connect_timeout', 5))
            ->timeout((int) config('sms.outbound.timeout', 15));
    }

    private function settings(): array
    {
        $setting = Setting::where('key', 'sms')->first();
        $value = $setting?->value;
        $token = $setting?->sms_authorization_token;

        if (! is_array($value) || blank($value['sender_id'] ?? null) || ! is_string($token) || blank($token)) {
            throw new RuntimeException('SMS settings are not configured.');
        }

        return [
            'sender_id' => trim((string) $value['sender_id']),
            'token' => $this->normalizeToken($token),
        ];
    }

    private function normalizeToken(string $token): string
    {
        $token = trim($token);
        if (preg_match('/^Bearer\s+(.+)$/i', $token, $matches) === 1) {
            return trim($matches[1]);
        }

        return $token;
    }

    private function reference(?string $reference): string
    {
        $reference = trim((string) $reference);

        return $reference !== '' ? Str::limit($reference, 64, '') : Str::lower(Str::random(12));
    }

    private function validateFlash(int $flash): void
    {
        if ($flash !== 0 && $flash !== 1) {
            throw new InvalidArgumentException('SMS flash must be 0 or 1.');
        }
    }
}
