<?php

namespace Modules\B2BContact\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\B2BContact\Models\B2BContacts;
use Modules\B2BContact\Models\B2BContactTypes;
use Modules\B2BContact\Models\HistoryExports;

class HistoryExportController extends Controller
{
    private $contact_pharmacy = null;
    private $contact_supplier = null;
    private $contact_general_newsletter = null;

    use \App\Traits\ApiResponder;

    public function __construct()
    {
        $this->contact_pharmacy = B2BContactTypes::where('contact_type_name', 'PHARMACY')->first();
        $this->contact_supplier = B2BContactTypes::where('contact_type_name', 'SUPPLIER')->first();
        $this->contact_general_newsletter = B2BContactTypes::where('contact_type_name', 'GENERAL NEWSLETTER')->first();
    }
    
    /**
     * Display a listing of the resource.
     */
    public function getAllHistoryExports(Request $request)
    {
        try{
            // default pagination setup
            $sort_column = $request->get('sort') == '' ? 'history_exports.id' :  'history_exports.' . explode('-',$request->get('sort'))[0];
            $sort_direction = $request->get('sort') == '' ? 'asc' :  explode('-',$request->get('sort'))[1];;
            $start = $request->get('start', 0);
            $length = $request->get('length', 10);
            $search = $request->get('search');
    
            // count total records
            $records_total = HistoryExports::count();
            
            //  filter records if there are any filter request
            $filteredQuery = HistoryExports::leftJoin('saved_filters as sf', 'sf.id', 'history_exports.saved_filter_id')
                                ->select('history_exports.*', 'sf.filter_name', 'sf.applied_filters as sf_filter_details')
                                ->when($request->get('name'), function($query, $filter_value){
                                    $query->where('name','ilike','%'.$filter_value.'%');
                                })->when($request->get('amount_of_contacts'), function($query, $filter_value){
                                    $filter_value = explode(',', $filter_value);
                                    $query->whereBetween('amount_of_contacts', $filter_value);
                                })->when($request->get('contact_type'), function($query, $filter_value){
                                    $params = explode(',', $filter_value);
                                    foreach ($params as $key => $filter) {
                                        switch ($filter) {
                                            case 'pharmacy':
                                                $contact_type_id[] = $this->contact_pharmacy->id;
                                                break;
                                            case 'supplier':
                                                $contact_type_id[] = $this->contact_supplier->id;
                                                break;
                                            case 'general-newsletter':
                                                $contact_type_id[] = $this->contact_general_newsletter->id;
                                                break;
                                            default: 
                                                $contact_type_id[] = null;
                                                break;
                                        }
                                    }
                                    $query->whereIn('contact_type_id', $contact_type_id);
                                })->when($request->get('created_date'), function($query, $filter_value){
                                    $filter_value = explode(',', $filter_value);
                                    $query->whereBetween('created_date', $filter_value);
                                });
    
            $results = $filteredQuery;
    
            if($search){
                $search = trim($search);
                $results = $filteredQuery
                ->where(function($query) use ($search) {
                    $query->where('history_exports.name', 'ilike', '%'.$search.'%');
                });
            }
    
            $filteredQuery->orderBy($sort_column, $sort_direction);
            $records_filtered = $results->count();
    
            // paginate records
            $results = $results 
            ->take($length)
            ->skip($start)
            ->get();
    
            // manipulate records
            foreach ($results as $key => $data) {
                $data['applied_filters'] = json_decode($data['applied_filters']);
                if ($data['sf_filter_details']) {
                    $data['applied_filters'] = json_decode($data['sf_filter_details']);
                }
                unset($data['sf_filter_details']);
            }
    
            $data = [
                'recordsTotal' => $records_total,
                'recordsFiltered' => $records_filtered,
                'data' => $results
            ];
    
            return $this->successResponse($data, 'All History exports retrieved', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrive history exports', 400);  
        }
    }

}
