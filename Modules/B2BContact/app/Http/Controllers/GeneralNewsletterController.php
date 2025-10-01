<?php

namespace Modules\B2BContact\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\AuditLog\Events\AuditLogged;
use Modules\AuditLog\Events\ContactLogged;
use Modules\AuditLog\Models\AuditLogs;
use Modules\B2BContact\Helpers\FilterHelper;
use Modules\B2BContact\Models\B2BContacts;
use Modules\B2BContact\Models\B2BContactTypes;
use Modules\B2BContact\Models\ColumnMappings;
use Modules\B2BContact\Services\B2BContactService;
use Modules\B2BContact\Services\FileService;

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
        try {
            // default pagination setup
            $sort[] = ['row_no', 'asc'];
            if ($request->has('sort')) {
                $sort = [];
                $allowed_sort = ['id', 'row_no', 'contact_name', 'email', 'whatsapp_subscription', 'email_subscription', 'phone_no', 'created_date'];

                $sort_column = $request->get('sort');
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

            $baseQuery = FilterHelper::createBaseQuery($this->contact_general_newsletter->id, $request);
            $records_total = $baseQuery->count();
        } catch (\Exception $e) {
            Log::error('error get data general newsletter: ',[$e->getMessage()]);
            return $this->errorResponse('Error', 400, 'Failed to get general newsletter data. Invalid filter format.');
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

        foreach ($sort as $value) {
            $baseQuery->orderBy($value[0], $value[1]);
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
            'email' => 'nullable|email|max:255',
            'country_code' => 'nullable|string|max:255',
            'phone_no' => 'nullable|string|max:255',
            'whatsapp_subscription' => 'nullable|boolean',
            'email_subscription' => 'nullable|boolean',
        ]);
                
        try {
            DB::connection('pgsql_b2b')->beginTransaction();
            $data['contact_type_id'] = $this->contact_general_newsletter->id;
            // Create the contact
            $contact = B2BContacts::create($data);

            
            $userName = Auth::user()->user_name ?? 'cansativa';
            $userEmail = Auth::user()->email ?? 'cansativa';
            $description = [
                'log_type' => "contacts",
                'title' => "Added manually by ". $userName
            ];
            event(new ContactLogged('add_contact', 'b2b', $contact->id, null, $description, $userName, $userEmail));
            event(new AuditLogged(AuditLogs::MODULE_GENERAL_NEWSLETTER, 'Create new Contact'));
            
            DB::connection('pgsql_b2b')->commit();
            return $this->successResponse($contact,'General newsletter data added successfully',200);
        } catch (\Exception $e) {
            DB::connection('pgsql_b2b')->rollBack();
            return $this->errorResponse('Error', 400, 'Failed to add new General newsletter data');
        }
    }

    /**
     * Get general newsletter data by ID
     */

    public function generalNewsletterDataById($id)
    {
        $contact = B2BContacts::with('customFieldValues.contactField')
            ->where('id', $id)
            ->where('contact_type_id', $this->contact_general_newsletter->id)
            ->where('is_deleted', false)
            ->select('id', 'contact_name', 'email', 'phone_no', 'whatsapp_subscription', 'email_subscription')
            ->first();

        if(!$contact){
            return $this->errorResponse('Error', 404, 'General newsletter not found');
        }

        $data = $contact->toArray();
        $data['custom_field_values'] = collect($contact->customFieldValues)->map(function ($item) {
            return [
                'id'               => $item->id,
                'contact_id'       => $item->contact_id,
                'contact_field_id' => $item->contact_field_id,
                'value'            => $item->value,
                'field_name'       => $item->contactField->field_name,
                'field_type'       => $item->contactField->field_type,
                'description'      => $item->contactField->description,
            ];
        })->toArray();

        return $this->successResponse($data,'General newsletter data',200);
    }

    /**
     * update general newsletter data by ID
    */

    public function updateGeneralNewsletterDataById(Request $request, $id)
    {
        $data = $request->validate([
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'country_code' => 'nullable|string|max:255',
            'phone_no' => 'nullable|string|max:255',
            'whatsapp_subscription' => 'nullable|boolean',
            'email_subscription' => 'nullable|boolean',
        ]);

        $contact = B2BContacts::where('id', $id)
            ->where('contact_type_id', $this->contact_general_newsletter->id)
            ->where('is_deleted', false)
            ->first();

        if(!$contact){
            return $this->errorResponse('Error', 400, 'General newsletter not found');
        }

        // Fill data first
        $contact->fill($data);

        // Compare before save
        $dirty = collect($contact->getDirty())->except(['updated_date']);
        $original = $contact->getOriginal();

        // log changes
        $userName = Auth::user()->user_name ?? 'cansativa';
        $userEmail = Auth::user()->email ?? 'cansativa';

        $editPhone = false;
        $editcountryCode = false;
        foreach ($dirty as $attr => $newValue) {
            $field_name = ColumnMappings::where('field_name', $attr)->where('contact_type_id', 3)->value('display_name');
            // skip some attributes
            if ($attr == 'phone_no') {
                $editPhone = true;
                continue;
            }
            if ($attr == 'country_code') {
                $editcountryCode = true;
                continue;
            }

            $ori = $original[$attr] ?? '-';
            // If this attribute is boolean
            if (in_array($attr, ['whatsapp_subscription', 'email_subscription'])) {
                $ori = filter_var($ori, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
                $newValue = filter_var($newValue, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
            }

            $description = [
                'log_type' => "edit_contact",
                'title' => "{$field_name} edited from {$ori} to $newValue"
            ];
            event(new ContactLogged('edit_contact', 'b2b', $contact->id, null, $description, $userName, $userEmail));
        }
        
        if ($editPhone || $editcountryCode) {
            $cc = $original['country_code'] ?? null;
            $newCC = $original['country_code'] ?? null;
            if ($editcountryCode) {
                $newCC = $dirty['country_code'];
            }

            $ori = $original['phone_no'] ?? '-';
            $newValue = $original['phone_no'];
            if ($editPhone) {
                $newValue = $dirty['phone_no'];
            }

            $description = [
                'log_type' => "edit_contact",
                'title' => "Phone edited from {$cc}{$ori} to {$newCC}{$newValue}"
            ];
            event(new ContactLogged('edit_contact', 'b2b', $contact->id, null, $description, $userName, $userEmail));    
        }

        // save updated data
        $contact->save();
        event(new AuditLogged(AuditLogs::MODULE_GENERAL_NEWSLETTER, 'Edit Contact'));

        return $this->successResponse($contact->refresh(),'General newsletter data updated successfully',200);
    }

    /**
     *  Delete general newsletter data by ID
     */ 
    public function deleteGeneralNewsletterDataById($id)
    {
        $contact = B2BContacts::where('id', $id)
            ->where('is_deleted', false)
            ->where('contact_type_id', $this->contact_general_newsletter->id)
            ->first();

        if(!$contact){
            return $this->errorResponse('Error', 404, 'General newsletter not found');
        }

        $contact->delete();

        event(new AuditLogged(AuditLogs::MODULE_GENERAL_NEWSLETTER, 'Delete Contact'));

        return $this->successResponse(null,'General newsletter data deleted successfully',200);
    }
}
