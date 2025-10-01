<?php

namespace Modules\B2BContact\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\AuditLog\Events\AuditLogged;
use Modules\AuditLog\Models\AuditLogs;
use Modules\AuditLog\Models\ContactLogs;
use Modules\B2BContact\Helpers\FilterHelper;
use Modules\B2BContact\Models\AccountKeyManagers;
use Modules\B2BContact\Models\B2BContacts;
use Modules\B2BContact\Models\B2BContactTypes;
use Modules\B2BContact\Models\B2BFiles;
use Modules\B2BContact\Models\ColumnMappings;
use Modules\B2BContact\Models\CommunityUsers;
use Modules\B2BContact\Models\ContactField as B2BContactField;
use Modules\B2BContact\Models\CountryCodes;
use Modules\B2BContact\Models\HistoryExports;
use Modules\B2BContact\Models\SavedFilters;
use Modules\B2BContact\Services\B2BContactService;
use Modules\B2BContact\Services\FileService;
use Modules\B2BContactAdjustment\Http\Controllers\B2BContactAdjustmentController;
use Modules\Campaign\Models\CampaignContact;
use Modules\NewContactData\Models\ContactField as B2CContactField;
use Modules\NewContactData\Models\Contacts;
use Modules\Whatsapp\Models\WhatsappChatTemplate;
use Modules\WhatsappNewsletter\Models\WaNewsLetter;
use Modules\WhatsappNewsletter\Services\FilterConfigService;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class B2BContactController extends Controller
{
    /**
     * List of traits used by the controller.
     *
     * @return void
     */
    use \App\Traits\ApiResponder;

    /**
     * list of contact type names 
     */

    private $contact_pharmacy = null;
    private $contact_supplier = null;
    private $contact_general_newsletter = null;
    private $contact_pharmacy_db = null;
    private $contact_community = null;
    protected $file_service;
    protected $contact_service;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->contact_pharmacy = B2BContactTypes::where('contact_type_name', 'PHARMACY')->first();
        $this->contact_supplier = B2BContactTypes::where('contact_type_name', 'SUPPLIER')->first();
        $this->contact_general_newsletter = B2BContactTypes::where('contact_type_name', 'GENERAL NEWSLETTER')->first();
        $this->contact_pharmacy_db = B2BContactTypes::where('contact_type_name', 'PHARMACY DATABASE')->first();
        $this->contact_community = B2BContactTypes::where('contact_type_name', 'COMMUNITY')->first();
        $this->file_service = new FileService;
        $this->contact_service = new B2BContactService;
    }

    public function getB2BContactTypes()
    {
        $results = B2BContactTypes::all();
        return $this->successResponse($results, 'Contacts types retrived successfully', 200);
    }

    public function getMetricsData()
    {
        $results = [];
        $total_contacts = B2BContacts::where('contacts.is_deleted', false)->count();
        $new_contacts = B2BContacts::where('contacts.is_deleted', false)->where('created_date',  '>=', Carbon::now()->subDays(30))->count();

        $results = [
          'total_contacts' => $total_contacts,
          'new_contacts' => $new_contacts,
        ];

        return $this->successResponse($results, 'Contacts statistics retrived successfully', 200);
    }
    
    public function getB2CMetricsData()
    {
        $results = [];
        $total_contacts = Contacts::where('contacts.is_deleted', false)->count();
        $new_contacts = Contacts::where('contacts.is_deleted', false)->where('created_date',  '>=', Carbon::now()->subDays(30))->count();

        $results = [
          'total_contacts' => $total_contacts,
          'new_contacts' => $new_contacts,
        ];

        return $this->successResponse($results, 'Contacts statistics retrived successfully', 200);
    }
    
    /**
     * Get top five pharmacies.
     *
     * @return Response
     */
    public function topFiveAreaPharmacies(Request $request)
    {
        $results = B2BContacts::where('contact_type_id', $this->contact_pharmacy->id)
        ->selectRaw("contacts.post_code,COUNT(contacts.post_code) AS total_pharmacies")
        ->where('contacts.is_deleted', false)
        ->orderBy('total_pharmacies', 'desc')
        ->groupBy('contacts.post_code')
        ->take(5)->get()->sortBy('post_code')->values();

        $res = [];
        foreach( $results as $result ){
            $res[] = [
                'post_code' => $result->post_code,
                'total_pharmacies' => (int) $result->total_pharmacies
            ];
        }
       return $this->successResponse($res,'Top five area pharmacies',200);
    }
    /**
     * Get top five purchase pharmacies.
     *
     * @return Response
     */
    public function topFivePurchasePharmacies(Request $request)
    {
       
        $results = B2BContacts::where('contact_type_id', $this->contact_pharmacy->id)
        ->select('contacts.contact_name','contacts.total_purchase')
        ->where('contacts.is_deleted', false)
        ->orderBy('total_purchase', 'desc')
        ->take(5)
        ->get();
        $res = [];
        foreach( $results as $result ){
            $res[] = [
                'pharmacy_name' => $result->contact_name,
                'total_purchase' => (int) $result->total_purchase
            ];
        }
       return $this->successResponse($res,'Top five purchase pharmacies',200);
    }

    // B2B top cards

    /**
     * Get top contact card for B2B.
     */

    public function topContactCardB2B(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:pharmacies,suppliers,general-newsletter',
        ]);
        $res = [];
        switch ($data['type']) {
            case 'pharmacies':
                $contact_type_id = $this->contact_pharmacy->id;
                break;
            case 'suppliers':
                $contact_type_id = $this->contact_supplier->id;
                break;
            case 'general-newsletter':
                $contact_type_id = $this->contact_general_newsletter->id;
                break;
            default:
                return $this->errorResponse('Invalid type',400);
                break;
        }

        $current_month = date('m');
        $all_contacts = B2BContacts::where('contact_type_id', $contact_type_id)
        ->where('is_deleted', false)
        ->count();

        $current_month_count = B2BContacts::where('contact_type_id', $contact_type_id)
        ->whereMonth('created_date', $current_month)
        ->where('is_deleted', false)
        ->count();

        array_push($res, [
            'total' => $all_contacts,
            'delta' => $current_month_count > 0 ? $current_month_count : 0,
        ]);
            
        return $this->successResponse($res,'Top contact card',200);


    }

    /**
     * Get contact growth for B2B.
     */

    public function contactGrowthB2B(Request $request)
    {
        $now = date('Y');
        $months = [
            1 => 'January',
            2 => 'February',   
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];

        $total_pharmacy = 0;
        $total_supplier = 0;
        $total_general_newsletter = 0;
        $pharmacy_result = [];
        $supplier_result = [];
        $general_newsletter_result = [];

        $month = Carbon::now()->month;

        for($i = 1; $i <= $month; $i++){
            $count_pharmacy = B2BContacts::where('contact_type_id', $this->contact_pharmacy->id)
                    ->whereMonth('created_date', $i)
                    ->whereYear('created_date', $now)
                    ->where('is_deleted', false)
                    ->count();
            $total_pharmacy = $total_pharmacy + $count_pharmacy;
            $pharmacy_result[$months[$i]] = $total_pharmacy;

            $count_supplier = B2BContacts::where('contact_type_id', $this->contact_supplier->id)
                    ->whereMonth('created_date', $i)
                    ->whereYear('created_date', $now)
                    ->where('is_deleted', false)
                    ->count();
            $total_supplier = $total_supplier + $count_supplier;
            $supplier_result[$months[$i]] = $total_supplier;

            $count_general_newsletter = B2BContacts::where('contact_type_id', $this->contact_general_newsletter->id)
                    ->whereMonth('created_date', $i)
                    ->whereYear('created_date', $now)
                    ->where('is_deleted', false)
                    ->count();
            $total_general_newsletter = $total_general_newsletter + $count_general_newsletter;
            $general_newsletter_result[$months[$i]] = $total_general_newsletter;
        }

        $res = [
          'Pharmacies' => $pharmacy_result,
          'Suppliers' => $supplier_result,
          'General Newsletter' => $general_newsletter_result
        ];

       return $this->successResponse($res, 'Contact growth', 200);
    }

    public function topFivePharmaciesByDatabase(Request $request)
    {
        $pharmacyDb = Contacts::select('contact_parent_id', DB::raw('COUNT(*) as pharmacy_db_count'))
            ->whereNotNull('contact_parent_id')
            ->where('is_deleted', false)
            ->groupBy('contact_parent_id')
            ->orderByDesc('pharmacy_db_count')
            ->limit(5)
            ->get()
            ->keyBy('contact_parent_id');

        $result = B2BContacts::whereIn('id', $pharmacyDb->keys())
            ->where('is_deleted', false)
            ->select('id', 'contact_name')
            ->get()
            ->map(function ($pharmacy) use ($pharmacyDb) {
                return [
                    'id' => $pharmacy->id,
                    'name' => $pharmacy->contact_name,
                    'total_contacts' => $pharmacyDb[$pharmacy->id]->pharmacy_db_count,
                ];
            });
       
       return $this->successResponse($result,'Top five pharmacies by user database',200);
    }

    public function uploadFile(Request $request)
    {
        // Validate the request
        $request->validate([
            'files' => 'required|array|max:3',
            'files.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);
        
        $path = '/uploads/contact-data';

        $uploaded_files = $this->file_service->uploadFile(null, $request->file('files'), $path);

        if ($uploaded_files) {
            return $this->successResponse(['files' => $uploaded_files], 'File uploaded successfully', 200);
        }

        return $this->errorResponse('Error', 400, 'Upload failed');
    }

    public function getFileContent($id)
    {
        $file = B2BFiles::where('id', $id)->first();
        
        if (!$file) {
            return $this->errorResponse('Error', 400, 'File not found');
        }

        $mime = Storage::mimeType($file->file_path);

        return response(Storage::get($file->file_path), 200)
            ->header('Content-Type', $mime)
            ->header('Content-Disposition', 'inline; filename="' . $file->file_name . '"');
    }

    /**
     * xlsx export
     */
    public function exportData(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'export_to' => 'required|in:xlsx,email,whatsapp',
            'contact_type' => 'required|in:pharmacy,supplier,general-newsletter',
            'applied_filters' => 'nullable',
            'newsletter_channel' => 'nullable|in:email,whatsapp',
            'frequency_cap' => 'nullable',
            'apply_for_all_channel' => 'nullable|boolean',
        ]);

        $contact = $data['contact_type'];
        $contact_type = [
            'pharmacy'=>$this->contact_pharmacy->id,
            'supplier'=>$this->contact_supplier->id,
            'general-newsletter'=>$this->contact_general_newsletter->id
        ];

        $matchContact = [
            'pharmacy'=> 'Pharmacies',
            'supplier'=> 'Suppliers',
            'general-newsletter'=> 'General Newsletter'
        ];
        $contactFilter = 'B2B - '.$matchContact[$contact];
        
        $baseQuery = B2BContacts::with(['customFieldValues.contactField'])
        ->where('contact_type_id', $contact_type[$contact])
        ->where('contacts.is_deleted', false);
        
        if ($request->has('applied_filters')) {
            $checkDefaultColumn = array_merge(
                ColumnMappings::where('contact_type_id', 1)->pluck('field_name')->toArray(),
                ['subscription', 'contact_type', 'contactType', 'exportType']
            );

            foreach ($request->applied_filters as $key => $filter) {
                if (in_array($filter['key'], $checkDefaultColumn)) {
                    FilterHelper::getFilterQuery($baseQuery, $filter);
                }else{
                    // custom field from contacts
                    $baseQuery->whereHas('customFieldValues', function ($queryContactField) use ($filter) {
                        $queryContactField->whereHas('contactField', function ($queryFieldValue) use ($filter) {
                            $filter['items'] = [$filter['key']];
                            $filter['key'] = 'field_name';
                            FilterHelper::getFilterQuery($queryFieldValue, $filter);
                        });

                        $filter['key'] = 'value';
                        FilterHelper::getFilterQuery($queryContactField, $filter);
                    });
                }
            }
        }

        try {
            $count = $baseQuery->count();
        } catch (\Exception $e) {
            Log::error('failed to export data: ', [$e->getMessage()]);
            return $this->errorResponse('Error', 400, 'Failed to export data. Invalid filter.');
        }

        $limit = 25;
        $chunk_size = ceil($count / $limit);
        $chunk = 0;

        Settings::setLocale('de_DE'); 
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Result');
        while($chunk < $chunk_size){
            $data = $baseQuery
                    ->skip($chunk * $limit)
                    ->take($limit)
                    ->get();

            // Collect unique custom field keys
            $customKeys = [];
            foreach ($data as $rowCustom) {
                if (!empty($rowCustom->custom_fields)) {
                    foreach ($rowCustom->custom_fields as $key => $value) {
                        $customKeys[] = $key;
                    }
                }
            }
            $customKeys = array_unique($customKeys);

            if ($contact == 'general-newsletter') {
                $resultHeader = [
                    'No', 'Full Name', 'Email', 'Phone Number', 'Whatsapp Subscription','Email Subscription', 'Created At'
                ];

                // Merge in custom fields at the end
                $headers = array_merge($resultHeader, $customKeys);

                // === 2. Write headers ===
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $col++;
                }

                // Merge in custom fields at the end
                $headers = array_merge($headers, $customKeys);

                // === 2. Write headers ===
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $col++;
                }

                // === 3. Write rows ===
                $rowNum = ($chunk * $limit) + 2;
                $no = ($chunk * $limit) + 1;

                foreach ($data as $row) {
                    $phoneNo = '';
                    if ($row['phone_no']) {
                        $phoneNo = $row['country_code'] ? $row['country_code'].$row['phone_no'] : ''.$row['phone_no'];
                        $phoneNo = '['.$phoneNo.']';
                    }

                    $record = [
                        'No' => $no++,
                        'Contact Name' => $row['contact_name'] ?? '',
                        'Email' => $row['email'] ?? '',
                        'Phone Number' => $phoneNo ?? '',
                        'Whatsapp Subscription' => $row['whatsapp_subscription'] ?? false,
                        'Email Subscription' => $row['email_subscription'] ?? false,
                        'Created At' => $row['created_date'] ?? '',
                    ];


                    // Merge in custom fields
                    foreach ($customKeys as $key) {
                        $record[$key] = $row['custom_fields'][$key] ?? '';
                    }

                    // Write the row with formatting
                    $this->writeRow($sheet, $rowNum, $record, $headers);
                    $rowNum++;
                }
            }
            
            if($contact == 'supplier'){
                $headers = [
                    'No',
                    'Company Name',
                    'VAT ID',
                    'Address',
                    'Postcode',
                    'City',
                    'Country',
                    'Contact Person',
                    'Email',
                    'Phone Number',
                    'Amount of Purchase',
                    'Average of Purchase',
                    'Total Purchase',
                    'Last Purchase Date',
                    'Whatsapp Subscription',
                    'Email Subscription',
                    'Created At',
                ];

                // Merge in custom fields at the end
                $headers = array_merge($headers, $customKeys);

                // === 2. Write headers ===
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $col++;
                }

                // === 3. Write rows ===
                $rowNum = ($chunk * $limit) + 2;
                $no = ($chunk * $limit) + 1;

                foreach ($data as $row) {
                    $phoneNo = '';
                    if ($row['phone_no']) {
                        $phoneNo = $row['country_code'] ? $row['country_code'].$row['phone_no'] : ''.$row['phone_no'];
                        $phoneNo = '['.$phoneNo.']';
                    }

                    $record = [
                        'No' => $no++,
                        'Contact Name' => $row['contact_name'] ?? '',
                        'VAT ID' => $row['vat_id'] ?? '',
                        'Address' => $row['address'] ?? '',
                        'Postcode' => $row['post_code'] ?? '',
                        'City' => $row['city'] ?? '',
                        'Country' => $row['country'] ?? '',
                        'Contact Person' => $row['contact_person'] ?? '',
                        'Email' => $row['email'] ?? '',
                        'Phone Number' => $phoneNo ?? '',
                        'Amount of Purchase' => $row['amount_purchase'] ?? 0,
                        'Average of Purchase' => $row['average_purchase'] ?? 0,
                        'Total Purchase' => $row['total_purchase'] ?? 0,
                        'Last Purchase Date' => $row['last_purchase_date'] ?? '',
                        'Whatsapp Subscription' => $row['whatsapp_subscription'] ?? false,
                        'Email Subscription' => $row['email_subscription'] ?? false,
                        'Created At' => $row['created_date'] ?? '',
                    ];

                    // Merge in custom fields
                    foreach ($customKeys as $key) {
                        $record[$key] = $row['custom_fields'][$key] ?? '';
                    }

                    // Write the row with formatting
                    $this->writeRow($sheet, $rowNum, $record, $headers);
                    $rowNum++;
                }
            }
            
            if($contact == 'pharmacy'){
                // === 1. Define headers (base + custom) ===
                $headers = [
                    'No',
                    'Contact Name',
                    'Contact No',
                    'Address',
                    'Postcode',
                    'City',
                    'Country',
                    'Contact Person',
                    'Email',
                    'Phone Number',
                    'Amount of Purchase',
                    'Average of Purchase',
                    'Total Purchase',
                    'Last Purchase Date',
                    'Whatsapp Subscription',
                    'Email Subscription',
                    'Created At',
                ];

                // Merge in custom fields at the end
                $headers = array_merge($headers, $customKeys);

                // === 2. Write headers ===
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $col++;
                }

                // === 3. Write rows ===
                $rowNum = ($chunk * $limit) + 2;
                $no = ($chunk * $limit) + 1;

                foreach ($data as $row) {
                    $phoneNo = '';
                    if ($row['phone_no']) {
                        $phoneNo = $row['country_code'] ? $row['country_code'].$row['phone_no'] : ''.$row['phone_no'];
                        $phoneNo = '['.$phoneNo.']';
                    }

                    $record = [
                        'No' => $no++,
                        'Contact Name' => $row['contact_name'] ?? '',
                        'Contact No' => $row['contact_no'] ?? '',
                        'Address' => $row['address'] ?? '',
                        'Postcode' => $row['post_code'] ?? '',
                        'City' => $row['city'] ?? '',
                        'Country' => $row['country'] ?? '',
                        'Contact Person' => $row['contact_person'] ?? '',
                        'Email' => $row['email'] ?? '',
                        'Phone Number' => $phoneNo ?? '',
                        'Amount of Purchase' => $row['amount_purchase'] ?? 0,
                        'Average of Purchase' => $row['average_purchase'] ?? 0,
                        'Total Purchase' => $row['total_purchase'] ?? 0,
                        'Last Purchase Date' => $row['last_purchase_date'] ?? '',
                        'Whatsapp Subscription' => $row['whatsapp_subscription'] ?? false,
                        'Email Subscription' => $row['email_subscription'] ?? false,
                        'Created At' => $row['created_date'] ?? '',
                    ];

                    // Merge in custom fields
                    foreach ($customKeys as $key) {
                        $record[$key] = $row['custom_fields'][$key] ?? '';
                    }

                    // Write the row with formatting
                    $this->writeRow($sheet, $rowNum, $record, $headers);
                    $rowNum++;
                }
            }

            $chunk++;
        }

        $highestColumn = $sheet->getHighestColumn();
        foreach (range('A', $highestColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'CCCCCC']
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        $filtersSheet = $spreadsheet->createSheet();
        $filtersSheet->setTitle('Filters');

        // Metadata
        $info = [
            ['Contact Type:', $contactFilter],
            ['Saved Filter Used:', '-'],
            ['Export Date:', now()->format('d.m.Y H:i')],
        ];

        $row = 1;
        foreach ($info as $item) {
            $filtersSheet->setCellValue("A{$row}", $item[0]);
            $filtersSheet->setCellValue("B{$row}", $item[1]);
            $filtersSheet->getStyle("B{$row}")->getFont()->setBold(true);
            $row++;
        }

        $row++; // Empty line

        // Table headers
        $filtersSheet->setCellValue("A{$row}", 'Filter Category');
        $filtersSheet->setCellValue("B{$row}", 'Selected Filters');
        $filtersSheet->getStyle("A{$row}:B{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'CCCCCC']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ]
            ]
        ]);
        $row++;

        // Table filter data
        if ($request->has('applied_filters')) {
            $filters = FilterHelper::formatFilterForExportExcel($request->applied_filters);
            foreach ($filters as $fill) {
                $filtersSheet->setCellValue("A{$row}", $fill['category']);
                $filtersSheet->setCellValue("B{$row}", $fill['value']);
                $filtersSheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ]
                    ],
                ]);
                $row++;
            }            
        }else{
            $filtersSheet->setCellValue("A{$row}", '-');
            $filtersSheet->setCellValue("B{$row}", '-');
        }

        // Auto-size
        foreach (range('A', 'B') as $col) {
            $filtersSheet->getColumnDimension($col)->setAutoSize(true);
        }
    
        $filename ="XLSX-Export.xlsx";
        $writer = new Xlsx($spreadsheet); 
        // Save to a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'export_');
        $writer->save($tempFile);
        
        HistoryExports::insert([
            'name' => 'XLSX-Export',
            'contact_type' => $contact,
            'applied_filters' => $request->applied_filters ? json_encode($request->applied_filters) : null,
            'export_to'=> $request->get('export_to','.xlsx'),
            'amount_of_contacts' => $count,
            'created_date' => date('Y-m-d H:i:s')
        ]);

        // Convert temp file path to UploadedFile
        $uploadedFile = new UploadedFile(
            $tempFile,                     // full path
            basename($filename),           // original filename
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // MIME type
            null,                          // size (optional, will be auto-detected)
            true                           // mark as test mode (no need for real HTTP upload)
        );

        $b2bController = new B2BContactAdjustmentController();
        $uploadRequest = new Request();
        $uploadRequest->files->set('file', $uploadedFile);

        $uploadResponse = $b2bController->handleFileUpload($uploadRequest);
        $uploadData = json_decode($uploadResponse->getContent(), true);

        if ($uploadResponse->getStatusCode() !== 200) {
            return $this->errorResponse(
                'Failed to upload file: ' . ($uploadData['message'] ?? 'Unknown error'),
                400
            );
        }

        $minioBaseUrl = env('MINIO_ENDPOINT');        
        $results['file_url'] = $minioBaseUrl.'/datahub/'.$uploadData['minio_path'];

        switch ($contact) {
            case 'pharmacy':
                $moduleLog = AuditLogs::MODULE_PHARMACY;
                $moduleName = 'Pharmacy';
                break;
            case 'supplier':
                $moduleLog = AuditLogs::MODULE_SUPPLIER;
                $moduleName = 'Supplier';
                break;
            case 'general-newsletter':
                $moduleLog = AuditLogs::MODULE_GENERAL_NEWSLETTER;
                $moduleName = 'General Newsletter';
                break;
            default:
                break;
        }
        event(new AuditLogged($moduleLog, 'Export as xlsx'));

        return $this->successResponse($results, 'exported data', 200);
    }

    function writeRow($sheet, int $rowNum, array $record, array $allHeaders)
    {
        $col = 'A';
        foreach ($allHeaders as $header) {
            $value = $record[$header] ?? '';

            // Dates
            if (in_array($header, ['Last Purchase Date', 'Created At'])) {
                if (!empty($value)) {
                    $timestamp = strtotime($value);
                    $value = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($timestamp);
                    $sheet->getStyle("{$col}{$rowNum}")
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
                } else {
                    $value = '';
                }
            }

            // Floats with currency formatting
            if (in_array($header, ['Average of Purchase', 'Total Purchase'])) {
                $value = (float) $value;
                $sheet->getStyle("{$col}{$rowNum}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00 [$€-407]'); // European format with €
            }

            // Booleans
            if (in_array($header, ['Whatsapp Subscription', 'Email Subscription'])) {
                $value = !empty($value) ? 'Yes' : 'No';
            }

            $sheet->setCellValue("{$col}{$rowNum}", $value);
            $col++;
        }
    }

    public function exportToNewsletter(Request $request)
    {
        $validated = $request->validate([
            'campaignName' => 'required|string|max:255',
            'contactTypeId' => 'required|integer',
        ]);

        $contactType = B2BContactTypes::where('id', $validated['contactTypeId'])->firstOrFail();
            $contactFlag = 'b2c';

            if (in_array(strtolower($contactType->contact_type_name), ['pharmacy', 'supplier', 'general newsletter'])) {
                $contactFlag = 'b2b';
            } elseif (in_array(strtolower($contactType->contact_type_name), ['community', 'pharmacy database'])) {
                $contactFlag = 'b2c';
            }

        $newsletter = WaNewsLetter::create([
            'name' => $validated['campaignName'],
            'contact_type_id' => $validated['contactTypeId'],
            'contact_flag' => $contactFlag,
            'status' => WaNewsLetter::STATUS_DRAFT_NOT_SUBMITTED,
            'created_by' => $request->user() ? $request->user()->id : null,
        ]);

        $saved_filters = SavedFilters::where('is_deleted', false)->get();
    
        $filterConfigService = new FilterConfigService();
        $filterConfig = $filterConfigService->getFilterConfig($contactType->contact_type_name);

        return $this->successResponse(
            [
                'id' => $newsletter->id,
                'name' => $newsletter->name,
                'contact_type_id' => $contactType->id,
                'contact_type_name' => $contactType->contact_type_name,
                'filter_config' => $filterConfig,
                'saved_filters' => $saved_filters
            ],
            'Newsletter created successfully',
            201
        );
    }

    public function previewExportContacts(Request $request)
    {
        $sort = [];
        if ($request->has('sort')) {
            // sorting example => { sort : email.asc,contact_name.asc,post_code.desc }
            $allowed_sort = ['contact_name', 'contact_no', 'post_code', 'city', 'country', 'contact_person', 'email', 
            'phone_no', 'amount_purchase','average_purchase', 'total_purchase', 'last_purchase_date', 'created_date'];

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
        
        $baseQuery = B2BContacts::where('contact_type_id', $this->contact_pharmacy->id)
        ->where('contacts.is_deleted', false);
        if ($request->has('applied_filters')) {
            foreach ($request->applied_filters as $key => $filter) {
                FilterHelper::getFilterQuery($baseQuery, $filter);
            }
        }

        if ($request->has('is_frequence')) {
            $params = $request->all();
            $contactIds = FilterHelper::getDataByFrequencyCap($params['frequency_cap'], $params['newsletter_channel'], $params['is_apply_freq']);
            $baseQuery->whereNotIn('id', $contactIds);
        }

        try {
            $records_total = $baseQuery->count();
        } catch (\Exception $e) {
            return $this->errorResponse('Error', 400, 'Failed to get pharmacy data. Invalid filter column.');
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
            'data' => $results,
        ];

        $res = [
            'recordsTotal' => $records_total,
            'recordsFiltered' => $records_filtered,
            'data' => $results,
        ];

        return $this->successResponse($res,'Preview export contacts data', 200);
    }

    /**
     * import Data
    */

    public function readDataFromFile($file_url, $preview_data = true, $contact_type_id)
    {
        try {
            $data = [];

            // Example whitelist of allowed domains
            $allowedUrls = [ env('MINIO_ENDPOINT') ];

            foreach ($allowedUrls as $url) {
                $parsed = parse_url($url);
                if ($parsed && isset($parsed['host'])) {
                    $hosts[] = strtolower($parsed['host']);
                }
            }
            $allowedHosts = $hosts;
            $parsed = parse_url($file_url);
            $path   = implode('/', array_map('rawurlencode', explode('/', $parsed['path'])));
            $fixed_url = $parsed['scheme'].'://'.$parsed['host'].$path;
            $sanitised_file_url = filter_var($fixed_url, FILTER_VALIDATE_URL);

            if ($sanitised_file_url !== false) {
                $parsed = parse_url($sanitised_file_url);

                if ($parsed && isset($parsed['host'])) {
                    $host = strtolower($parsed['host']);

                    if (in_array($host, $allowedHosts, true)) {
                        $response = Http::get($sanitised_file_url);
                    } else {
                        throw new \Exception("Host not allowed.");
                    }
                } else {
                    throw new \Exception("Invalid URL structure.");
                }
            } else {
                throw new \Exception("Invalid URL.");
            }

            if ($response->successful()) {
                $fileContents = $response->body();
            }

            if (!$fileContents) {
                throw new \Exception('Failed to download file');
            }

            // Detect file type from URL
            $extension = strtolower(pathinfo(parse_url($file_url, PHP_URL_PATH), PATHINFO_EXTENSION));

            // Open in-memory stream
            $tempFile = tempnam(sys_get_temp_dir(), 'import_') . '.' . $extension;
            file_put_contents($tempFile, $fileContents);

            // Use correct reader
            $reader = match ($extension) {
                'xlsx' => new XlsxReader(),
                'csv'  => new Csv(),
                default => throw new \Exception("Unsupported file type: $extension")
            };

            // Load using PhpSpreadsheet
            // $reader = IOFactory::createReaderForFile('php://temp'); // will fallback if not known
            // $spreadsheet = IOFactory::load($temp); // works because $temp is a stream resource

            $spreadsheet = $reader->load($tempFile);
            // $spreadsheet = $reader->load($file);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // get headers from row 1
            $headerRow = $worksheet->getRowIterator(1)->current();
            $headerIterator = $headerRow->getCellIterator();
            $headerIterator->setIterateOnlyExistingCells(true);

            $headers = [];
            foreach ($headerIterator as $cell) {
                $headers[] = $cell->getValue();
            }
            $headerCount = count($headers);
            
            $results = [];
            $limit = 0;

            // if preview data, return headers
            if ($preview_data) {
                $limit = 10;
                $headers = $this->normalizeKeys($headers);
                $data['headers'] = $headers;
            }

            $coreContactFields = ColumnMappings::where('contact_type_id', $contact_type_id)->select('field_name', 'field_type', 'display_name')->get()->toArray();
            
            // start read data from excel
            foreach ($worksheet->getRowIterator(1) as $key => $row) {
                if ($limit !== 0 && $limit < $key) {
                    break;
                }
                $rowData = [];
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                $colIndex = 0;
                foreach ($cellIterator as $cell) {
                    if ($colIndex >= $headerCount) break;

                    $value = $cell->getValue();
                    $rowData[] = $value;
                    $colIndex++;
                }

                // skip empty rows
                $isEmpty = collect($rowData)->every(fn($val) => is_null($val) || $val === '');
                if ($isEmpty) {
                    continue;
                }

                $results[] = $rowData;
            }
            
            // make array of object using headers as array keys
            $keys = array_keys($headers);
            for ($i = 1; $i < count($results); $i++) {
                $data['imported_data'][] = array_combine($keys, $results[$i]);
            }

            $importMapping = $this->buildImportMapping($coreContactFields, $headers);
            $data['data_mapping'] = $importMapping;
            return $data;
            
        } catch (\Exception $e) {
            Log::error("Failed to read imported file: " . $e->getMessage());
            throw $e;
        }
    }

    function buildImportMapping($columnMappings, $headers)
    {
        $result = [];

        foreach ($headers as $headerKey => $headerValue) {
            // Find mapping definition
            $diff = false;
            $sourceKey = null;
            if (in_array($headerKey, ['company_name', 'pharmacy_name','full_name'])) {
                $sourceKey = $headerKey;
                $headerKey = 'contact_name';
                $diff = true;
            }
            if (in_array($headerKey, ['pharmacy_number'])) {
                $sourceKey = $headerKey;
                $headerKey = 'contact_no';
                $diff = true;
            }
            if (in_array($headerKey, ['created_at'])) {
                $sourceKey = $headerKey;
                $headerKey = 'created_date';
                $diff = true;
            }
            $mapping = collect($columnMappings)->firstWhere('field_name', $headerKey);
            
            if ($mapping) {
                $mapKey = $diff ? $sourceKey : $headerKey;
                $result[] = [
                    'display_name'      => $mapping['display_name'],
                    'source_field'      => $mapKey,                   // field from Excel file
                    'destination_field' => $mapping['field_name'],       // field in DB
                    'type'              => strtolower($mapping['field_type']), // e.g. "text"
                    'import'            => true,                         // default true
                    'custom'            => false,                        // default false
                ];
            }else{
                $result[] = [
                    'display_name'      => ucwords(str_replace('_', ' ', $headerKey)), 
                    'source_field'      => $headerKey,
                    'destination_field' => $headerKey,
                    'type'              => 'Text',
                    'import'            => false,
                    'custom'            => true,
                ];
            }
        }

        return $result;
    }
   

    public function previewImportContact(Request $request){
        $data = $request->validate([
            'contact_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
            'contact_type' => 'required|in:pharmacy,supplier,general-newsletter,pharmacy-database,community',
        ]);

        $b2bController = new B2BContactAdjustmentController();
        $uploadRequest = new Request();
        $uploadRequest->files->set('file', $data['contact_file']);

        $uploadResponse = $b2bController->handleFileUpload($uploadRequest);
        $uploadData = json_decode($uploadResponse->getContent(), true);

        if ($uploadResponse->getStatusCode() !== 200) {
            return $this->errorResponse(
                'Failed to upload file: ' . ($uploadData['message'] ?? 'Unknown error'),
                400
            );
        }

        $minioBaseUrl = env('MINIO_ENDPOINT');
        $uploadedFiles['file_name'] = $uploadData['original_filename'];
        $uploadedFiles['file_path'] = $uploadData['minio_path'];
        // $uploadedFiles['file_url'] = $uploadData['file_url'];
        $uploadedFiles['file_url'] = $minioBaseUrl.'/datahub/'.$uploadData['minio_path'];

        $contact = $data['contact_type'];
        $contact_type = [
            'pharmacy'=>$this->contact_pharmacy->id,
            'supplier'=>$this->contact_supplier->id,
            'general-newsletter'=>$this->contact_general_newsletter->id,
            'community'=>$this->contact_community->id,
            'pharmacy-database'=>$this->contact_pharmacy_db->id,
        ];

        try {
            $results = $this->readDataFromFile($uploadedFiles['file_url'], true, $contact_type[strtolower($contact)]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error', 400, 'Failed to read imported file');
        }

        $results['file_url'] = $uploadedFiles['file_url'];
        $results['contact_type'] = $data['contact_type'];
        unset($results['headers']);
        
        return $this->successResponse($results, 'Imported data', 200);
    }

    function normalizeKeys(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            if ($value) {
                $newKey = strtolower(str_replace(' ', '_', $value));
                $normalized[$newKey] = ucwords(str_replace('_', ' ', $value));
            }
        }

        return $normalized;
    }

    public function saveImportContact(Request $request)
    {
        $validated = $request->validate([
            'file_url' => 'required|string',
            'contact_type' => 'required|in:pharmacy,supplier,general-newsletter,community,pharmacy-database',
            'data_mapping' => 'required|array',
        ]);

        try {
            // whitelist of allowed domains
            $allowedUrls = [ env('MINIO_ENDPOINT') ];

            foreach ($allowedUrls as $url) {
                $parsed = parse_url($url);
                if ($parsed && isset($parsed['host'])) {
                    $hosts[] = strtolower($parsed['host']);
                }
            }
            $allowedHosts = $hosts;
            $parsed = parse_url($validated['file_url']);
            $path   = implode('/', array_map('rawurlencode', explode('/', $parsed['path'])));
            $fixed_url = $parsed['scheme'].'://'.$parsed['host'].$path;
            $sanitised_file_url = filter_var($fixed_url, FILTER_VALIDATE_URL);

            if ($sanitised_file_url !== false) {
                $parsed = parse_url($sanitised_file_url);

                if ($parsed && isset($parsed['host'])) {
                    $host = strtolower($parsed['host']);

                    if (!in_array($host, $allowedHosts, true)) {
                        throw new \Exception("Host not allowed.");
                    }
                } else {
                    throw new \Exception("Invalid URL structure.");
                }
            } else {
                throw new \Exception("Invalid URL.");
            }
        } catch (\Exception $e) {
            Log::error('Error import: ', [$e->getMessage()]);
            return $this->errorResponse('Error', 400, 'Failed to save imported contact');
        }

        $contact = $validated['contact_type'];
        $contact_type = [
            'pharmacy'=>$this->contact_pharmacy->id,
            'supplier'=>$this->contact_supplier->id,
            'general-newsletter'=>$this->contact_general_newsletter->id,
            'community'=>$this->contact_community->id,
            'pharmacy-database'=>$this->contact_pharmacy_db->id,
        ];

        dispatch(new ProcessImport($validated['file_url'], $contact_type[strtolower($contact)], $contact, $validated['data_mapping']));
        event(new AuditLogged(AuditLogs::getModule($validated['contact_type']), 'Import Contacts'));

        return $this->successResponse(null, 'Data imported on background. Please check again in a few moments.', 200);
    }

    public function contactFilters(Request $request)
    {
        $validated = $request->validate([
            'contact_type' => 'nullable|in:pharmacy,supplier,general-newsletter,community,pharmacy-database',
            'search_type' => 'required|in:post_code,country,city',
        ]);

        $baseQueryB2B = B2BContacts::query();
        $baseQueryB2C = Contacts::query();

        if ($request->has('contact_type')) {
            $contact = $validated['contact_type'];
            $contact_type = [
                'pharmacy'=>$this->contact_pharmacy->id,
                'supplier'=>$this->contact_supplier->id,
                'general-newsletter'=>$this->contact_general_newsletter->id,
                'pharmacy-database'=>$this->contact_pharmacy_db->id,
                'community'=>$this->contact_community->id
            ];
            $baseQueryB2B->where('contact_type_id', $contact_type[$contact]);
            $baseQueryB2C->where('contact_type_id', $contact_type[$contact]);
        }

        if ($request->has('search')) {
            $baseQueryB2B->where($validated['search_type'], 'ilike', '%'.$request->search.'%');
            $baseQueryB2C->where($validated['search_type'], 'ilike', '%'.$request->search.'%');
        }

        $B2BResult = $baseQueryB2B->distinct()->where($validated['search_type'], '!=', '')->whereNotNull($validated['search_type'])->pluck($validated['search_type']);
        $B2CResult = $baseQueryB2C->distinct()->where($validated['search_type'], '!=', '')->whereNotNull($validated['search_type'])->pluck($validated['search_type']);

        $filters[$validated['search_type']] = $B2BResult->merge($B2CResult)
                                            ->map(fn($item) => $item ? trim($item) : null) // trim around
                                            ->filter(fn($item) => !empty($item))           // remove null/empty
                                            ->unique(fn($item) => strtolower($item))       // case-insensitive unique
                                            ->sort()                                       // sort alphabetically
                                            ->values();
        return $this->successResponse($filters,'Filter data retrived',200);
    }

    public function checkContactByPhone(Request $request)
    {
        $validated = $request->validate([
            'phone_no' => 'required|string|max:255',
        ]);

        $contact = B2BContacts::where('phone_no', $validated['phone_no'])
            ->where('is_deleted', false)
            ->first();

        if ($contact) {
            return $this->successResponse($contact, 'Phone number found', 200);
        }

        return $this->errorResponse('Error', 400, 'Phone number not found');
    }

    public function getContactDetailById($id, $type)
    {
        $contactType = null;

        if ($type == 'b2b') {
            $contactType = new B2BContacts;
        }
        
        if ($type == 'b2c') {
            $contactType = new Contacts;
        }

        if(!$contactType){
            return $this->errorResponse('Error', 400, 'Invalid Contact Type');
        }

        $contact = $contactType::where('id', $id)
            ->where('is_deleted', false)
            ->first();

        if(!$contact){
            return $this->errorResponse('Error', 404, 'Contact not found');
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
        
       return $this->successResponse($contact, 'Contact data', 200);
    }

    public function addFile(Request $request)
    {
        $data = $request->validate([
            'contact_id' => 'nullable|numeric',
            'files' => 'required|array|max:3',
            'files.*' => 'required|file|mimes:doc,docx,csv,xlsx,xls,pdf|max:2048',
        ]);

        if ($request->has('files')) {
            $b2bController = new B2BContactAdjustmentController();
            $uploadRequest = new Request();
            $uploadedFiles = [];
            foreach ($data['files'] as $key => $file) {
                $uploadRequest->files->set('file', $file);
                $uploadResponse = $b2bController->handleFileUpload($uploadRequest);
                $uploadData = json_decode($uploadResponse->getContent(), true);

                if ($uploadResponse->getStatusCode() !== 200) {
                    return $this->errorResponse(
                        'Failed to upload file: ' . ($uploadData['message'] ?? 'Unknown error'),
                        400
                    );
                }

                $minioBaseUrl = env('MINIO_ENDPOINT');
                $uploadedFiles['file_name'] = $uploadData['original_filename'];
                $uploadedFiles['file_path'] = $uploadData['minio_path'];
                if ($request->has('contact_id')) {
                    $uploadedFiles['contact_id'] = $data['contact_id'];
                }
                $size = $file->getSize();
                $sizeInKb = round($size / 1024, 2);
                $uploadedFiles['file_size'] = $sizeInKb.' KB';
                $newFile = B2BFiles::create($uploadedFiles);
                $newFile['file_url'] = $minioBaseUrl.'/datahub/'.$uploadData['minio_path'];
                $createdFiles[] = $newFile;
            }
        }
        if (!$createdFiles) {
            return $this->errorResponse('Error', 400, 'Failed to upload file');
        }
        
        return $this->successResponse($createdFiles, 'File uploaded', 200);
    }

    public function readFile($id)
    {
        $file = B2BFiles::find($id);
        if (!$file) {
            return $this->errorResponse('Error', 400, 'File not found');
        }
        
        return $this->successResponse($file, 'File deleted', 200);
    }

    public function deleteFile($id)
    {
        $file = B2BFiles::find($id);
        if (!$file) {
            return $this->errorResponse('Error', 400, 'File not found');
        }
        $file->delete();
        return $this->successResponse(null, 'File deleted', 200);
    }

    public function addKeyAccountManager(Request $request)
    {
        $data = $request->validate([
            'contact_id' => 'required',
            'manager_name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:255',
            'wa_template_id' => 'nullable|numeric',
            'message_template_name' => 'nullable|string|max:255',
            'auto_reply' => 'nullable|boolean',
        ]);

        $contact = B2BContacts::where('id', $data['contact_id'])
        ->where('is_deleted', false)
        ->first();
        if (!$contact) {
            return $this->errorResponse('Error', 400, 'Contact not found');
        }

        $keyAccountManager = AccountKeyManagers::create($data);
        $contact->account_key_manager_id = $keyAccountManager->id;
        $contact->save();
        $contact->load('accountManager');

        return $this->successResponse($contact, 'Key account manager added', 200);
    }

    public function editKeyAccountManager(Request $request, $id)
    {
        $data = $request->validate([
            'manager_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:255',
            'wa_template_id' => 'nullable|numeric',
            'message_template_name' => 'nullable|string|max:255',
            'auto_reply' => 'nullable|boolean',
        ]);

        $keyAccountManager = AccountKeyManagers::find($id);
        if (!$keyAccountManager) {
            return $this->errorResponse('Error', 400, 'Key Account Manager not found');
        }

        $keyAccountManager->update($data);
        $keyAccountManager->refresh();

        return $this->successResponse($keyAccountManager, 'Key account manager updated', 200);
    }

    public function deleteKeyAccountManager($id)
    {
        $keyAccountManager = AccountKeyManagers::find($id);
        if (!$keyAccountManager) {
            return $this->errorResponse('Error', 400, 'Key Account Manager not found');
        }

        $contact = B2BContacts::where('account_key_manager_id', $id)
        ->where('is_deleted', false)
        ->first();

        $contact->update(['account_key_manager_id' => null]);

        $keyAccountManager->delete();

        return $this->successResponse(null, 'Key account manager deleted', 200);
    }

    public function getKeyAccountManageTemplateMessage()
    {
        $templateMessage = WhatsappChatTemplate::get();

        if($templateMessage->isEmpty()){
            return $this->errorResponse('Error', 400, 'Template not found');
        }
        
        return $this->successResponse($templateMessage, 'Template Message retrieved', 200);
    }

    public function getKeyAccountManageTemplateMessagebyId($id)
    {
        $templateMessage = $templateMessage = WhatsappChatTemplate::find($id);

        if(!$templateMessage){
            return $this->errorResponse('Error', 400, 'Template not found');
        }
        
        return $this->successResponse($templateMessage, 'Template Message retrieved', 200);
    }

    function generateSignature(array $payload): string
    {
        $signatureToken = config('app.signature_token');
        $signature = hash_hmac('sha256', json_encode($payload, JSON_THROW_ON_ERROR), $signatureToken);

        return $signature;
    }

    public function getCommunityUserByAge() {
        try {
            $communityUsers = CommunityUsers::query()
            ->selectRaw("DATE_PART('year', AGE(date_of_birth)) AS age, COUNT(id) AS total")
            ->whereNotIn('id', function ($query) {
                $query->select('user_id')
                    ->from('user_roles');
            })
            ->groupByRaw("DATE_PART('year', AGE(date_of_birth))")
            ->orderBy('age')
            ->get();

            $totalUsers = $communityUsers->sum('total');

            $ageRanges = [
                '18-25' => 0,
                '26-34' => 0,
                '35-49' => 0,
                '50+' => 0,
            ];

            foreach ($communityUsers as $item) {
                if ($item->age >= 18 && $item->age <= 25) {
                    $ageRanges['18-25'] += $item->total;
                } elseif ($item->age >= 26 && $item->age <= 34) {
                    $ageRanges['26-34'] += $item->total;
                } elseif ($item->age >= 35 && $item->age <= 49) {
                    $ageRanges['35-49'] += $item->total;
                } elseif ($item->age >= 50) {
                    $ageRanges['50+'] += $item->total;
                }
            }

            $collection = collect([]);
            foreach ($ageRanges as $key => $value) {
                $collection->push([
                    'name' => $key,
                    'total' => $value,
                    'percentage' => $value > 0 ? number_format(($value / $totalUsers) * 100, 2) . '%' : '0%',
                ]);
            }

            return $this->successResponse($collection, '"Community users by age retrieved', 200);
        } catch (\Exception $e) {
            Log::error('Failed to getCommunityUserByAge: ', [$e->getMessage()]);
            return $this->errorResponse('Error', 400, 'Failed to get community user by age');
        }
        
    }

    public function getCountryCode(Request $request)
    {
        if ($request->has('code')) {
            $results = CountryCodes::where('code', $request->code)->first();
        }else{
            $results = CountryCodes::all();
        }

        return $this->successResponse($results, 'Country code retrieved', 200);
    }

    public function getImportColumnMapping(Request $request)
    {
        $validated = $request->validate([
            'contact_type' => 'required|in:pharmacy,supplier,general-newsletter,community,pharmacy-database',
        ]);

        $contact = $validated['contact_type'];
        $contact_type = [
            'pharmacy'=>$this->contact_pharmacy->id,
            'supplier'=>$this->contact_supplier->id,
            'general-newsletter'=>$this->contact_general_newsletter->id,
            'pharmacy-database'=>$this->contact_pharmacy_db->id,
            'community'=>$this->contact_community->id
        ];
        
        $mappings = ColumnMappings::where('contact_type_id', $contact_type[$contact])->select('field_name', 'display_name', 'field_type')->get();
        return $this->successResponse($mappings, 'Column mappings retrieved', 200);
    }

    public function downloadSampleImport(Request $request)
    {
        $validated = $request->validate([
            'contact_type' => 'required|in:pharmacy,supplier,general-newsletter,community,pharmacy-database',
        ]);

        switch ($validated['contact_type']) {
            case 'pharmacy':
                $data['file_url'] = 'https://cns-storage.kemang.sg/datahub/68cb780c0a7b9-sample-pharmacy.csv';
                break;
            case 'supplier':
                $data['file_url'] = 'https://cns-storage.kemang.sg/datahub/68cb78a3ec0ed-sample-supplier.csv';
                break;
            case 'general-newsletter':
                $data['file_url'] = 'https://cns-storage.kemang.sg/datahub/68cb787364be5-sample-general-newsletter.csv';
                break;
            case 'community':
                $data['file_url'] = 'https://cns-storage.kemang.sg/datahub/68cb784032b5b-sample-community.csv';
                break;
            case 'pharmacy-database':
                $data['file_url'] = 'https://cns-storage.kemang.sg/datahub/68cb788d14142-sample-pharmacy-database.csv';
                break;
            default:
                $data['file_url'] = null;
                break;
        }

        // $filename = "sample-{$validated['contact_type']}.csv";
        // $filePath = 'sample/' . $filename;

        // if (!Storage::disk('public')->exists($filePath)) {
        //     return $this->errorResponse('Error', 400, 'File not found');
        // }

        // $url = URL::temporarySignedRoute(
        //     'api.download.sample',
        //     now()->addMinutes(5),
        //     ['path' => $filePath]
        // );

        return $this->successResponse($data, 'Download sample import file', 200);
    }

    public function contactDropdownFilter(Request $request, $contact_type_id, $column)
    {
        $validator = validator(
            ['contact_type_id' => $contact_type_id, 'column' => $column],
            [
                'contact_type_id' => 'required|integer|exists:contact_types,id',
                'column' => 'required|string'
            ]
        );

        if ($validator->fails()) {
            return $this->errorResponse('Error', 400, 'Failed to get data. Data not Found.');
        }
        
        $search = $request->validate([
            'search' => 'nullable|string'
        ]);

        try {
            // check against special columns
            $checkColumn = [
                'company_name'   => 'contact_name',
                'pharmacy_name'  => 'contact_name',
                'full_name'      => 'contact_name',
                'pharmacy_number'=> 'contact_no',
                'created_at'     => 'created_date',
            ];
            $searchCol = $checkColumn[$column] ?? $column;

            $baseQueryB2B = B2BContacts::query();
            $baseQueryB2C = Contacts::query();

            $baseQueryB2B->where('is_deleted', false)->where('contact_type_id', $contact_type_id);
            $baseQueryB2C->where('is_deleted', false)->where('contact_type_id', $contact_type_id);

            if ($request->has('search')) {
                $baseQueryB2B->where($searchCol, 'ilike', '%'.$request->search.'%');
                $baseQueryB2C->where($searchCol, 'ilike', '%'.$request->search.'%');
            }

            $B2BResult = $baseQueryB2B->distinct()->where($searchCol, '!=', '')->whereNotNull($searchCol)->limit(5)->pluck($searchCol);
            $B2CResult = $baseQueryB2C->distinct()->where($searchCol, '!=', '')->whereNotNull($searchCol)->limit(5)->pluck($searchCol);

            $filters = $B2BResult->merge($B2CResult)
                        ->map(fn($item) => $item ? trim($item) : null) // trim around
                        ->filter(fn($item) => !empty($item))           // remove null/empty
                        ->unique(fn($item) => strtolower($item))       // case-insensitive unique
                        ->sort()                                       // sort alphabetically
                        ->values();
        } catch (\Exception $e) {
            Log::error('Get filter Data: ', [$e->getMessage()]);
            return $this->errorResponse('Error', 400, 'Failed to get data. Invalid column name.');
        }
        
        return $this->successResponse($filters,'Filter data retrieved',200);
    }

    public function getContactReport(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:whatsapp,email',
            'log_id' => 'required|exists:pgsql_b2b_shared.contact_logs,id',
            'status' => 'nullable|string'
        ]);

        $log = ContactLogs::find($validated['log_id']);

        if ($validated['type'] == 'email') {
            if ($request->filled('status')) {
                $status = FilterHelper::mapEmailCampaignEventToStatus($validated['status']);
                $contactIds = CampaignContact::select('contact_id')->whereIn('status', $status)->distinct()->get()->pluck('contact_id')->toArray();
            }else{
                $contactIds = CampaignContact::select('contact_id')->where('id', $log->campaign_id)->distinct()->get()->pluck('contact_id')->toArray();
            }
        }

        if ($validated['type'] == 'whatsapp') {
            if ($request->filled('status')) {
                $status = FilterHelper::mapWhatsappCampaignStatusForReport($validated['status']);
                $contactIds = CampaignContact::select('contact_id')->whereIn('status', $status)->distinct()->get()->pluck('contact_id')->toArray();
            }else{
                $campaign = WaNewsLetter::select('contact_ids')->where('id', $log->campaign_id)->first();
                $contactIds = json_decode($campaign->contact_ids, true);
            }
        }

        try {
            $sort[] = ['row_no', 'asc'];
            if ($request->filled('sort')) {
                $allowed_sort = ['id', 'row_no', 'contact_name', 'contact_no', 'post_code', 'city', 'country', 'address', 'contact_person', 'email', 
                'phone_no', 'amount_purchase','average_purchase', 'total_purchase', 'last_purchase_date', 'created_date'];

                $sort_column = $request->get('sort');
                foreach ($sort_column as $key => $value) {
                    $sort[] = explode('.', $value);
                    // if sort column not included in array and not ascending or descending
                    if (!in_array($sort[$key][0], $allowed_sort) || ($sort[$key][1] !== 'asc' && $sort[$key][1] !== 'desc')) {
                        return $this->errorResponse('Error', 400, 'Failed to get contact data. Invalid sorting column.');
                    }
                }
            }

            $start = $request->get('start', 0);
            $length = $request->get('length', 10);
            $search = $request->get('search');

            if ($log->contact_flag == 'b2b') {
                $baseQuery = B2BContacts::whereIn('id', $contactIds);
            }else{
                $baseQuery = Contacts::whereIn('id', $contactIds);
            }

            $records_total = $baseQuery->count();
        } catch (\Exception $e) {
            Log::error('Get contacts Data: ', [$e->getMessage()]);
            return $this->errorResponse('Error', 400, 'Failed to get contacts data.');
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

        if ($request->has('sort')) {
            foreach ($sort as $value) {
                $baseQuery->orderBy($value[0], $value[1]);
            }
        }else{
            $baseQuery->orderBy('contacts.id', 'desc');
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
            'data' => $results,
        ];

        return $this->successResponse($res, 'All contacts data', 200);
    }

    public function reviewExportCampaignContact(Request $request)
    {
        try {
            $sortByAmount = false;
            $sort[] = ['row_no', 'asc'];
            if ($request->has('sort')) {
                $sort = [];
                $allowed_sort = ['id', 'row_no', 'contact_name', 'vat_id', 'contact_no', 'post_code', 'city', 'country', 'address', 'contact_person', 'email', 'amount_of_contacts', 'amount_contacts',
                'phone_no', 'amount_purchase','average_purchase', 'total_purchase', 'last_purchase_date', 'created_date', 'whatsapp_subscription', 'email_subscription'];

                $sort_column = $request->get('sort');

                if (is_string($sort_column)) {
                    $decoded = json_decode($sort_column, true);
                    $sort_column = json_last_error() === JSON_ERROR_NONE ? $decoded : [$sort_column];
                }
                foreach ($sort_column as $key => $value) {
                    $tempSort = explode('.', $value);
                    if ($tempSort[0] == 'amount_contacts') {
                        $sortByAmount = $tempSort;
                    }else{
                        $sort[] = $tempSort;
                        // if sort column not included in array and not ascending or descending
                        if (!in_array($sort[$key][0], $allowed_sort) || ($sort[$key][1] !== 'asc' && $sort[$key][1] !== 'desc')) {
                            return $this->errorResponse('Error', 400, 'Failed to get pharmacy data. Invalid sorting column.');
                        }
                    }                    
                }
            }

            $start = $request->get('start', 0);
            $length = $request->get('length', 10);
            $search = $request->get('search');

            $ids = $request->contact_ids;
            if (is_string($ids) && str_starts_with($ids, '[')) {
                $ids = json_decode($ids, true);
            }

            $ids = array_map('intval', (array) $ids);
            if (in_array($request->contact_type_id, [1,2,3])) {
                $baseQuery = B2BContacts::selectRaw('ROW_NUMBER() OVER (ORDER BY id desc) as row_no, *')
                ->where('contact_type_id', $request->contact_type_id)->whereIn('id', $ids);
            }else{
                $baseQuery = Contacts::selectRaw('ROW_NUMBER() OVER (ORDER BY id desc) as row_no, *')
                ->where('contact_type_id', $request->contact_type_id)->whereIn('id', $ids);
            }
        
            $records_total = $baseQuery->count();
        } catch (\Exception $e) {
            Log::error('Review export contact Data: ', [$e->getMessage()]);
            return $this->errorResponse('Error', 400, 'Failed to get data.');
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

        // special case if sort by amount contacts
        if ($sortByAmount) {
            if($sortByAmount[1] == 'asc'){
                $results = $results->sortBy('amount_contacts')->values();
            }else{
                $results = $results->sortByDesc('amount_contacts')->values();
            }
        }

        $res = [
            'recordsTotal' => $records_total,
            'recordsFiltered' => $records_filtered,
            'data' => $results,
        ];

       return $this->successResponse($res,'All pharmacy data',200);
    }
    
    public function getDefaultFields($contact_type_id)
    {
        $lists = ColumnMappings::where('contact_type_id', $contact_type_id)->get();
        return $this->successResponse($lists, 'Default fields retrieved', 200);
    }
    
    public function getCustomFields($contact_type_id, Request $request)
    {
        if (in_array($contact_type_id, [1,2,3])) {
            $query = B2BContactField::where('contact_type_id', $contact_type_id);
        }else{
            $query = B2CContactField::where('contact_type_id', $contact_type_id);
        }

        if ($request->has('search')) {
            $lists = $query->where('field_name', 'ilike', '%'.$request->search.'%')->get();
        }else{
            $lists = $query->get();
        }

        foreach ($lists as $list) {
            $list->display_name = ucwords(str_replace('_', ' ', $list->field_name));
        }
        return $this->successResponse($lists, 'Custom fields retrieved', 200);
    }

    public function addCustomField($contact_type_id, Request $request)
    {
        $data = $request->validate([
            'field_name' => 'required|string|max:255',
            'field_type' => 'required|string|in:Text,Number,Date',
        ]);

        $data['contact_type_id'] = $contact_type_id;

        try {
            if (in_array($contact_type_id, [1,2,3])) {
                $custom_field = B2BContactField::create($data);
            }else{
                $custom_field = B2CContactField::create($data);
            }

            return $this->successResponse($custom_field, 'Custom field added successfully', 200);
        } catch (\Exception $e) {
            Log::error('failed to add new custom field: ', [$e]);
            return $this->errorResponse('Error', 400, 'Failed to add new custom field');
        }
    }

    public function checkCustomField($contact_type_id, $field_name)
    {
        try {
            if (in_array($contact_type_id, [1,2,3])) {
                $custom_field = B2BContactField::where('contact_type_id', $contact_type_id)->where('field_name', $field_name)->first();
            }else{
                $custom_field = B2CContactField::where('contact_type_id', $contact_type_id)->where('field_name', $field_name)->first();
            }

            if (!$custom_field) {
                return $this->successResponse(null, 'Custom field not found', 400);
            }

            return $this->successResponse($custom_field, 'Custom field data', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error', 400, 'Custom field not found');
        }
    }
}