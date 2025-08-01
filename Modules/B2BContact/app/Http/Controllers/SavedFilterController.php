<?php

namespace Modules\B2BContact\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\B2BContact\Helpers\FilterHelper;
use Modules\B2BContact\Models\B2BContactTypes;
use Modules\B2BContact\Models\SavedFilters;

class SavedFilterController extends Controller
{
    private $contact_pharmacy = null;
    private $contact_supplier = null;
    private $contact_general_newsletter = null;
    
    use \App\Traits\ApiResponder;
    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        $this->contact_pharmacy = B2BContactTypes::where('contact_type_name', 'PHARMACY')->first();
        $this->contact_supplier = B2BContactTypes::where('contact_type_name', 'SUPPLIER')->first();
        $this->contact_general_newsletter = B2BContactTypes::where('contact_type_name', 'GENERAL NEWSLETTER')->first();
    }

    public function getFilterTableList(Request $request)
    {
        try {
            // default pagination setup
            $sort = [];
            if ($request->has('sort')) {
                // sorting example => { sort : filter_name.asc,amount_of_contacts.asc,created_date.desc }
                $allowed_sort = ['filter_name', 'contact_type_name', 'applied_filters', 'amount_of_contacts', 'created_date'];

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
            
            //  filter records if there are any filter request
            $baseQuery = SavedFilters::join('contact_types as ct', 'ct.id', 'saved_filters.contact_type_id')
                                ->select('saved_filters.id', 'saved_filters.filter_name', 'saved_filters.applied_filters', 'saved_filters.amount_of_contacts',
                                'saved_filters.created_date', 'ct.contact_type_name');

            if ($request->has('applied_filters')) {
                foreach ($request->applied_filters as $key => $filter) {
                    FilterHelper::getFilterQuery($baseQuery, $filter);
                }
            }
            
            try {
                $records_total = $baseQuery->count();
            } catch (\Exception $e) {
                return $this->errorResponse('Error', 400, 'Failed to get pharmacy data. Invalid filter column.'. $e->getMessage());
            }
            
            $records_filtered = $records_total;
            if($search){
                $search = trim($search);
                $baseQuery->where(function($query) use ($search) {
                                $query->where('saved_filters.filter_name', 'ilike', '%'.$search.'%');
                            });
            }

            if (isset($sort)) {
                foreach ($sort as $value) {
                    $baseQuery->orderBy($value[0], $value[1]);
                }
            }

            // paginate records
            $results = $baseQuery 
            ->take($length)
            ->skip($start)
            ->get();

            // manipulate records
            foreach ($results as $key => $data) {
                $data['applied_filters'] = json_decode($data['applied_filters']);
            }

            $data = [
                'recordsTotal' => $records_total,
                'recordsFiltered' => $records_filtered,
                'data' => $results
            ];

            return $this->successResponse($data, 'All saved filter retrieved', 200);  
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrive saved filter', 400);  
        }
    }

    public function getAllFilter()
    {
        try {
            $results = SavedFilters::select('id', 'filter_name', 'applied_filters')->get();
            foreach ($results as $key => $data) {
                $data['applied_filters'] = json_decode($data['applied_filters']);
            }

            return $this->successResponse($results, 'Filter detail retrieved', 200);  
        } catch (\Exception $e) {
            dd($e->getMessage());
            return $this->errorResponse('Failed to retrive filter', 400);  
        }
    }

    public function getFilterDetail($id)
    {
        try {
            $result = SavedFilters::find($id);
            $result->applied_filters = json_decode($result->applied_filters);

            return $this->successResponse($result, 'Filter detail retrieved', 200);  
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrive filter', 400);  
        }
    }

    public function saveNewFilter(Request $request)
    {
        $data = $request->validate([
            'filter_name' => 'required|string|max:255',
            'applied_filters' => 'required',
            'amount_of_contacts' => 'required|numeric',
            'contact_type' => 'required|in:pharmacy,supplier,general-newsletter'
        ]);

        $contact = $data['contact_type'];
        $contact_type = [
            'pharmacy'=>$this->contact_pharmacy->id,
            'supplier'=>$this->contact_supplier->id,
            'general-newsletter'=>$this->contact_general_newsletter->id
        ];

        try {
            $data['contact_type_id'] = $contact_type[$contact];
            $data['applied_filters'] = json_encode($request->applied_filters);

            $result = SavedFilters::create($data);

            return $this->successResponse($result, 'New filter saved', 200);  
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to save new filter', 400);  
        }
    }

    public function updateFilter(Request $request, $id)
    {
        $data = $request->validate([
            'filter_name' => 'nullable|string|max:255',
            'applied_filters' => 'nullable'
        ]);

        try {
            if (isset($data['applied_filters'])) {
                $data['applied_filters'] = json_encode($data['applied_filters']);
            }

            SavedFilters::find($id)->update($data);

            return $this->successResponse(null, 'The Filter has been edited', 200);  
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update filter', 400);  
        }
    }

    public function renameFilter(Request $request, $id)
    {
        $data = $request->validate([
            'filter_name' => 'required|string|max:255',
        ]);

        try {
            $result = SavedFilters::find($id);
            $result->filter_name = $data['filter_name'];
            $data['updated_by'] = 100;

            return $this->successResponse(null, 'The Filter has been renamed', 200);  
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to renamed filter', 400);  
        }
    }

    public function deleteFilter($id)
    {
        try {
            $result = SavedFilters::where('id', $id)
                ->where('is_deleted', false)
                ->first();
    
            if(!$result){
                return $this->errorResponse('Error', 400, 'Filter not found');
            }
    
            // Soft delete the contact
            $result->is_deleted = true;
            $result->save();

            return $this->successResponse(null, 'The Filter has been deleted', 200);  
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete filter', 400);  
        }
    }
}
