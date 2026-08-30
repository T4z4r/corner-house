<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\Audit\AuditLogger;
use App\Services\Beds24\Beds24ChannelProvider;
use App\Services\Booking\BookingService;
use App\Services\Channel\ChannelManager;
use App\Services\Payment\FakePaymentGateway;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\StripePaymentGateway;
use App\Services\System\MailConfigurationService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function (): StripeClient {
            return new StripeClient((string) config('services.stripe.secret'));
        });

        $this->app->singleton(PaymentGatewayInterface::class, function ($app): PaymentGatewayInterface {
            if ($app->environment('testing') || blank(config('services.stripe.secret'))) {
                return $app->make(FakePaymentGateway::class);
            }

            return $app->make(StripePaymentGateway::class);
        });

        $this->app->singleton(ChannelManager::class, function ($app): ChannelManager {
            $manager = new ChannelManager(
                $app->make(BookingService::class),
                $app->make(AuditLogger::class),
            );
            $manager->register($app->make(Beds24ChannelProvider::class));

            return $manager;
        });
    }

    public function boot(): void
    {
        $this->app->make(MailConfigurationService::class)->apply();

        View::composer(
            ['layouts.admin.*', 'admin.*'],
            function ($view): void {
                $view->with('propertyName', Setting::getValue('property_name', config('app.name')));
            },
        );

        View::composer(
            ['layouts.website.*', 'website.*'],
            function ($view): void {
                $view->with('propertyName', Setting::getValue('property_name', config('app.name')));
            },
        );
    }
}
