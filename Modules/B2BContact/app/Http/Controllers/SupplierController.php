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
use Modules\B2BContact\Models\B2BFiles;
use Modules\B2BContact\Models\ColumnMappings;
use Modules\B2BContact\Services\B2BContactService;
use Modules\B2BContact\Services\FileService;
use Modules\B2BContactAdjustment\Http\Controllers\B2BContactAdjustmentController;

class SupplierController extends Controller
{
    use \App\Traits\ApiResponder;

    /**
     * list of contact type names 
     */

    private $contact_supplier = null;
    protected $file_service;
    protected $contact_service;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->contact_supplier = B2BContactTypes::where('contact_type_name', 'SUPPLIER')->first();
        $this->file_service = new FileService;
        $this->contact_service = new B2BContactService;
    }

    /** 
     * Get all supplier data.
     */
    public function allSupplierData(Request $request)
    {
        try {
            $sort[] = ['row_no', 'asc'];
            if ($request->has('sort')) {
                $sort = [];
                $allowed_sort = ['id', 'row_no', 'contact_name', 'vat_id', 'post_code', 'city', 'country', 'contact_person', 'email', 'address',
                'phone_no', 'amount_purchase','average_purchase', 'total_purchase', 'last_purchase_date', 'created_date', 'whatsapp_subscription', 'email_subscription'];

                $sort_column = $request->get('sort');
                foreach ($sort_column as $key => $value) {
                    $sort[] = explode('.', $value);
                    // if sort column not included in array and not ascending or descending
                    if (!in_array($sort[$key][0], $allowed_sort) || ($sort[$key][1] !== 'asc' && $sort[$key][1] !== 'desc')) {
                        return $this->errorResponse('Error', 400, 'Failed to get supplier data. Invalid sorting column.');
                    }
                }
            }

            $start = $request->get('start', 0);
            $length = $request->get('length', 10);
            $search = $request->get('search');

            $baseQuery = FilterHelper::createBaseQuery($this->contact_supplier->id, $request);
            $records_total = $baseQuery->count();
        } catch (\Exception $e) {
            Log::error('Get Supplier Data: ', [$e->getMessage()]);
            return $this->errorResponse('Error', 400, 'Failed to get supplier data. Invalid filter format.');
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
       
       return $this->successResponse($res,'All supplier data',200);
    }
    
    /**
     * Add supplier data
     */

    public function addSupplierData(Request $request)
    {
        $data = $request->validate([
            'contact_name' => 'required|string|max:255',
            'vat_id' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'post_code' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'amount_purchase' => 'nullable',
            'average_purchase' => 'nullable',
            'total_purchase' => 'nullable',
            'last_purchase_date' => 'nullable|date',
            'documents' => 'nullable|array',
            'contact_person' => 'nullable|array', 
            'contact_person.*' => 'nullable', 
            'contact_person.*.whatsapp_subscription' => 'nullable|boolean',
            'contact_person.*.email_subscription' => 'nullable|boolean',
        ]);

        try {
            DB::connection('pgsql_b2b')->beginTransaction();
            $data['contact_type_id'] = $this->contact_supplier->id;
            if (isset($data['contact_person'])) {
                foreach ($data['contact_person'] as $contact_person) {
                    $data['email'] = $contact_person['email'] ?? null;
                    $data['phone_no'] = $contact_person['phone_no'] ?? null;
                    $data['contact_person'] = $contact_person['name'] ?? null;
                    $data['country_code'] = $contact_person['country_code'] ?? null;
                    $data['whatsapp_subscription'] = $contact_person['whatsapp_subscription'] ?? false;
                    $data['email_subscription'] = $contact_person['email_subscription'] ?? false;
                    break;
                }
            }
            $new_contact = B2BContacts::create($data);

            if ($request->has('documents')) {
                foreach ($data['documents'] as $key => $file) {
                    $file_data = B2BFiles::where('file_path', $file['file_path'])->first();
                    if (!$file_data) {
                        throw new \Exception('File not found.');
                    }
                    B2BFiles::where('id', $file_data->id)->update(['contact_id' => $new_contact->id]);
                }
            }

            $userName = Auth::user()->user_name ?? 'cansativa';
            $userEmail = Auth::user()->email ?? 'cansativa';
            $description = [
                'log_type' => "contacts",
                'title' => "Added manually by ". $userName
            ];
            event(new ContactLogged('add_contact', 'b2b', $new_contact->id, null, $description, $userName, $userEmail));
            event(new AuditLogged(AuditLogs::MODULE_SUPPLIER, 'Create new Contact'));

            DB::connection('pgsql_b2b')->commit();
            return $this->successResponse($new_contact,'Supplier data added successfully',200);
        } catch (\Throwable $e) {
            DB::connection('pgsql_b2b')->rollBack();
            Log::error($e->getMessage());
            return $this->errorResponse('Error', 400, 'Failed to add new supplier data');
        }
    }

    /**
     * Get supplier data by ID
    */ 
    public function supplierDataById($id)
    {
        $contact = B2BContacts::with('documents', 'customFieldValues.contactField')
            ->where('id', $id)
            ->where('contact_type_id', $this->contact_supplier->id)
            ->where('is_deleted', false)
            ->first();

        if(!$contact){
            return $this->errorResponse('Error', 404, 'Supplier not found');
        }

        if ($contact['contact_person']) {
            $contact['contact_person'] = [
                [
                    'name' => $contact->contact_person,
                    'email' => $contact->email,
                    'phone_no' => $contact->phone_no,
                    'country_code' => $contact->country_code,
                    'whatsapp_subscription' => $contact->whatsapp_subscription,
                    'email_subscription' => $contact->email_subscription,
                ]
            ];
            unset($contact['email'], $contact['phone_no'], $contact['country_code']);
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

        return $this->successResponse($data,'Supplier data',200);
    }

    /**
     * Update supplier data by ID
    */

    public function updateSupplierDataById(Request $request, $id)
    {
        $data = $request->validate([
            'contact_name' => 'nullable|string|max:255',
            'vat_id' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'post_code' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'amount_purchase' => 'nullable',
            'average_purchase' => 'nullable',
            'total_purchase' => 'nullable',
            'last_purchase_date' => 'nullable|date',
            'documents' => 'nullable|array',
            'contact_person' => 'nullable|array', 
            'contact_person.*' => 'nullable', 
            'contact_person.*.whatsapp_subscription' => 'nullable|boolean',
            'contact_person.*.email_subscription' => 'nullable|boolean',
        ]);

        $contact = B2BContacts::where('id', $id)
            ->where('is_deleted', false)
            ->where('contact_type_id', $this->contact_supplier->id)
            ->first();

        if(!$contact){
            return $this->errorResponse('Error', 404, 'Supplier not found');
        }
        
        try {
            DB::connection('pgsql_b2b')->beginTransaction();
            $data['updated_date'] = now();
            if (isset($data['contact_person'])) {
                foreach ($data['contact_person'] as $contact_person) {
                    if (isset($contact_person['email'])) {
                        $data['email'] = $contact_person['email'];
                    }
                    if (isset($contact_person['phone_no'])) {
                        $data['phone_no'] = $contact_person['phone_no'];
                    }
                    if (isset($contact_person['country_code'])) {
                        $data['country_code'] = $contact_person['country_code'];
                    }
                    if (isset($contact_person['whatsapp_subscription'])) {
                        $data['whatsapp_subscription'] = $contact_person['whatsapp_subscription'];
                    }
                    if (isset($contact_person['email_subscription'])) {
                        $data['email_subscription'] = $contact_person['email_subscription'];
                    }
                    if (isset($contact_person['name'])) {
                        $data['contact_person'] = $contact_person['name'];
                    }else{
                        unset($data['contact_person']);
                    }
                    break;
                }
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
                $field_name = ColumnMappings::where('field_name', $attr)->where('contact_type_id', 2)->value('display_name');
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
            $contact->refresh();

            if ($request->has('documents')) {
                foreach ($data['documents'] as $key => $file) {
                    $file_data = B2BFiles::where('file_path', $file['file_path'])->first();
                    if (!$file_data) {
                        throw new \Exception('File not found.');
                    }
                    B2BFiles::where('id', $file_data->id)->update(['contact_id' => $id]);
                }
            }

            DB::connection('pgsql_b2b')->commit();
            event(new AuditLogged(AuditLogs::MODULE_SUPPLIER, 'Edit Contact'));

            return $this->successResponse($contact, 'Supplier data updated',200);
        } catch (\Exception $e) {
            DB::connection('pgsql_b2b')->rollBack();
            return $this->errorResponse('Error', 400, 'Update data failed.');
        }
    }

    /**
     * Delete supplier data by ID
     */
    public function deleteSupplierDataById($id)
    {
        $contact = B2BContacts::where('id', $id)
            ->where('is_deleted', false)
            ->where('contact_type_id', $this->contact_supplier->id)
            ->first();

        if(!$contact){
            return $this->errorResponse('Error',404, 'Supplier not found');
        }

        try {
            DB::connection('pgsql_b2b')->beginTransaction();
            $contact->delete();
            DB::connection('pgsql_b2b')->commit();
            event(new AuditLogged(AuditLogs::MODULE_SUPPLIER, 'Delete Contact'));

            return $this->successResponse(null,'Supplier data deleted successfully',200);
        } catch (\Exception $e) {
            DB::connection('pgsql_b2b')->rollBack();
            return $this->errorResponse('Error',500, 'Failed to delete Supplier data');
        }
    }
}
