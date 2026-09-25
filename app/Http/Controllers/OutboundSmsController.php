<?php

namespace App\Http\Controllers;

use App\Services\SmsSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OutboundSmsController extends Controller
{
    public function single(Request $request, SmsSender $sms): JsonResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'string', 'max:30', 'regex:/^(?:\+?[1-9][0-9]{7,14}|0[0-9]{9})$/'],
            'text' => ['required', 'string', 'max:1000'],
            'flash' => ['nullable', 'integer', 'in:0,1'],
            'reference' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);

        try {
            $response = $sms->sendSingle(
                $validated['to'],
                $validated['text'],
                (int) ($validated['flash'] ?? 0),
                $validated['reference'] ?? null,
            );
        } catch (\Throwable $exception) {
            Log::error('Outbound SMS request failed.', ['exception' => $exception->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'SMS could not be sent. Check the provider response and SMS settings.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'SMS sent successfully.',
            'provider_response' => $response->json(),
        ]);
    }

    public function bulk(Request $request, SmsSender $sms): JsonResponse
    {
        $validated = $request->validate([
            'recipients' => ['required', 'string', 'max:5000'],
            'text' => ['required', 'string', 'max:1000'],
            'flash' => ['nullable', 'integer', 'in:0,1'],
            'reference' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);

        $recipients = array_values(array_unique(array_filter(
            preg_split('/[\s,;]+/', $validated['recipients']) ?: [],
            static fn (string $recipient): bool => $recipient !== '',
        )));

        $maxRecipients = max(1, (int) config('sms.outbound.max_bulk_recipients', 100));
        if ($recipients === [] || count($recipients) > $maxRecipients) {
            throw ValidationException::withMessages([
                'recipients' => "Provide between 1 and {$maxRecipients} recipients.",
            ]);
        }

        $invalidRecipient = null;
        foreach ($recipients as $recipient) {
            if (preg_match('/^(?:\+?[1-9][0-9]{7,14}|0[0-9]{9})$/', $recipient) !== 1) {
                $invalidRecipient = $recipient;
                break;
            }
        }

        if ($invalidRecipient !== null) {
            throw ValidationException::withMessages([
                'recipients' => 'Every recipient must be a valid phone number.',
            ]);
        }

        $messages = array_map(
            static fn (string $recipient): array => ['to' => $recipient, 'text' => $validated['text']],
            $recipients,
        );

        try {
            $response = $sms->sendMultiple(
                $messages,
                (int) ($validated['flash'] ?? 0),
                $validated['reference'] ?? null,
            );
        } catch (\Throwable $exception) {
            Log::error('Outbound bulk SMS request failed.', ['exception' => $exception->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk SMS could not be sent. Check the provider response and SMS settings.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk SMS sent successfully.',
            'recipient_count' => count($messages),
            'provider_response' => $response->json(),
        ]);
    }
}
