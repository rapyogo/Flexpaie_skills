<?php

namespace FlexPay\Laravel;

use FlexPay\Laravel\Http\Controllers\FlexPayPaymentController;
use FlexPay\Laravel\Http\Controllers\FlexPayWebhookController;
use FlexPay\Laravel\Http\Middleware\VerifyFlexPayWebhookIp;
use FlexPay\Laravel\Services\FlexPayService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FlexPayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/flexpay.php', 'flexpay');

        $this->app->singleton(FlexPayService::class, function ($app) {
            return new FlexPayService();
        });

        $this->app->alias(FlexPayService::class, 'flexpay');
    }

    public function boot(): void
    {
        // 1. Publication de la configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/flexpay.php' => config_path('flexpay.php'),
            ], 'flexpay-config');

            // 2. Publication des migrations
            if (!class_exists('CreateFlexpayPaymentsTable')) {
                $this->publishes([
                    __DIR__ . '/../database/migrations/create_flexpay_payments_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_flexpay_payments_table.php'),
                ], 'flexpay-migrations');
            }

            // 3. Publication des vues
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/flexpay'),
            ], 'flexpay-views');
        }

        // 4. Chargement des vues Blade
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'flexpay');

        // 5. Enregistrement des routes si activées
        if (config('flexpay.routes.enabled', true)) {
            $this->registerRoutes();
        }
    }

    protected function registerRoutes(): void
    {
        $prefix = config('flexpay.routes.prefix', 'payments/flexpay');
        $webMiddleware = config('flexpay.routes.middleware', ['web']);

        Route::prefix($prefix)->group(function () use ($webMiddleware) {
            // Routes web sécurisées par session / CSRF standard
            Route::middleware($webMiddleware)->group(function () {
                Route::post('/initiate', [FlexPayPaymentController::class, 'initiate'])->name('flexpay.initiate');
                Route::post('/status', [FlexPayPaymentController::class, 'checkStatus'])->name('flexpay.status');
            });

            // Route Webhook : Filtrée par l'IP officielle FlexPay et sans CSRF
            Route::post('/callback', [FlexPayWebhookController::class, 'handle'])
                ->middleware([VerifyFlexPayWebhookIp::class])
                ->name('flexpay.callback');
        });
    }
}
