<?php

namespace Modules\AuditLog\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ContactLogged
{
    use Dispatchable;

    public $type;
    public $contact_flag;
    public $contact_id;
    public $campaign_id;
    public $description;
    public $creator_name;
    public $creator_email;

    public function __construct($type, $contact_flag, $contact_id, $campaign_id, $description, $creator_name, $creator_email)
    {
        $this->type = $type;
        $this->contact_flag = $contact_flag;
        $this->contact_id = $contact_id;
        $this->campaign_id = $campaign_id;
        $this->description = $description;
        $this->creator_name = $creator_name;
        $this->creator_email = $creator_email;
    }
}
