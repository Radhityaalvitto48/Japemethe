<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Midtrans\Config as MidtransConfig;

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
        $this->configureDefaults();
        $this->configureUrlGeneration();
        $this->configureMidtrans();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    /**
     * Configure URL generation for production domain / HTTPS.
     */
    protected function configureUrlGeneration(): void
    {
        if ((bool) env('APP_FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }
    }

    /**
     * Configure Midtrans payment gateway.
     */
    protected function configureMidtrans(): void
    {
        MidtransConfig::$serverKey = config('services.midtrans.server_key');
        MidtransConfig::$isProduction = (bool) config('services.midtrans.is_production');
        MidtransConfig::$isSanitized = (bool) config('services.midtrans.is_sanitized');
        MidtransConfig::$is3ds = (bool) config('services.midtrans.is_3ds');
    }

    /**
     * Configure request rate limits for public and sensitive endpoints.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('public-api', function (Request $request): Limit {
            return Limit::perMinute(90)
                ->by($request->ip());
        });

        RateLimiter::for('write-api', function (Request $request): Limit {
            return Limit::perMinute(30)
                ->by($request->ip());
        });

        RateLimiter::for('payment-callback', function (Request $request): Limit {
            return Limit::perMinute(60)
                ->by($request->ip());
        });

        RateLimiter::for('scan', function (Request $request): Limit {
            return Limit::perMinute(20)
                ->by($request->ip());
        });
    }
}
