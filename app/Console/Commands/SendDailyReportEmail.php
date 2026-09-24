<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\DailyOpening;
use App\Models\FloatTransaction;
use App\Models\Reconciliation;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\ExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

#[Signature('app:send-daily-report-email {--date= : Date Y-m-d for report, defaults to today}')]
#[Description('Send daily report email at 23:59 with attached PDF for the day')]
class SendDailyReportEmail extends Command
{
    public function handle(): int
    {
        $dateStr = $this->option('date') ?: today()->toDateString();
        try {
            $date = Carbon::parse($dateStr);
        } catch (\Throwable $e) {
            $this->error('Invalid date: '.$dateStr);

            return self::FAILURE;
        }

        $settings = Setting::where('key', 'email')->value('value');
        $enabled = is_array($settings) && ($settings['reports_via_email'] ?? '1') == '1';
        if (! $enabled) {
            $this->info('Reports via email disabled in settings (email.reports_via_email). Skipping.');

            return self::SUCCESS;
        }

        $recipientsRaw = $settings['reports_recipients'] ?? '';
        $recipients = array_filter(array_map('trim', explode(',', $recipientsRaw)));
        if (empty($recipients)) {
            // Fallback to contact email or from address
            $general = Setting::where('key', 'general')->value('value');
            $fallback = $general['contact_email'] ?? $settings['mail_from_address'] ?? null;
            if ($fallback && filter_var($fallback, FILTER_VALIDATE_EMAIL)) {
                $recipients = [$fallback];
            }
        }
        if (empty($recipients)) {
            $this->error('No recipients configured for daily report (settings email.reports_recipients).');

            return self::FAILURE;
        }

        // Apply DB email config to mail at runtime (like AppServiceProvider)
        try {
            if (is_array($settings) && $settings) {
                if (! empty($settings['mail_host'])) {
                    config(['mail.mailers.smtp.host' => $settings['mail_host']]);
                }
                if (! empty($settings['mail_port'])) {
                    config(['mail.mailers.smtp.port' => (int) $settings['mail_port']]);
                }
                if (array_key_exists('mail_encryption', $settings)) {
                    config(['mail.mailers.smtp.encryption' => $settings['mail_encryption'] ?: null]);
                }
                if (! empty($settings['mail_username'])) {
                    config(['mail.mailers.smtp.username' => $settings['mail_username']]);
                }
                if (array_key_exists('mail_password', $settings) && $settings['mail_password'] !== '') {
                    config(['mail.mailers.smtp.password' => $settings['mail_password']]);
                }
                if (! empty($settings['mail_from_address'])) {
                    config(['mail.from.address' => $settings['mail_from_address']]);
                }
                if (! empty($settings['mail_from_name'])) {
                    config(['mail.from.name' => $settings['mail_from_name']]);
                }
            }
        } catch (\Throwable $e) {
        }

        $agent = Agent::first() ?? Agent::query()->first();
        $cashPoint = $agent ? $agent : null;

        // Build daily data for the date
        $day = $date->copy()->startOfDay();
        $dayStr = $day->toDateString();

        $opening = $cashPoint ? DailyOpening::forAgentAndDate($cashPoint->id, $day)->first() : null;

        $transactions = Transaction::with(['network'])
            ->when($cashPoint, fn ($q) => $q->where('agent_id', $cashPoint->id))
            ->whereDate('created_at', $dayStr)
            ->where('status', 'completed')
            ->latest()
            ->get();

        $floatTransactions = FloatTransaction::with(['network'])
            ->when($cashPoint, fn ($q) => $q->where('agent_id', $cashPoint->id))
            ->whereDate('created_at', $dayStr)
            ->latest()
            ->get();

        $reconciliation = $cashPoint ? Reconciliation::where('agent_id', $cashPoint->id)->where('reconciliation_date', $dayStr)->latest()->first() : null;

        $business = app(ExportService::class)->businessInfo();

        // Generate PDF for the day
        $html = view('exports.daily-report-pdf', compact('day', 'dayStr', 'opening', 'transactions', 'floatTransactions', 'reconciliation', 'business', 'cashPoint'))->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true]);
        $pdfContent = $pdf->output();

        $subject = 'Daily Report — '.$day->format('d M Y').' — Wakala Feedtan Store';
        $filename = 'daily-report-'.$day->format('Ymd').'.pdf';

        try {
            Mail::send([], [], function ($message) use ($recipients, $subject, $pdfContent, $filename, $dayStr, $business) {
                $message->to($recipients)->subject($subject);
                $message->html('<p>Daily report for <strong>'.$dayStr.'</strong> — Wakala Feedtan Store</p><p>Attached PDF with opening, transactions, float, and reconciliation for the day.</p><p style="color:#6B5A48; font-size:12px;">'.$business['name'].' — '.$business['address'].'</p>');
                $message->attachData($pdfContent, $filename, ['mime' => 'application/pdf']);
            });

            $this->info('Daily report email sent to '.implode(', ', $recipients).' for '.$dayStr.' with PDF '.$filename);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            \Log::error('Daily report email failed', ['error' => $e->getMessage(), 'recipients' => $recipients, 'date' => $dayStr]);
            $this->error('Failed to send daily report email: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
