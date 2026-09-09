<?php

namespace App\Providers;

use App\Contracts\ExchangeRateProvider;
use App\Listeners\SendWebPushNotification;
use App\Services\DatosUruguayExchangeRateProvider;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ExchangeRateProvider::class, DatosUruguayExchangeRateProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureInertiaErrorPages();
        $this->configureNotifications();
    }

    protected function configureNotifications(): void
    {
        Event::listen(NotificationSent::class, SendWebPushNotification::class);
    }

    protected function configureInertiaErrorPages(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            $status = $response->statusCode();

            if (in_array($status, [403, 404], true)) {
                return $response->render('Errors/Show', [
                    'status' => $status,
                ])->withSharedData();
            }

            if (! app()->hasDebugModeEnabled() && in_array($status, [500, 503], true)) {
                return $response->render('Errors/Show', [
                    'status' => $status,
                ])->withSharedData();
            }
        });
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
            : null,
        );
    }
}
