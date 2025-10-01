<?php

namespace Modules\B2BContact\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\AuditLog\Events\AuditLogged;
use Modules\AuditLog\Models\AuditLogs;
use Modules\B2BContact\Helpers\FilterHelper;
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
            $sort[] = ['row_no', 'asc'];
            if ($request->has('sort')) {
                $sort = [];
                $allowed_sort = ['name', 'row_no', 'contact_type', 'applied_filters', 'amount_of_contacts', 'export_to', 'created_date'];
                $sort_column = $request->get('sort');

                if (is_string($sort_column)) {
                    $decoded = json_decode($sort_column, true);
                    $sort_column = json_last_error() === JSON_ERROR_NONE ? $decoded : [$sort_column];
                }
                foreach ($sort_column as $key => $value) {
                    $sort[] = explode('.', $value);
                    // if sort column not included in array and not ascending or descending
                    if (!in_array($sort[$key][0], $allowed_sort) || ($sort[$key][1] !== 'asc' && $sort[$key][1] !== 'desc')) {
                        return $this->errorResponse('Error', 400, 'Failed to get history export data. Invalid sorting column.');
                    }
                }
            }

            $start = $request->get('start', 0);
            $length = $request->get('length', 10);
            $search = $request->get('search');
            
            $baseQuery = HistoryExports::query()
                ->with(['savedFilter' => function ($q) {
                    $q->select('id', 'filter_name', 'applied_filters');
                }]);

            // apply filters
            if ($request->has('applied_filters')) {
                foreach ($request->applied_filters as $key => $filter) {
                    FilterHelper::getFilterQuery($baseQuery, $filter);
                }
            }

            try {
                // clone before counting
                $countQuery = clone $baseQuery;
                $records_total = $countQuery->count();
    
                $dataQuery = (clone $baseQuery)->selectRaw('ROW_NUMBER() OVER (ORDER BY history_exports.id DESC) as row_no, history_exports.*');
            } catch (\Exception $e) {
                Log::error('error get data history export: ', [$e->getMessage()]);
                return $this->errorResponse('Error', 400, 'Failed to get history export data. Invalid filter column.');
            }
    
            $records_filtered = $records_total;
            if($search){
                $search = trim($search);
                $results = $dataQuery
                ->where(function($query) use ($search) {
                    $query->where('history_exports.name', 'ilike', '%'.$search.'%');
                });
            }

            foreach ($sort as $value) {
                if ($value[0] !== 'applied_filters') {
                    $dataQuery->orderBy($value[0], $value[1]);
                }
            }

            // paginate records
            $results = $dataQuery 
            ->take($length)
            ->skip($start)
            ->get();

            // manipulate records
            foreach ($results as $key => $data) {
                if (isset($data['applied_filters'])) {
                    $appliedFilters = $data['applied_filters'];

                    if (is_string($appliedFilters)) {
                        $decoded = json_decode($appliedFilters, true);
                        $data['applied_filters'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
                    } elseif (is_array($appliedFilters) && !empty($appliedFilters)) {
                        $data['applied_filters'] = $appliedFilters; // already decoded
                    } else {
                        $data['applied_filters'] = null;
                    }
                }

                if (!is_null($data->savedFilter)) {
                    if (is_string($data->savedFilter['applied_filters'])) {
                        $decoded = json_decode($data->savedFilter['applied_filters'], true);
                        $data['applied_filters'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
                    } elseif (is_array($data->savedFilter['applied_filters']) && !empty($data->savedFilter['applied_filters'])) {
                        $data['applied_filters'] = $data->savedFilter['applied_filters'];
                    } else {
                        $data['applied_filters'] = null;
                    }
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
    
            return $this->successResponse($data, 'All History exports retrieved', 200);
        } catch (\Exception $e) {
            Log::error('Failed retrieve history: ',[$e->getMessage()]);
            return $this->errorResponse('Failed to retrieve history exports', 400);
        }
    }

    public function addHistoryExport(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'export_to' => 'nullable|in:xlsx,.xlsx,email,whatsapp',
            'contact_type' => 'required|in:pharmacy,supplier,general-newsletter,community,pharmacy-database',
            'applied_filters' => 'nullable',
            'saved_filter_id' => 'nullable',
            'newsletter_channel' => 'nullable',
            'frequency_cap' => 'nullable',
            'is_frequence' => 'nullable|boolean',
            'is_apply_freq' => 'nullable|boolean'
        ]);

        if ($request->has('applied_filters')) {
            $data['applied_filters'] = json_encode($data['applied_filters']);
        }
        if ($request->has('newsletter_channel')) {
            $data['newsletter_channel'] = json_encode($data['newsletter_channel']);
        }
        if ($request->has('frequency_cap')) {
            $data['frequency_cap'] = json_encode($data['frequency_cap']);
        }

        // count amount of contacts 
        try {
            $contact = $data['contact_type'];
            $contact_type = [
                'pharmacy'=>$this->contact_pharmacy->id,
                'supplier'=>$this->contact_supplier->id,
                'general-newsletter'=>$this->contact_general_newsletter->id
            ];
            
            $baseQuery = B2BContacts::where('contact_type_id', $contact_type[$contact])
            ->where('contacts.is_deleted', false);
            if ($request->has('applied_filters')) {
                foreach ($request->applied_filters as $key => $filter) {
                    FilterHelper::getFilterQuery($baseQuery, $filter);
                }
            }
            $data['amount_of_contacts'] = $baseQuery->count();
        } catch (\Exception $e) {
            Log::error('failed to export data: ', [$e->getMessage()]);
            return $this->errorResponse('Error', 400, 'Failed to export data. Invalid filter.');
        }

        if ($data['export_to'] == 'xlsx' || $data['export_to'] == '.xlsx') {
            $data['export_to'] = '.xlsx';
        }
        $result = HistoryExports::create($data);

        if (!$result) {
            return $this->errorResponse('Error', 400, 'Failed to create new export');
        }

        event(new AuditLogged(AuditLogs::getModule($data['contact_type']), 'Create Newsletter-Export'));
        
        return $this->successResponse($result, 'New export created', 200);
    }

    public function updateHistoryExport(Request $request, $id)
    {
        $data = $request->validate([
            'export_to' => 'nullable|in:.xlsx,xlsx,email,whatsapp',
            'applied_filters' => 'nullable',
            'saved_filter_id' => 'nullable',
            'newsletter_channel' => 'nullable',
            'frequency_cap' => 'nullable',
            'is_frequence' => 'nullable|boolean',
            'is_apply_freq' => 'nullable|boolean'
        ]);

        $export = HistoryExports::find($id);
        if (!$export) {
            return $this->errorResponse('Error', 400, 'Export data not found');
        }

        if ($request->has('applied_filters')) {
            $data['applied_filters'] = json_encode($data['applied_filters']);
        }
        if ($request->has('newsletter_channel')) {
            $data['newsletter_channel'] = json_encode($data['newsletter_channel']);
        }
        if ($request->has('frequency_cap')) {
            $data['frequency_cap'] = json_encode($data['frequency_cap']);
        }

        // count amount of contacts
        try {
            $contact = $data['contact_type'];
            $contact_type = [
                'pharmacy'=>$this->contact_pharmacy->id,
                'supplier'=>$this->contact_supplier->id,
                'general-newsletter'=>$this->contact_general_newsletter->id
            ];
            
            $baseQuery = B2BContacts::where('contact_type_id', $contact_type[$contact])
            ->where('contacts.is_deleted', false);
            if ($request->has('applied_filters')) {
                foreach ($request->applied_filters as $key => $filter) {
                    FilterHelper::getFilterQuery($baseQuery, $filter);
                }
            }
            $data['amount_of_contacts'] = $baseQuery->count();
        } catch (\Exception $e) {
            Log::error('failed to export data: ', [$e->getMessage()]);
            return $this->errorResponse('Error', 400, 'Failed to export data. Invalid filter.');
        }
        if ($data['export_to'] == 'xlsx' || $data['export_to'] == '.xlsx') {
            $data['export_to'] = '.xlsx';
        }

        $result = $export->update($data);

        if (!$result) {
            return $this->errorResponse('Error', 400, 'Failed to create new export');
        }
        
        return $this->successResponse($export->refresh(), 'Export data updated', 200);
    }

    public function historyExportDataById($id)
    {
        $result = HistoryExports::with(['savedFilter' => function ($q) {
                $q->select('id', 'filter_name', 'applied_filters');
            }])->find($id);

        if (!$result) {
            return $this->errorResponse('Failed to retrieve history export', 400);
        }

        if (is_string($result->applied_filters)) {
            $decoded = json_decode($result->applied_filters, true);
            $data['applied_filters'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : '-';
        } elseif (is_array($result->applied_filters)) {
            $data['applied_filters'] = $result->applied_filters; // already decoded
        } else {
            $data['applied_filters'] = '-';
        }
        
        if (!is_null($result->savedFilter)) {
            $result->savedFilter['applied_filters'] = json_decode($result->savedFilter['applied_filters']);
        }
        
        return $this->successResponse($result, 'History exports retrieved', 200);
    }
}
