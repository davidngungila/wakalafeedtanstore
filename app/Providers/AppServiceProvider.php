<?php

namespace App\Providers;

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
    }
}
