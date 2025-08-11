<?php

namespace Modules\AuditLog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AuditLog\Models\AuditLogs;
use Modules\AuditLog\Models\ContactLogs;

class ContactLogController extends Controller
{
    use \App\Traits\ApiResponder;
    /**
     * Display a listing of the resource.
     */
    public function getAllContactLogs(Request $request)
    {        
        $result = ContactLogs::all();
        return $this->successResponse($result, 'All contact logs data', 200);
    }

    public function getContactLogDetail($contact_id)
    {
        $contact_logs = ContactLogs::where('contact_id', $contact_id)->get();
        foreach ($contact_logs as $key => $log) {
            $log->description = json_decode($log->description);
        }

        return $contact_logs;
    }

}
