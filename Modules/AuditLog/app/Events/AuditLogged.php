<?php

namespace Modules\AuditLog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\Auth;

class AuditLogged
{
    use Dispatchable;

    public $full_name;
    public $email;
    public $module;
    public $activity;

    public function __construct($module, $activity)
    {
        $this->full_name = Auth::user()->user_name ?? 'cansativa';
        $this->email = Auth::user()->email ?? 'cansativa';
        $this->module = $module;
        $this->activity = $activity;
    }
}
