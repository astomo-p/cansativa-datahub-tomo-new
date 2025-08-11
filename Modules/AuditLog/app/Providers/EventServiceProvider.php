<?php

namespace Modules\AuditLog\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Modules\AuditLog\Events\AuditLogged::class => [
            \Modules\AuditLog\Listeners\SaveAuditLog::class,
        ],
        \Modules\AuditLog\Events\ContactLogged::class => [
            \Modules\AuditLog\Listeners\SaveContactLog::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
