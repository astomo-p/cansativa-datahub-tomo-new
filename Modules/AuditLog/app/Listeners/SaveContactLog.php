<?php

namespace Modules\AuditLog\Listeners;

use Modules\AuditLog\Events\ContactLogged;
use Modules\AuditLog\Models\ContactLogs;

class SaveContactLog
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(ContactLogged $event)
    {
        ContactLogs::create([
            'type' => $event->type,
            'contact_flag' => $event->contact_flag,
            'contact_id' => $event->contact_id,
            'campaign_id' => $event->campaign_id,
            'description' => json_encode($event->description),
            'creator_name' => $event->creator_name,
            'creator_email' => $event->creator_email
        ]);
    }
}
