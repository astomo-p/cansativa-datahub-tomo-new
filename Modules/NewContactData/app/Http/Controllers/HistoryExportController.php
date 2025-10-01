<?php

namespace Modules\NewContactData\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\AuditLog\Events\AuditLogged;
use Modules\AuditLog\Models\AuditLogs;
use Modules\AuditLog\Models\ContactLogs;
use Modules\B2BContact\Helpers\FilterHelper;
use Modules\NewContactData\Models\Contacts;
use Modules\NewContactData\Models\ContactTypes;
use Modules\NewContactData\Models\HistoryExports;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Modules\NewContactData\Helpers\TranslatorHelper;

class HistoryExportController extends Controller
{
    private $contact_pharmacy = 0;
    private $contact_supplier = 0;
    private $contact_community = 0;
    private $contact_general_newsletter = 0;
    private $contact_pharmacy_db = 0;
    private $contact_subscriber = 0;

    use \App\Traits\ApiResponder;

    public function __construct()
    {
         //$this->contact_pharmacy = B2BContactTypes::where('contact_type_name', 'PHARMACY')->first();
        //$this->contact_supplier = B2BContactTypes::where('contact_type_name', 'SUPPLIER')->first();
        $this->contact_community = ContactTypes::where('contact_type_name', 'COMMUNITY')->first();
        //$this->contact_general_newsletter = B2BContactTypes::where('contact_type_name', 'GENERAL NEWSLETTER')->first();
        $this->contact_pharmacy_db = ContactTypes::where('contact_type_name', 'PHARMACY DATABASE')->first();
        $this->contact_subscriber = ContactTypes::where('contact_type_name', 'SUBSCRIBER')->first();
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

              $final_results = [];
              foreach($results->toArray() as $item){
                 if($request->header('Lang')){
                     $meticulous = ucwords(str_replace('-', ' ',$item['contact_type']));
                     $item['contact_type'] = TranslatorHelper::getTranslate($meticulous,$request->header('Lang'));
                     $meticulous2 = ucwords($item['export_to']);
                     $item['export_name'] = ($item['export_to'] == 'XLSX-Export') ? 'XLSX-Export' : TranslatorHelper::getTranslate($meticulous2,$request->header('Lang'));
                 }
                 array_push($final_results,$item);
             }
    
            $data = [
                'recordsTotal' => $records_total,
                'recordsFiltered' => $records_filtered,
                'data' => $final_results
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
            'export_to' => 'nullable|in:.xlsx,xlsx,email,whatsapp',
            'contact_type' => 'required',
            'applied_filters' => 'nullable',
            'saved_filter_id' => 'nullable',
            'newsletter_channel' => 'nullable',
            'frequency_cap' => 'nullable',
            'is_frequence' => 'nullable|boolean',
            'is_apply_freq' => 'nullable|boolean'
        ]);

        $history = HistoryExports::where('name', $data['name'])->first();
        if ($history) {
            return $this->errorResponse('Error', 400, 'Failed to create new export. Name already taken.');
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
                'community'=>$this->contact_community->id,
                'pharmacy-database'=>$this->contact_pharmacy_db->id
            ];

