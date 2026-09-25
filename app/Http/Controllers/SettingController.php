<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->has('pane')) {
            return redirect()->route($this->legacySectionRoute($request->input('pane')));
        }

        return $this->settingsView('general');
    }

    public function commissions(): View
    {
        return $this->settingsView('commissions');
    }

    public function security(): View
    {
        return $this->settingsView('security');
    }

    public function notifications(): View
    {
        return $this->settingsView('notifications');
    }

    public function cashPoint(): View
    {
        return $this->settingsView('cash-point');
    }

    public function email(): View
    {
        return $this->settingsView('email');
    }

    public function sms(): View
    {
        return $this->settingsView('sms');
    }

    private function settingsView(string $section): View
    {
        $settings = Setting::all()->pluck('value', 'key');
        $smsSetting = Setting::where('key', 'sms')->first();
        $smsConfigured = filled($smsSetting?->sms_authorization_token);
        $agent = cash_point();

        return view('settings.index', compact('section', 'settings', 'agent', 'smsConfigured'));
    }

    private function legacySectionRoute(mixed $section): string
    {
        return match ($section) {
            'commissions' => 'settings.commissions',
            'security' => 'settings.security',
            'notifications' => 'settings.notifications',
            'cashpoint' => 'settings.cash-point',
            'email' => 'settings.email',
            'sms' => 'settings.sms',
            default => 'settings.index',
        };
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $hasSmsAuthorizationToken = Setting::where('key', 'sms')->whereNotNull('sms_authorization_token')->exists();
        $validated = $request->validate([
            'general' => ['nullable', 'array'],
            'general.business_name' => ['nullable', 'string', 'max:120'],
            'general.address' => ['nullable', 'string', 'max:255'],
            'general.contact_email' => ['nullable', 'email', 'max:120'],
            'general.contact_phone' => ['nullable', 'string', 'max:30'],
            'general.currency' => ['nullable', 'string', 'max:10'],
            'commissions' => ['nullable', 'array'],
            'security' => ['nullable', 'array'],
            'security.max_transaction_limit' => ['nullable', 'numeric', 'min:0'],
            'security.min_withdrawal_limit' => ['nullable', 'numeric', 'min:0'],
            'security.require_approval_above' => ['nullable', 'numeric', 'min:0'],
            'security.session_timeout_minutes' => ['nullable', 'integer', 'min:1'],
            'notifications' => ['nullable', 'array'],
            'email' => ['nullable', 'array'],
            'email.mail_mailer' => ['nullable', 'string', 'max:30'],
            'email.mail_host' => ['nullable', 'string', 'max:120'],
            'email.mail_port' => ['nullable', 'integer', 'min:1'],
            'email.mail_encryption' => ['nullable', 'string', 'max:10'],
            'email.mail_username' => ['nullable', 'string', 'max:120'],
            'email.mail_password' => ['nullable', 'string', 'max:255'],
            'email.mail_from_address' => ['nullable', 'email', 'max:120'],
            'email.mail_from_name' => ['nullable', 'string', 'max:120'],
            'email.otp_enabled' => ['nullable', 'in:0,1'],
            'email.otp_via_email' => ['nullable', 'in:0,1'],
            'email.reports_via_email' => ['nullable', 'in:0,1'],
            'email.reports_recipients' => ['nullable', 'string', 'max:500'],
            'sms' => ['nullable', 'array'],
            'sms.sender_id' => ['nullable', 'required_with:sms', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/'],
            'sms.authorization_token' => [
                Rule::requiredIf(fn (): bool => $request->has('sms') && ! $hasSmsAuthorizationToken),
                'nullable',
                'string',
                'max:255',
                'regex:/^(?:Bearer\s+)?\S+$/i',
            ],
        ]);

        foreach ($validated as $group => $values) {
            if (is_array($values) && $values !== []) {
                if ($group === 'sms') {
                    $token = $this->normalizeSmsToken($values['authorization_token'] ?? null);
                    unset($values['authorization_token']);

                    $attributes = ['value' => $values];
                    if ($token !== null) {
                        $attributes['sms_authorization_token'] = $token;
                    }

                    Setting::updateOrCreate(['key' => 'sms'], $attributes);

                    continue;
                }

                Setting::updateOrCreate(['key' => $group], ['value' => $values]);
            }
        }

        $this->recordAudit('Settings updated', 'Setting', null, ['groups' => array_keys(array_filter($validated, 'is_array'))]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Settings saved successfully.']);
        }

        return back()->with('status', 'Settings saved successfully.');
    }

    private function normalizeSmsToken(mixed $token): ?string
    {
        if (! is_string($token)) {
            return null;
        }

        $token = trim($token);
        if (preg_match('/^Bearer\s+(.+)$/i', $token, $matches) === 1) {
            $token = trim($matches[1]);
        }

        return $token !== '' ? $token : null;
    }

    public function showTestEmail(Request $request): View
    {
        $email = Setting::where('key', 'email')->value('value');
        $prefill = $email['mail_from_address'] ?? Setting::where('key', 'general')->value('value')['contact_email'] ?? '';
        if ($request->filled('to')) {
            $prefill = $request->input('to');
        }

        return view('settings.test-email', compact('prefill'));
    }

    public function sendTestEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'email', 'max:120'],
        ]);

        $to = $validated['to'];

        // Ensure email config is applied from DB (AppServiceProvider already does on boot, but re-apply for this request in case just saved)
        try {
            $email = Setting::where('key', 'email')->value('value');
            if (is_array($email) && $email) {
                if (! empty($email['mail_host'])) {
                    config(['mail.mailers.smtp.host' => $email['mail_host']]);
                }
                if (! empty($email['mail_port'])) {
                    config(['mail.mailers.smtp.port' => (int) $email['mail_port']]);
                }
                if (array_key_exists('mail_encryption', $email)) {
                    config(['mail.mailers.smtp.encryption' => $email['mail_encryption'] ?: null]);
                }
                if (! empty($email['mail_username'])) {
                    config(['mail.mailers.smtp.username' => $email['mail_username']]);
                }
                if (array_key_exists('mail_password', $email) && $email['mail_password'] !== '') {
                    config(['mail.mailers.smtp.password' => $email['mail_password']]);
                }
                if (! empty($email['mail_from_address'])) {
                    config(['mail.from.address' => $email['mail_from_address']]);
                }
                if (! empty($email['mail_from_name'])) {
                    config(['mail.from.name' => $email['mail_from_name']]);
                }
            }
        } catch (\Throwable) {
        }

        try {
            Mail::send('emails.test', ['to' => $to], function ($message) use ($to) {
                $message->to($to)->subject('Test Email — Wakala Feedtan Store — '.now()->format('H:i'));
            });

            $this->recordAudit('Test email sent', 'Setting', null, ['to' => $to]);

            return response()->json(['success' => true, 'message' => 'Test email sent to '.$to.' — check inbox/spam. Config is saved in database (settings email).']);
        } catch (\Throwable $e) {
            \Log::error('Test email failed', ['error' => $e->getMessage(), 'to' => $to]);

            return response()->json(['success' => false, 'message' => 'Failed to send test email: '.$e->getMessage()], 500);
        }
    }
}
