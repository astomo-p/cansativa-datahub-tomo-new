<?php

namespace Modules\B2BContact\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\B2BContact\Helpers\FilterHelper;
use Modules\B2BContact\Models\B2BContacts;
use Modules\B2BContact\Models\B2BContactTypes;
use Modules\B2BContact\Services\B2BContactService;
use Modules\B2BContact\Services\FileService;

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
        $sort = [];
        if ($request->has('sort')) {
            // sorting example => { sort : email.asc,contact_name.asc,post_code.desc }
            $allowed_sort = ['contact_name', 'vat_id', 'post_code', 'city', 'country', 'contact_person', 'email', 
            'phone_no', 'amount_purchase','average_purchase', 'total_purchase', 'last_purchase_date', 'created_date'];

            $sort_column = explode(',', $request->get('sort'));
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

        $baseQuery = B2BContacts::where('contact_type_id', $this->contact_supplier->id)
        ->where('contacts.is_deleted', false);

        if ($request->has('applied_filters')) {
            foreach ($request->applied_filters as $key => $filter) {
                FilterHelper::getFilterQuery($baseQuery, $filter);
            }
        }
        
        try {
            $records_total = $baseQuery->count();
        } catch (\Exception $e) {
            return $this->errorResponse('Error', 400, 'Failed to get supplier data. Invalid filter column.');
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

        foreach ($results as $key => $data) {
            $data->amount_contacts = B2BContacts::where('contact_parent_id',$data->id)->count();
        }
        
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
            'amount_purchase' => 'nullable|numeric',
            'average_purchase' => 'nullable|numeric',
            'total_purchase' => 'nullable|numeric',
            'last_purchase_date' => 'nullable|date',
            'contact_person' => 'nullable|array', 
            'files' => 'nullable|array|max:3',
            'files.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $data['contact_type_id'] = $this->contact_supplier->id;
            if (isset($data['contact_person'])) {
                foreach ($data['contact_person'] as $contact_person) {
                    $data['email'] = $contact_person['email'];
                    $data['phone_no'] = $contact_person['phone_no'];
                    $data['contact_person'] = $contact_person['name'];
                    break;
                }
            }
            $new_contact = B2BContacts::create($data);

            if ($request->exists('files')) {
                // Validate the request
                $request->validate([
                    'files' => 'nullable|array|max:3',
                    'files.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
                ]);
                $path = '/uploads/contact-data';
    
                // Store to local private storage
                $this->file_service->uploadFile($new_contact->id, $request->file('files'), $path);
            }

            DB::commit();
            return $this->successResponse(null,'Supplier data added successfully',200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error', 400, 'Failed to add new supplier data');
        }
    }

    /**
     * Get supplier data by ID
    */ 
    public function supplierDataById($id)
    {
        $contact = B2BContacts::with('documents')
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
                ]
            ];
            unset($contact['email'], $contact['phone_no']);
        }

        return $this->successResponse($contact,'Supplier data',200);
    }

    /**
     * Update supplier data by ID
    */

    public function updateSupplierDataById(Request $request, $id)
    {
        $data = $request->validate([
            'contact_name' => 'required|string|max:255',
            'vat_id' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'post_code' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'amount_purchase' => 'nullable|numeric',
            'average_purchase' => 'nullable|numeric',
            'total_purchase' => 'nullable|numeric',
            'last_purchase_date' => 'nullable|date',
            'contact_person' => 'nullable|array', 
            'files' => 'nullable|array|max:3',
            'files.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $contact = B2BContacts::where('id', $id)
            ->where('is_deleted', false)
            ->where('contact_type_id', $this->contact_supplier->id)
            ->first();

        if(!$contact){
            return $this->errorResponse('Error', 404, 'Supplier not found');
        }
        
        DB::beginTransaction();
        try {
            $data['updated_date'] = now();
            if (isset($data['contact_person'])) {
                foreach ($data['contact_person'] as $contact_person) {
                    if (isset($contact_person['email'])) {
                        $data['email'] = $contact_person['email'];
                    }
                    if (isset($contact_person['phone_no'])) {
                        $data['phone_no'] = $contact_person['phone_no'];
                    }
                    if (isset($contact_person['name'])) {
                        $data['contact_person'] = $contact_person['name'];
                    }
                    break;
                }
            }
            $contact->update($data);

            if ($request->exists('files')) {
                // Validate the request
                $request->validate([
                    'files' => 'nullable|array|max:3',
                    'files.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
                ]);
                
                $path = '/uploads/contact-data';
    
                // Store to local private storage
                $uploaded_files = $this->file_service->uploadFile($contact->id, $request->file('files'), $path);
            }

            DB::commit();
            return $this->successResponse($contact->refresh(), 'Supplier data updated',200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error', 400, 'Update data failed.');
        }
    }

    /**
     * Delete supplier data by ID
     */
    public function deleteSupplierDataById($id)
    {
        $result = B2BContacts::where('id', $id)
            ->where('is_deleted', false)
            ->where('contact_type_id', $this->contact_supplier->id)
            ->first();

        if(!$result){
            return $this->errorResponse('Error',404, 'Supplier not found');
        }

        DB::beginTransaction();
        try {
            // Soft delete the contact
            $result->is_deleted = true;
            $result->save();
            DB::commit();
            return $this->successResponse(null,'Supplier data deleted successfully',200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error',500, 'Failed to delete Supplier data');
        }
    }
}