            $baseQuery = Contacts::where('contact_type_id', $contact_type[$contact])
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
                'community'=>$this->contact_community->id,
                'pharmacy-database'=>$this->contact_pharmacy_db->id
            ];

            $baseQuery = Contacts::where('contact_type_id', $contact_type[$contact])
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

    public function exportData(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'export_to' => 'required|in:xlsx,email,whatsapp',
            'contact_type' => 'required|in:pharmacy-database,community',
            'applied_filters' => 'nullable',
            'newsletter_channel' => 'nullable|in:email,whatsapp',
            'frequency_cap' => 'nullable',
            'apply_for_all_channel' => 'nullable|boolean',
        ]);

        $contact = $data['contact_type'];
        $contact_type = [
            'pharmacy-database'=>$this->contact_pharmacy_db->id, 
            'community'=>$this->contact_community->id
        ];
        
        $baseQuery = Contacts::where('contact_type_id', $contact_type[$contact])
        ->where('contacts.is_deleted', false);
        
        if ($request->has('applied_filters')) {
            foreach ($request->applied_filters as $key => $filter) {
                FilterHelper::getFilterQuery($baseQuery, $filter);
            }
        }

        if ($data['frequency_cap']) {
            $data['frequency_cap']['count_limit'];
            $data['frequency_cap']['day_limit'];
            $data['frequency_cap']['unit'];

            if ($data['frequency_cap']['unit'] == 'day') {
                $substractDate = Carbon::now()->subDays($data['frequency_cap']['day_limit']);
            }
            if ($data['frequency_cap']['unit'] == 'month') {
                $substractDate = Carbon::now()->subMonths($data['frequency_cap']['day_limit']);
            }
            if ($data['frequency_cap']['unit'] == 'year') {
                $substractDate = Carbon::now()->subYears($data['frequency_cap']['day_limit']);
            }

            $contactIds = ContactLogs::query()
                ->select('contact_id')
                ->where('type', $data['newsletter_channel'])
                ->where('created_date', '>=', $substractDate)
                ->groupBy('contact_id')
                ->havingRaw('COUNT(*) <= ?', $data['frequency_cap']['count_limit'])
                ->pluck('contact_id');

            $logs = ContactLogs::query()
                ->where('type', $data['newsletter_channel'])
                ->where('created_date', '>=', $substractDate)
                ->whereIn('contact_id', $contactIds)
                ->orderBy('created_date', 'desc')
                ->get();
        }

        try {
            $count = $baseQuery->count();
        } catch (\Exception $e) {
            return $this->errorResponse('Error', 400, 'Failed to export data. Invalid filter.');
        }

        $limit = 25;
        $chunk_size = ceil($count / $limit);
        $chunk = 0;

        $spreadsheet = new Spreadsheet();
        while($chunk < $chunk_size){
            $data = $baseQuery
                    ->skip($chunk * $limit)
                    ->take($limit)
                    ->get();

            $sheet = $chunk == 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();

            if ($contact == 'community') {
                $sheet->setCellValue('A1', 'Full Name');
                $sheet->setCellValue('B1', 'Email'); 
                $sheet->setCellValue('C1', 'Phone Number');
                $sheet->setCellValue('D1', 'Whatsapp Subscription');
                $sheet->setCellValue('E1', 'Email Subscription');
                $sheet->setCellValue('F1', 'Created At');
                $rows = 2;

                foreach($data as $row){
                    $sheet->setCellValue('A' . $rows, $row['contact_name']);
                    $sheet->setCellValue('B' . $rows, $row['email']);
                    $sheet->setCellValue('C' . $rows, $row['phone_no']);
                    $sheet->setCellValue('D' . $rows, $row['whatsapp_subscription'] ? 'Yes' : 'No');
                    $sheet->setCellValue('E' . $rows, $row['email_subscription'] ? 'Yes' : 'No');
                    $sheet->setCellValue('F' . $rows, date('d F Y',strtotime($row['created_date'])));
                    $rows++;
                }
            }
            
            if($contact == 'supplier'){
                $sheet->setCellValue('A1', 'Pharmacy Name');
                $sheet->setCellValue('B1', 'Pharmacy Number');
                $sheet->setCellValue('C1', 'VAT ID');
                $sheet->setCellValue('D1', 'Address');
                $sheet->setCellValue('E1', 'Postcode');
                $sheet->setCellValue('F1', 'City');
                $sheet->setCellValue('G1', 'Country');
                $sheet->setCellValue('H1', 'State');
                $sheet->setCellValue('I1', 'Contact Person'); 
                $sheet->setCellValue('J1', 'Email');
                $sheet->setCellValue('K1', 'Phone Number');
                $sheet->setCellValue('L1', 'Amount of Purchase');
                $sheet->setCellValue('M1', 'Average of Purchase');
                $sheet->setCellValue('N1', 'Total Purchase');
                $sheet->setCellValue('O1', 'Last Purchase Date');
                $sheet->setCellValue('P1', 'Created At');
                $rows = 2;
                foreach($data as $row){
                    $sheet->setCellValue('A' . $rows, $row['contact_name']);
                    $sheet->setCellValue('B' . $rows, $row['contact_no']);
                    $sheet->setCellValue('C' . $rows, $row['vat_id']);
                    $sheet->setCellValue('D' . $rows, $row['address']);
                    $sheet->setCellValue('E' . $rows, $row['post_code']);
                    $sheet->setCellValue('F' . $rows, $row['city']);
                    $sheet->setCellValue('G' . $rows, $row['country']);
                    $sheet->setCellValue('H' . $rows, $row['state']);
                    $sheet->setCellValue('I' . $rows, $row['contact_person']);
                    $sheet->setCellValue('J' . $rows, $row['email']);
                    $sheet->setCellValue('K' . $rows, $row['phone_no']);
                    $sheet->setCellValue('L' . $rows, $row['amount_purchase']);
                    $sheet->setCellValue('M' . $rows, $row['average_purchase']);
                    $sheet->setCellValue('N' . $rows, $row['total_purchase']);
                    $sheet->setCellValue('O' . $rows, date('d F Y',strtotime($row['last_purchase_date'])));
                    $sheet->setCellValue('P' . $rows, date('d F Y',strtotime($row['created_date'])));
                    
                    $rows++;
                }
            }
            
            if($contact == 'pharmacy-database'){
                $sheet->setCellValue('A1', 'Pharmacy Name');
                $sheet->setCellValue('B1', 'Pharmacy Number');
                $sheet->setCellValue('C1', 'Address');
                $sheet->setCellValue('D1', 'Postcode');
                $sheet->setCellValue('E1', 'City');
                $sheet->setCellValue('F1', 'Country');
                $sheet->setCellValue('G1', 'State');
                $sheet->setCellValue('H1', 'Contact Person'); 
                $sheet->setCellValue('I1', 'Email');
                $sheet->setCellValue('J1', 'Phone Number');
                $sheet->setCellValue('K1', 'Amount of Purchase');
                $sheet->setCellValue('L1', 'Average of Purchase');
                $sheet->setCellValue('M1', 'Total Purchase');
                $sheet->setCellValue('N1', 'Last Purchase Date');
                $sheet->setCellValue('O1', 'Created At');
                $rows = 2;
                foreach($data as $row){
                    $sheet->setCellValue('A' . $rows, $row['contact_name']);
                    $sheet->setCellValue('B' . $rows, $row['contact_no']);
                    $sheet->setCellValue('C' . $rows, $row['address']);
                    $sheet->setCellValue('D' . $rows, $row['post_code']);
                    $sheet->setCellValue('E' . $rows, $row['city']);
                    $sheet->setCellValue('F' . $rows, $row['country']);
                    $sheet->setCellValue('G' . $rows, $row['state']);
                    $sheet->setCellValue('H' . $rows, $row['contact_person']);
                    $sheet->setCellValue('I' . $rows, $row['email']);
                    $sheet->setCellValue('J' . $rows, $row['phone_no']);
                    $sheet->setCellValue('K' . $rows, $row['amount_purchase']);
                    $sheet->setCellValue('L' . $rows, $row['average_purchase']);
                    $sheet->setCellValue('M' . $rows, $row['total_purchase']);
                    $sheet->setCellValue('N' . $rows, date('d F Y',strtotime($row['last_purchase_date'])));
                    $sheet->setCellValue('O' . $rows, date('d F Y',strtotime($row['created_date'])));
                    
                    $rows++;
                }
            }

            $chunk++;
        }
    
        $filename = "XLSX-Export.xlsx";
        $writer = new Xlsx($spreadsheet); 
        $writer->save($filename);
        
        HistoryExports::insert([
            'name' => 'XLSX-Export',
            'contact_type' => 'B2B - '.ucwords(str_replace('-', ' ', $contact)),
            'applied_filters' => json_encode($request->applied_filters),
            'export_to'=> $request->get('export_to','.xlsx'),
            'amount_of_contacts' => $count,
            'created_date' => date('Y-m-d H:i:s')
        ]);

        return $this->successResponse([
            "filename"=>url('public/' . $filename)
        ],'successfully exported file',200);
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
