<?php

namespace Modules\B2BContact\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\B2BContact\Helpers\FilterHelper;
use Modules\B2BContact\Models\B2BContacts;
use Modules\B2BContact\Models\B2BContactTypes;
use Modules\B2BContact\Models\ContactTypes;
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
            $sort[] = ['row_no', 'asc'];
            if ($request->has('sort')) {
                $sort = [];
                $allowed_sort = ['filter_name', 'row_no', 'contact_type_id', 'contact_type', 'applied_filters', 'amount_of_contacts', 'created_date'];
                $sort_column = $request->get('sort');

                if (is_string($sort_column)) {
                    $decoded = json_decode($sort_column, true);
                    $sort_column = json_last_error() === JSON_ERROR_NONE ? $decoded : [$sort_column];
                }
                foreach ($sort_column as $key => $value) {
                    $sort[] = explode('.', $value);
                    // if sort column not included in array and not ascending or descending
                    if (!in_array($sort[$key][0], $allowed_sort) || ($sort[$key][1] !== 'asc' && $sort[$key][1] !== 'desc')) {
                        return $this->errorResponse('Error', 400, 'Failed to get saved filter data. Invalid sorting column.');
                    }
                    
                }
            }

            $start = $request->get('start', 0);
            $length = $request->get('length', 10);
            $search = $request->get('search');
            
            //  filter records if there are any filter request
            $baseQuery = SavedFilters::select(
                DB::raw('ROW_NUMBER() OVER (ORDER BY saved_filters.id DESC) as row_no'),
                'saved_filters.*',
                DB::raw('(
                    select contact_type_name
                    from contact_types
                    where contact_types.id = saved_filters.contact_type_id
                    limit 1
                ) as contact_type')
            );

            if ($request->has('applied_filters')) {
                foreach ($request->applied_filters as $key => $filter) {
                    FilterHelper::getFilterQuery($baseQuery, $filter, 'saved_filters');
                }
            }

            try {
                if ($request->has('filter_name')) {
                    $filterSearch = trim($request->filter_name);
                    $baseQuery->where('saved_filters.filter_name', 'ilike', '%'.$filterSearch.'%');
                }

                $records_total = $baseQuery->count();
                $records_filtered = $records_total;

                if($search){
                    $search = trim($search);
                    $baseQuery->where(function($query) use ($search) {
                                    $query->where('saved_filters.filter_name', 'ilike', '%'.$search.'%');
                                });
                }

                foreach ($sort as $value) {
                    if ($value[0] !== 'applied_filters') {
                        if ($value[0] == 'contact_type') {
                            $baseQuery->orderBy(
                                ContactTypes::select('contact_type_name')
                                    ->whereColumn('contact_types.id', 'saved_filters.contact_type_id'),
                                $value[1]
                            );
                        }else{
                            $baseQuery->orderBy($value[0], $value[1]);
                        }
                    }
                }

                // paginate records
                $results = $baseQuery 
                ->take($length)
                ->skip($start)
                ->get();
            } catch (\Exception $e) {
                Log::error('Failed to get saved filter data: ',[$e->getMessage()]);
                return $this->errorResponse('Error', 400, 'Failed to get saved filter data.');
            }

            $mapContact = [
                1 => 'Pharmacy',
                2 => 'Supplier',
                3 => 'General Newsletter',
                4 => 'Community',
                5 => 'Pharmacy Database',
            ];
            // manipulate records
            foreach ($results as $key => $data) {
                $data['applied_filters'] = json_decode($data['applied_filters']);
                $data['contact_type'] = ucwords(strtolower($data['contact_type']));
                if ($data['frequency_cap']) {
                    $data['frequency_cap'] = json_decode($data['frequency_cap']);
                }
                if ($data['newsletter_channel']) {
                    $data['newsletter_channel'] = json_decode($data['newsletter_channel']);
                }
                
                $data['contact_type'] = $mapContact[$data['contact_type_id']];
            }

            foreach ($sort as $value) {
                if ($value[0] == 'applied_filters') {
                    if (strtolower($value[1]) == 'asc') {
                        $results = $results->sortBy(function ($item) {
                            $filters = $item->applied_filters ?? '[]';
                            return $filters[0]->key ?? '';
                        })->values();
                    }else{
                        $results = $results->sortByDesc(function ($item) {
                            $filters = $item->applied_filters ?? '[]';
                            return $filters[0]->key ?? '';
                        })->values();

                    }
                }
            }

            $data = [
                'recordsTotal' => $records_total,
                'recordsFiltered' => $records_filtered,
                'data' => $results
            ];

            return $this->successResponse($data, 'All saved filter retrieved', 200);  
        } catch (\Exception $e) {
            Log::error('Failed retrieve filter: ',[$e->getMessage()]);
            return $this->errorResponse('Failed to retrieve saved filter ', 400);  
        }
    }

    public function getAllFilter(Request $request)
    {
        $data = $request->validate([
            'contact_type' => 'required|in:pharmacy,supplier,general-newsletter'
        ]);

        $contact = $data['contact_type'];
        $contact_type = [
            'pharmacy'=>$this->contact_pharmacy->id,
            'supplier'=>$this->contact_supplier->id,
            'general-newsletter'=>$this->contact_general_newsletter->id
        ];

        try {
            $querySavedFilter = SavedFilters::where('is_deleted', false)
                ->where('contact_type_id', $contact_type[$contact])
                ->select('id', 'filter_name', 'applied_filters');

            if ($request->has('search')) {
                $querySavedFilter->where('filter_name', 'ilike', '%'.$request->search.'%');
            }

            $results = $querySavedFilter->get();
                
            foreach ($results as $key => $data) {
                $data['applied_filters'] = json_decode($data['applied_filters']);
            }

            return $this->successResponse($results, 'Filter detail retrieved', 200);  
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve filter', 400);  
        }
    }

    public function getFilterDetail($id)
    {
        try {
            $result = SavedFilters::with('contactType')
            ->where('is_deleted', false)
            ->find($id);
            $result->applied_filters = json_decode($result->applied_filters);
            if ($result->frequence_cap) {
                $result->frequence_cap = json_decode($result->frequence_cap);
            }
            if ($result->newsletter_channel) {
                $result->newsletter_channel = json_decode($result->newsletter_channel);
            }

            return $this->successResponse($result, 'Filter detail retrieved', 200);  
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve filter', 400);  
        }
    }

    public function saveNewFilter(Request $request)
    {
        $data = $request->validate([
            'filter_name' => 'required|string|max:255',
            'applied_filters' => 'nullable',
            'contact_type' => 'required|in:pharmacy,supplier,general-newsletter',
            'is_frequence' => 'nullable|boolean',
            'is_apply_freq' => 'nullable|boolean',
            'frequence_cap' => 'nullable',
            'newsletter_channel' => 'nullable',
        ]);

        $contact = $data['contact_type'];
        $contact_type = [
            'pharmacy'=>$this->contact_pharmacy->id,
            'supplier'=>$this->contact_supplier->id,
            'general-newsletter'=>$this->contact_general_newsletter->id
        ];

        $baseQuery = B2BContacts::where('contact_type_id', $contact_type[$contact])
        ->where('contacts.is_deleted', false);
        
        if ($request->filled('applied_filters') && $request->applied_filters != '-') {
            foreach ($request->applied_filters as $key => $filter) {
                FilterHelper::getFilterQuery($baseQuery, $filter);
            }
        }

        try {
            $amount_of_contacts = $baseQuery->count();
        } catch (\Exception $e) {
            return $this->errorResponse('Error', 400, 'Failed to save filter. Invalid applied filters.');
        }

        try {
            $data['contact_type_id'] = $contact_type[$contact];
            $data['amount_of_contacts'] = $amount_of_contacts;
            if (isset($data['applied_filters']) && $data['applied_filters'] != '-') {
                $data['applied_filters'] = json_encode($data['applied_filters']);
            }else{
                $data['applied_filters'] = null;
            }
            if (isset($data['frequency_cap'])) {
                $data['frequency_cap'] = json_encode($data['frequency_cap']);
            }
            if (isset($data['newsletter_channel'])) {
                $data['newsletter_channel'] = json_encode($data['newsletter_channel']);
            }

            $result = SavedFilters::create($data);

            return $this->successResponse($result, 'New filter saved', 200);  
        } catch (\Exception $e) {
            Log::error("Failed to save new filter", [$e->getMessage()]);
            return $this->errorResponse('Failed to save new filter', 400);  
        }
    }

    public function updateFilter(Request $request, $id)
    {
        $data = $request->validate([
            'filter_name' => 'nullable|string|max:255',
            'applied_filters' => 'nullable',
            'is_frequence' => 'nullable|boolean',
            'is_apply_freq' => 'nullable|boolean',
            'frequency_cap' => 'nullable',
            'newsletter_channel' => 'nullable',
        ]);

        $saved_fitler = SavedFilters::where('is_deleted', false)->find($id);

        try {
            if (isset($data['applied_filters'])) {
                $baseQuery = B2BContacts::where('contact_type_id', $saved_fitler->contact_type_id)
                ->where('contacts.is_deleted', false);
                
                if ($request->filled('applied_filters') && $request->applied_filters != '-') {
                    foreach ($request->applied_filters as $key => $filter) {
                        FilterHelper::getFilterQuery($baseQuery, $filter);
                    }
                }

                $amount_of_contacts = $baseQuery->count();
                $data['amount_of_contacts'] = $amount_of_contacts;
                if (isset($data['applied_filters']) && $data['applied_filters'] != '-') {
                    $data['applied_filters'] = json_encode($data['applied_filters']);
                }else{
                    $data['applied_filters'] = null;
                }
            }

            if (isset($data['frequency_cap'])) {
                $data['frequency_cap'] = json_encode($data['frequency_cap']);
            }
            if (isset($data['newsletter_channel'])) {
                $data['newsletter_channel'] = json_encode($data['newsletter_channel']);
            }

            $saved_fitler->update($data);

            return $this->successResponse($saved_fitler->refresh(), 'The Filter has been edited', 200);  
        } catch (\Exception $e) {
            Log::error('Update saved filter', [$e->getMessage()]);
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

    private function filterQuery($key, $column, $query, $filter)
    {
        if ($key == 'amount') {
            if(isset($filter['min'], $filter['max'])){
                return $query->whereBetween($column, [$filter['min'], $filter['max']]);
            }

            if (isset($filter['min'])) {
                return $query->where($column, '>', $filter['min']);
            }

            if (isset($filter['max'])) {
                return $query->where($column, '<', $filter['max']);
            }
        }

        if ($key == 'creation') {
            if(isset($filter['startDate'], $filter['endDate'])){
                return $query->whereBetween($column, [$filter['startDate'], $filter['endDate']]);
            }

            if (isset($filter['startDate'])) {
                return $query->where($column, '>', $filter['startDate']);
            }

            if (isset($filter['endDate'])) {
                return $query->where($column, '<', $filter['endDate']);
            }
        }
    }
}
