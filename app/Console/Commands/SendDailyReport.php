<?php

namespace App\Console\Commands;

use App\Mail\DailyReportMail;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailyReport extends Command
{
    protected $signature = 'reports:daily-email {--date= : Date for the report (YYYY-MM-DD). Defaults to today.}';

    protected $description = 'Email the daily report to the cash point.';

    public function handle(): int
    {
        $agent = cash_point();
        if ($agent === null || ! filter_var($agent->email ?? '', FILTER_VALIDATE_EMAIL)) {
            $this->warn('No cash point email configured. Skipping daily report.');

            return self::SUCCESS;
        }

        $notifications = Setting::where('key', 'notifications')->value('value') ?? [];
        if ((string) ($notifications['email_daily_summary'] ?? '1') !== '1') {
            $this->warn('Daily summary emails are disabled in settings.');

            return self::SUCCESS;
        }

        $date = $this->option('date') !== null ? Carbon::parse($this->option('date')) : today();

        try {
            Mail::to($agent->email)->send(new DailyReportMail($agent->id, $date));
            $this->info('Daily report emailed to '.$agent->email.' for '.$date->toDateString());
        } catch (\Throwable $e) {
            Log::error('Daily report email failed', [
                'to' => $agent->email,
                'date' => $date->toDateString(),
                'error' => $e->getMessage(),
            ]);
            $this->error('Failed to send daily report: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
