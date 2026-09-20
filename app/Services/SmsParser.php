<?php

namespace App\Services;

use App\Models\Network;
use App\Services\Parsers\AirtelSmsParser;
use App\Services\Parsers\BankSmsParser;
use App\Services\Parsers\GenericSmsParser;
use App\Services\Parsers\HaloPesaSmsParser;
use App\Services\Parsers\MixxSmsParser;
use App\Services\Parsers\MpesaSmsParser;
use App\Services\Parsers\SmsParserContract;
use App\Services\Parsers\TigoSmsParser;

/**
 * Routes an incoming SMS to the parser for its provider. The sender name is
 * matched against each provider's keywords; when nothing is recognised the
 * generic parser handles it, so the pipeline never depends on a single SMS
 * format or a single operator.
 */
class SmsParser
{
    /**
     * @return array{reference: string, type: string, amount: float, customer_name: string, customer_phone: string, balance: float, received_at: string|null}
     */
    public function parse(string $body, ?string $provider = null): array
    {
        return $this->parserFor($provider)->parse($body);
    }

    /**
     * Resolve the provider (parser route) an SMS belongs to from its sender.
     */
    public function identifyProvider(string $sender): ?string
    {
        foreach (config('sms.providers', []) as $key => $provider) {
            foreach ($provider['senders'] ?? [] as $keyword) {
                if (stripos($sender, $keyword) !== false) {
                    return $key;
                }
            }
        }

        return null;
    }

    /**
     * Resolve the network an SMS belongs to from its sender name.
     */
    public function identifyNetwork(string $sender, ?Network $fallback): ?Network
    {
        foreach (config('sms.senders', []) as $keyword => $code) {
            if (stripos($sender, $keyword) !== false) {
                return Network::firstWhere('code', $code) ?? $fallback;
            }
        }

        return $fallback;
    }

    private function parserFor(?string $provider): SmsParserContract
    {
        return match ($provider) {
            'mpesa' => new MpesaSmsParser,
            'airtel' => new AirtelSmsParser,
            'tigo' => new TigoSmsParser,
            'halopesa' => new HaloPesaSmsParser,
            'mixx' => new MixxSmsParser,
            'bank' => new BankSmsParser,
            default => new GenericSmsParser,
        };
    }
}
