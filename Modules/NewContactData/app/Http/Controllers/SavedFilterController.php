<?php

namespace Modules\NewContactData\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\B2BContact\Helpers\FilterHelper;
use Modules\NewContactData\Models\Contacts;
use Modules\NewContactData\Models\ContactTypes;
use Modules\NewContactData\Models\SavedFilters;

class SavedFilterController extends Controller
{
    private $contact_pharmacy = 0;
    private $contact_supplier = 0;
    private $contact_community = 0;
    private $contact_general_newsletter = 0;
    private $contact_pharmacy_db = 0;
    private $contact_subscriber = 0;

    use \App\Traits\ApiResponder;
    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
         //$this->contact_pharmacy = B2BContactTypes::where('contact_type_name', 'PHARMACY')->first();
        //$this->contact_supplier = B2BContactTypes::where('contact_type_name', 'SUPPLIER')->first();
        $this->contact_community = ContactTypes::where('contact_type_name', 'COMMUNITY')->first();
        //$this->contact_general_newsletter = B2BContactTypes::where('contact_type_name', 'GENERAL NEWSLETTER')->first();
        $this->contact_pharmacy_db = ContactTypes::where('contact_type_name', 'PHARMACY DATABASE')->first();
        $this->contact_subscriber = ContactTypes::where('contact_type_name', 'SUBSCRIBER')->first();
   }

    public function getFilterTableList(Request $request)
    {
        try {
            // default pagination setup
            $sort = [];
            if ($request->has('sort')) {
                // sorting example => { sort : filter_name.asc,amount_of_contacts.asc,created_date.desc }
                $allowed_sort = ['filter_name', 'contact_type', 'applied_filters', 'amount_of_contacts', 'created_date'];
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
            
            //  filter records if there are any filter request
            $baseQuery = SavedFilters::join('contact_types as ct', 'ct.id', 'saved_filters.contact_type_id')
                            ->select('saved_filters.*', 'saved_filters.created_date as created_date', 'ct.contact_type_name as contact_type')
                            ->where('saved_filters.is_deleted', false);
            
                            if ($request->has('applied_filters')) {
                                foreach ($request->applied_filters as $key => $filter) {
                                    FilterHelper::getFilterQuery($baseQuery, $filter, 'saved_filters');
                                }
                            }

            try {
                $records_total = $baseQuery->count();
                $records_filtered = $records_total;

                if($search){
                    $search = trim($search);
                    $baseQuery->where(function($query) use ($search) {
                                    $query->where('saved_filters.filter_name', 'ilike', '%'.$search.'%');
                                });
                }

                if ($request->has('sort')) {
                    foreach ($sort as $value) {
                        if ($value[0] !== 'applied_filters') {
                            $baseQuery->orderBy($value[0], $value[1]);
                        }
                    }
                }else{
                    $baseQuery->orderBy('saved_filters.id', 'desc');
                }

                // paginate records
                $results = $baseQuery 
                ->take($length)
                ->skip($start)
                ->get();
            } catch (\Exception $e) {
                Log::error('invalid filter format: ',[$e->getMessage()]);
                return $this->errorResponse('Error', 400, 'Failed to get saved filter data. Invalid filter format. ');
            }

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
            'contact_type' => 'required|in:pharmacy-database,community'
        ]);

        $contact = $data['contact_type'];
        $contact_type = [
            'pharmacy_database'=>$this->contact_pharmacy_db->id, 
            'community'=>$this->contact_general_newsletter->id
        ];

        try {
            $results = SavedFilters::where('is_deleted', false)
                ->where('contact_type_id', $contact_type[$contact])
                ->select('id', 'filter_name', 'applied_filters')
                ->get();
                
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
            'applied_filters' => 'required',
            'contact_type' => 'required|in:pharmacy-database,community',
            'is_frequence' => 'nullable|boolean',
            'is_apply_freq' => 'nullable|boolean',
            'frequence_cap' => 'nullable',
            'newsletter_channel' => 'nullable',
        ]);

        $contact = $data['contact_type'];
        $contact_type = [
            'pharmacy-database'=>$this->contact_pharmacy_db->id, 
            'community'=>$this->contact_community->id
        ];

        $baseQuery = Contacts::where('contact_type_id', $contact_type[$contact])
        ->where('contacts.is_deleted', false);
        
           foreach ($request->applied_filters as $key => $filter) {
            FilterHelper::getFilterQuery($baseQuery, $filter);
        }   

        try {
            $amount_of_contacts = $baseQuery->count();
        } catch (\Exception $e) {
            return $this->errorResponse('Error', 400, 'Failed to save filter. Invalid applied filters.');
        }

        try {
            $data['contact_type_id'] = $contact_type[$contact];
            $data['amount_of_contacts'] = $amount_of_contacts;
            if (isset($data['applied_filters'])) {
                $data['applied_filters'] = json_encode($data['applied_filters']);
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
           // return $this->errorResponse('Failed to save new filter', 400);
           return $this->errorResponse($e, 400);  
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
                $baseQuery = Contacts::where('contact_type_id', $saved_fitler->contact_type_id)
                ->where('contacts.is_deleted', false);
                
                foreach ($request->applied_filters as $key => $filter) {
                    FilterHelper::getFilterQuery($baseQuery, $filter);
                }

                $amount_of_contacts = $baseQuery->count();
                $data['amount_of_contacts'] = $amount_of_contacts;
                if (isset($data['applied_filters'])) {
                    $data['applied_filters'] = json_encode($data['applied_filters']);
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
