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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function (): StripeClient {
            $secret = Setting::getValue('stripe_secret', config('services.stripe.secret', ''));

            return new StripeClient((string) $secret);
        });

        $this->app->singleton(PaymentGatewayInterface::class, function ($app): PaymentGatewayInterface {
            $secret = Setting::getValue('stripe_secret', config('services.stripe.secret', ''));

            if ($app->environment('testing') || blank($secret)) {
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
        Schema::defaultStringLength(191);

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
