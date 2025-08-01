<?php

namespace Modules\B2BContact\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\AuditLog\Events\AuditLogged;
use Modules\AuditLog\Events\ContactLogged;
use Modules\AuditLog\Models\AuditLogs;
use Modules\B2BContact\Helpers\FilterHelper;
use Modules\B2BContact\Models\B2BContacts;
use Modules\B2BContact\Models\B2BContactTypes;
use Modules\B2BContact\Services\B2BContactService;
use Modules\B2BContact\Services\FileService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class GeneralNewsletterController extends Controller
{
    use \App\Traits\ApiResponder;

    /**
     * list of contact type names 
     */

    private $contact_general_newsletter = null;
    protected $file_service;
    protected $contact_service;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->contact_general_newsletter = B2BContactTypes::where('contact_type_name', 'GENERAL NEWSLETTER')->first();
        $this->file_service = new FileService;
        $this->contact_service = new B2BContactService;
    }
    
    /**
     * get all general newsletter data
     */
     
    public function allGeneralNewsletterData(Request $request)
    {
        // default pagination setup
        $sort = [];
        if ($request->has('sort')) {
            // sorting example => { sort : email.asc,contact_name.asc,post_code.desc }
            $allowed_sort = ['contact_name', 'email', 'whatsapp_subscription', 'email_subscription', 'phone_no', 'created_date'];

            $sort_column = explode(',', $request->get('sort'));
            foreach ($sort_column as $key => $value) {
                $sort[] = explode('.', $value);
                // if sort column not included in array and not ascending or descending
                if (!in_array($sort[$key][0], $allowed_sort) || ($sort[$key][1] !== 'asc' && $sort[$key][1] !== 'desc')) {
                    return $this->errorResponse('Error', 400, 'Failed to get pharmacy data. Invalid sorting column.');
                }
            }
        }
        
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $search = $request->get('search');

        $baseQuery = B2BContacts::where('contact_type_id', $this->contact_general_newsletter->id)
        ->where('contacts.is_deleted', false);

        if ($request->has('applied_filters')) {
            foreach ($request->applied_filters as $key => $filter) {
                FilterHelper::getFilterQuery($baseQuery, $filter);
            }
        }

        try {
            $records_total = $baseQuery->count();
        } catch (\Exception $e) {
            return $this->errorResponse('Error', 400, 'Failed to get general newsletter data. Invalid filter column.');
        }

        $records_filtered = $records_total;
        if($search){
            $search = trim($search);
            $baseQuery->where(function($query) use ($search) {
                            $query->where('contacts.contact_name', 'ilike', '%'.$search.'%')
                                ->orWhere('contacts.contact_no', 'ilike', '%'.$search.'%')
                                ->orWhere('contacts.email', 'ilike', '%'.$search.'%');
                        });
        }

        if (isset($sort)) {
            foreach ($sort as $value) {
                $baseQuery->orderBy($value[0], $value[1]);
            }
        }

        $records_filtered = $baseQuery->count();
        
        $results = $baseQuery 
        ->take($length)
        ->skip($start)
        ->get();

        $res = [
            'recordsTotal' => $records_total,
            'recordsFiltered' => $records_filtered,
            'data' => $results
        ];
        
        return $this->successResponse($res,'All general newsletter data',200);
    }

    /** 
     * add general newsletter data
    */
    public function addGeneralNewsletterData(Request $request)
    {
        $data = $request->validate([
            'contact_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_no' => 'required|string|max:255',
            'whatsapp_subscription' => 'required|boolean',
            'email_subscription' => 'required|boolean',
        ]);
                
        DB::beginTransaction();
        try {
            $data['contact_type_id'] = $this->contact_general_newsletter->id;
            // Create the contact
            $contact = B2BContacts::create($data);

            $description['title'] = "Added manually by user_name";
            event(new ContactLogged('general-newsletter', 'b2b', $contact->id, null, $description, 'user_name', 'user_email'));
            event(new AuditLogged(AuditLogs::MODULE_GENERAL_NEWSLETTER, 'Create General Newsletter Contacts'));
            
            DB::commit();
            return $this->successResponse(null,'General newsletter data added successfully',200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error', 400, 'Failed to add new General newsletter data');
        }
    }

    /**
     * Get general newsletter data by ID
     */

    public function generalNewsletterDataById($id)
    {
        $result = B2BContacts::where('id', $id)
            ->where('contact_type_id', $this->contact_general_newsletter->id)
            ->where('is_deleted', false)
            ->select('id', 'contact_name', 'email', 'phone_no', 'whatsapp_subscription', 'email_subscription')
            ->first();

        if(!$result){
            return $this->errorResponse('Error', 404, 'General newsletter not found');
        }
       return $this->successResponse($result,'General newsletter data',200);
    }

    /**
     * update general newsletter data by ID
    */

    public function updateGeneralNewsletterDataById(Request $request, $id)
    {
        $data = $request->validate([
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone_no' => 'nullable|string|max:255',
            'whatsapp_subscription' => 'nullable|boolean',
            'email_subscription' => 'nullable|boolean',
        ]);

        $result = B2BContacts::where('id', $id)
            ->where('contact_type_id', $this->contact_general_newsletter->id)
            ->where('is_deleted', false)
            ->first();

        if(!$result){
            return $this->errorResponse('Error', 400, 'General newsletter not found');
        }

        // Update the contact
        $result->update($data);

        $description['title'] = "Updated by user_name";
        event(new ContactLogged('general-newsletter', 'b2b', $id, null, $description, 'user_name', 'user_email'));
        event(new AuditLogged(AuditLogs::MODULE_GENERAL_NEWSLETTER, 'Update General Newsletter Contacts'));

        return $this->successResponse($result->refresh(),'General newsletter data updated successfully',200);
    }

    /**
     *  Delete general newsletter data by ID
     */ 
    public function deleteGeneralNewsletterDataById($id)
    {
        $result = B2BContacts::where('id', $id)
            ->where('is_deleted', false)
            ->where('contact_type_id', $this->contact_general_newsletter->id)
            ->first();

        if(!$result){
            return $this->errorResponse('Error', 404, 'General newsletter not found');
        }

        // Soft delete the contact
        $result->is_deleted = true;
        $result->save();

        event(new AuditLogged(AuditLogs::MODULE_GENERAL_NEWSLETTER, 'Delete General Newsletter Contacts'));

        return $this->successResponse(null,'General newsletter data deleted successfully',200);
    }
}
