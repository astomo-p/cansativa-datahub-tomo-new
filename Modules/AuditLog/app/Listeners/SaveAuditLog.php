<?php

namespace Modules\AuditLog\Listeners;

use Modules\AuditLog\Events\AuditLogged;
use Modules\AuditLog\Models\AuditLogs;

class SaveAuditLog
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(AuditLogged $event){
        AuditLogs::create([
            'full_name' => $event->full_name,
            'email' => $event->email,
            'module' => $event->module,
            'activity' => $event->activity,
        ]);
    }
}
