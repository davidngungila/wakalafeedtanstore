<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\Transaction;
use App\Observers\TransactionObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('money', fn (string $expression) => "<?php echo money($expression); ?>");

        RateLimiter::for('device-me', fn () => Limit::perMinute(30)->by(request()->ip() ?? 'unknown'));

        Transaction::observe(TransactionObserver::class);

        // Apply email settings from DB to mail config so OTP & reports use stored SMTP
        try {
            $email = Setting::where('key', 'email')->value('value');
            if (is_array($email) && $email) {
                if (! empty($email['mail_mailer'])) {
                    config(['mail.default' => $email['mail_mailer']]);
                }
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
    }
}
