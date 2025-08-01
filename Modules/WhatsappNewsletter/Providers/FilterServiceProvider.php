<?php

namespace Modules\WhatsappNewsletter\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\WhatsappNewsletter\Helpers\FilterValidationHelper;
use Modules\WhatsappNewsletter\Services\FilterConfigService;

class FilterServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(FilterConfigService::class, function ($app) {
            return new FilterConfigService();
        });

        $this->app->singleton(FilterValidationHelper::class, function ($app) {
            return new FilterValidationHelper(
                $app->make(FilterConfigService::class)
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            FilterConfigService::class,
            FilterValidationHelper::class,
        ];
    }
}
