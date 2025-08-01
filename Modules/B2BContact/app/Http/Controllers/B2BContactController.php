<?php

namespace Modules\B2BContact\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\B2BContact\Models\B2BContacts;
use Modules\B2BContact\Models\B2BContactTypes;
use Modules\B2BContact\Models\B2BFiles;
use Modules\B2BContact\Models\ContactPersons;
use Modules\B2BContact\Models\HistoryExports;
use Modules\B2BContact\Models\SavedFilters;
use Modules\B2BContact\Services\B2BContactService;
use Modules\B2BContact\Services\FileService;
use Modules\B2BContact\Services\FilterService;
use Modules\WhatsappNewsletter\Models\WaNewsLetter;
use Modules\WhatsappNewsletter\Services\FilterConfigService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Shared\Date;

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
    
    /**
     * Get top five pharmacies.
     *
     * @return Response
     */
    public function topFiveAreaPharmacies(Request $request)
    {
        $results = B2BContactTypes::find($this->contact_pharmacy->id)->contacts()
        ->selectRaw("contacts.post_code,COUNT(contacts.post_code) AS total_pharmacies")
        ->where('contacts.is_deleted', false)
        ->orderBy('total_pharmacies', 'desc')
        ->groupBy('contacts.post_code')
        ->take(5)->get();

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
       
        $results = B2BContactTypes::find($this->contact_pharmacy->id)->contacts()
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
        $res = [];
        if($request->type == 'pharmacies'){
            $prev_month = date('m',strtotime('-1 Month'));
            $current_month = date('m');
            $prev_month_count = B2BContactTypes::find($this->contact_pharmacy->id)->contacts()
            ->whereMonth('created_date', $prev_month)
            ->count();

            $current_month_count = B2BContactTypes::find($this->contact_pharmacy->id)->contacts()
            ->whereMonth('created_date', $current_month)
            ->count();

            $diff =  $current_month_count - $prev_month_count;  
            array_push($res, [
                'total' => $current_month_count,
                'delta' => $diff > 0 ? '+'.$diff : 0,
            ]);
        } else if($request->type == 'suppliers'){
            $prev_month = date('m',strtotime('-1 Month'));
            $current_month = date('m');
            $prev_month_count = B2BContactTypes::find($this->contact_supplier->id)->contacts()
            ->whereMonth('created_date', $prev_month)
            ->count();
            $current_month_count = B2BContactTypes::find($this->contact_supplier->id)->contacts()
            ->whereMonth('created_date', $current_month)
            ->count();
            $diff =  $current_month_count - $prev_month_count;

            array_push($res, [
                'total' => $current_month_count,
                'delta' => $diff > 0 ? '+'.$diff : 0,
            ]);
        } else if($request->type == 'general-newsletter'){
            $prev_month = date('m',strtotime('-1 Month'));
            $current_month = date('m');
            $prev_month_count = B2BContactTypes::find($this->contact_general_newsletter->id)->contacts()
            ->whereMonth('created_date', $prev_month)
            ->count();
            $current_month_count = B2BContactTypes::find($this->contact_general_newsletter->id)->contacts()
            ->whereMonth('created_date', $current_month)
            ->count();
            $diff =  $current_month_count - $prev_month_count;

            array_push($res, [
                'total' => $current_month_count,
                'delta' => $diff > 0 ? '+'.$diff : 0,
            ]);
        } else {
            return $this->errorResponse('Invalid type',400);
        }
            
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

        //pharmacy
        $pharmacy = [];
        for($i = 1; $i <= 12; $i++){
            $pharmacy[$i] = B2BContactTypes::find($this->contact_pharmacy->id)->contacts()
            ->whereMonth('created_date', $i)
            ->whereYear('created_date', $now)
            ->count();
        }
        $pharmacy_result = [];
        foreach($pharmacy as $key => $value){
            $pharmacy_result[$months[$key]] = (int) $value;
        }   
        //supplier
        $supplier = [];
        for($i = 1; $i <= 12; $i++){
            $supplier[$i] = B2BContactTypes::find($this->contact_supplier->id)->contacts()
            ->whereMonth('created_date', $i)
            ->whereYear('created_date', $now)
            ->count();
        }
        $supplier_result = [];
        foreach($supplier as $key => $value){
            $supplier_result[$months[$key]] = (int) $value;
        }

        //general newsletter
        $general_newsletter = [];
        for($i = 1; $i <= 12; $i++){
            $general_newsletter[$i] = B2BContactTypes::find($this->contact_general_newsletter->id)->contacts()
            ->whereMonth('created_date', $i)
            ->whereYear('created_date', $now)
            ->count();
        }   
        $general_newsletter_result = [];
        foreach($general_newsletter as $key => $value){
            $general_newsletter_result[$months[$key]] = (int) $value;
        }

        $res = [
          'Pharmacies' => $pharmacy_result,
          'Suppliers' => $supplier_result,
          'General Newsletter' => $general_newsletter_result
        ];
       return $this->successResponse($res,'Contact growth',200);
    }

    public function topFivePharmaciesByDatabase(Request $request)
    {

        $childs = B2BContacts::whereNotNull('contact_parent_id')
        ->selectRaw('COUNT(*) as total, contact_parent_id')
        ->where('is_deleted', false)
        ->where('contact_type_id', $this->contact_pharmacy->id)
        ->groupBy('contact_parent_id')
        ->orderBy('total', 'desc')
        ->take(5)
        ->get();

        $parent_id = $childs->map(function($element){
            return $element->contact_parent_id;
        });

        $parents = B2BContacts::where('contact_parent_id', null)
        ->orWhere('contact_parent_id', 0)
        ->where('is_deleted', false)
        ->whereIn('id',$parent_id)
        ->select('id', 'contact_name', 'post_code')
        ->get();

        $res = [];

        foreach($parents as $parent){
          foreach($childs as $child){
            if($child->contact_parent_id == $parent->id){
                array_push($res,[
                    'name'=> $parent->contact_name,
                    'post_code'=> $parent->post_code,
                    'database_size'=> $child->total
                ]);
            }
          }
        }
       
       return $this->successResponse($res,'Top five pharmacies by user database',200);
    }

    /**
     * Upload file to MinIO.
     */
    public function minioUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048', 
        ]);

        $file = $request->file('file');

        $path = 'uploads/contact-data/';

        try {
            Storage::disk('minio')->put($path, file_get_contents($file));

            return $this->successResponse(['path' => $path], 'File uploaded successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error', 500, 'Failed to upload: ' . $e->getMessage());
        }
        
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
        ]);

        $contact = $data['contact_type'];
        $contact_type = [
            'pharmacy'=>$this->contact_pharmacy->id,
            'supplier'=>$this->contact_supplier->id,
            'general-newsletter'=>$this->contact_general_newsletter->id
        ];
        
        // filter last_purchase_date not done
        $filteredQuery = B2BContactTypes::find($contact_type[$contact])
        ->contacts()
        ->where('contacts.is_deleted', false);

        // filter_keys other than city, postcode, country
        $filter_keys = ['amount_purchase', 'total_purchase', 'average_purchase', 'created_date'];
        if ($request->input('applied_filters.include')) {
            $included_filter = $request->input('applied_filters.include');

            foreach ($included_filter as $key => $filter) {
                $params = explode(',', $filter);
                if (in_array($key, $filter_keys)) {
                    $filteredQuery->whereBetween($key, $params);
                }else if ($key == 'last_purchase_date') {
                    $filteredQuery->where('last_purchase_date', '>', Carbon::now()->subDays($filter));
                }else{
                    // filter city, postcode, country
                    $filteredQuery->whereIn($key, $params);
                }
            }
        }

        if ($request->input('applied_filters.exclude')) {
            $exclude_filter = $request->input('applied_filters.exclude');
            foreach ($exclude_filter as $key => $filter) {
                $params = explode(',', $filter);
                if (in_array($key, $filter_keys)) {
                    $filteredQuery->whereNotBetween($key, $params);
                }else if ($key == 'last_purchase_date') {
                    $filteredQuery->whereNot('last_purchase_date', '>', Carbon::now()->subDays($filter));
                }else{
                    $filteredQuery->whereNotIn($key, $params);
                }
            }
        };

        $count = $filteredQuery->count();

        $limit = 25;
        $chunk_size = ceil($count / $limit);
        $chunk = 0;

        $spreadsheet = new Spreadsheet();
        while($chunk < $chunk_size){
            $data = $filteredQuery
                    ->skip($chunk * $limit)
                    ->take($limit)
                    ->get();

            $sheet = $chunk == 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();

            if ($contact == 'general-newsletter') {
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
            
            if($contact == 'pharmacy'){
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
    
        $filename = date('YmdHis') . "-" . $contact . ".xlsx";
        $writer = new Xlsx($spreadsheet); 
        $writer->save($filename);

        
        $brevo_id = 0;

        // if($request->get('export_to') == 'whatsapp'){
        //     $recipient = B2BContacts::where('user_id',$request->get('user_id'))->get();
        //     try {
        //         $endpoint = env('WHATSAPP_API_URL') . '/' . env('WHATSAPP_PHONE_NUMBER_ID') . '/messages';
        //         $response = Http::withToken(env('WHATSAPP_API_TOKEN'))
        //         ->post($endpoint, [
        //             'messaging_product' => 'whatsapp',
        //             'recipient_type' => 'individual',
        //             'to' => $recipient[0]->phone_no,
        //             'type' => 'text',
        //             'text' => [
        //                 'body' => 'Please download your report here:' . url('public/' . $filename)
        //             ]
        //         ]);
        //         $responseData = $response->throw();
        //     }catch(\Exception $e){
        //         return $this->errorResponse('Error', 400, 'Failed to send whatsapp: ' . $e->getMessage());
        //     }
        // }
          
        // if($request->get('export_to') == 'email'){
        //     $recipient = B2BContacts::where('user_id',$request->get('user_id'))->get();

        //     try {
        //         $campaign = Http::withHeaders([
        //             'api-key' => env('BREVO_API_KEY'),
        //             'content-type' => 'application/json',
        //             'accept' => 'application/json'
        //         ])->post(env('BREVO_API_URL') . '/smtp/email', [
        //             'name' => 'Contact Data Export',
        //             'subject' => 'Your Contact Data Report is Ready',
        //             'sender' => [
        //                 'name' => 'Cansativa',
        //                 'email' => env('BREVO_SENDER_EMAIL','siroja@kemang.sg'),
        //             ],
        //             'htmlContent' => "<html><body><h1>Please download your report</h1><p><a href='".url('public/' . $filename)."'>Here</a></p></body></html>",
        //             'to' => [
        //                 [
        //                     'email' => $recipient[0]->email
        //                 ]
        //             ]
        //         ]);

        //         $campaign->throw();
        //         $brevo_id = $campaign;

        //         /* $campaign_id = $campaign->json()['id'];

        //         $sent = Http::withHeaders([
        //             'api-key' => env('BREVO_API_KEY'),
        //             'content-type' => 'application/json',
        //             'accept' => 'application/json'
        //         ])->post(env('BREVO_API_URL') . '/emailCampaigns/' . $campaign_id . '/sendNow',[
        //             'emailTo' => ['tomo@kemang.sg'], 
        //         ]);   */

        //     }catch(\Exception $e){
        //         return $this->errorResponse('Error', 400, 'Failed to send email: ' . $e->getMessage());
        //     }
        // }
        
        HistoryExports::insert([
            'name' => $request->name,
            'contact_type_id' => $contact_type[$contact],
            'applied_filters' => json_encode($request->applied_filters),
            'export_to'=> $request->get('export_to','.xlsx'),
            'amount_of_contacts' => $count,
            'created_date' => date('Y-m-d H:i:s')
        ]);

        return $this->successResponse([
            "filename"=>url('public/' . $filename)
        ],'successfully exported file',200);
    }

    public function exportToNewsletter(Request $request)
    {
        $validated = $request->validate([
            'campaignName' => 'required|string|max:255',
            'contactTypeId' => 'required|integer|exists:contact_types,id',
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
        $validated = $request->validate([
            'contact_type_id' => 'required|integer|exists:contact_types,id',
            'applied_filters' => 'required',
        ]);

        // default pagination setup
        $sort_column = $request->get('sort') == '' ? 'contacts.id' :  'contacts.' . explode('-',$request->get('sort'))[0];
        $sort_direction = $request->get('sort') == '' ? 'asc' :  explode('-',$request->get('sort'))[1];;
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $search = $request->get('search');
        
        $filteredQuery = B2BContactTypes::find($validated['contact_type_id'])->contacts()
        ->where('contacts.is_deleted', false);

        // filter_keys other than city, postcode, country
        $filter_keys = ['amount_purchase', 'total_purchase', 'average_purchase', 'created_date'];
        if (isset($validated['applied_filters']['include'])) {
            $included_filter = $validated['applied_filters']['include'];

            foreach ($included_filter as $key => $filter) {
                $params = explode(',', $filter);
                if (in_array($key, $filter_keys)) {
                    $filteredQuery->whereBetween($key, $params);
                }else if ($key == 'last_purchase_date') {
                    if ($params[1] == 'days') {
                        $search_date = Carbon::now()->subDays($params[0]);
                    } else if ($params[1] == 'months') {
                        $search_date = Carbon::now()->subMonths($params[0]);
                    } else {
                        $search_date = Carbon::now()->subYears($params[0]);
                    }
                    $filteredQuery->where('last_purchase_date', '>', $search_date);
                }else{
                    // filter city, postcode, country
                    $filteredQuery->whereIn($key, $params);
                }
            }
        }

        if (isset($validated['applied_filters']['exclude'])) {
            $exclude_filter = $validated['applied_filters']['exclude'];
            foreach ($exclude_filter as $key => $filter) {
                $params = explode(',', $filter);
                if (in_array($key, $filter_keys)) {
                    $filteredQuery->whereNotBetween($key, $params);
                }else if ($key == 'last_purchase_date') {
                    if ($params[1] == 'days') {
                        $search_date = Carbon::now()->subDays($params[0]);
                    } else if ($params[1] == 'months') {
                        $search_date = Carbon::now()->subMonths($params[0]);
                    } else {
                        dd($params[0]);
                        $search_date = Carbon::now()->subYears($params[0]);
                    }
                    $filteredQuery->where('last_purchase_date', '>', $search_date);
                }else{
                    $filteredQuery->whereNotIn($key, $params);
                }
            }
        };
        
        $records_total = $filteredQuery->count();
        $records_filtered = $records_total;
        if($search){
            $search = trim($search);
            $results = $filteredQuery
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'ilike', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'ilike', '%'.$search.'%')
                      ->orWhere('contacts.email', 'ilike', '%'.$search.'%');
            })
            ->orderBy($sort_column, $sort_direction);
            $records_filtered = $results
            ->count();
            $results = $results 
            ->take($length)
            ->skip($start)
            ->get();
        } else {
            $results = $filteredQuery
            ->orderBy($sort_column, $sort_direction);
            $records_filtered = $results
            ->count();
            $results = $results 
            ->take($length)
            ->skip($start)
            ->get();
        }

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

    public function readDataFromFile($file, $preview_data = true)
    {
        $data = [];
        if ($preview_data) {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
        }else{
            $reader = IOFactory::createReaderForFile($file);
        }

        $spreadsheet = $reader->load($file);
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
                if (strtolower($value) == 'yes' || strtolower($value) == 'no') {
                    $value = strtolower($value) == 'yes' ? true : false;
                }
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

        $imported_data['headers'] = $headers;

        $available_attributes[] = [
            "header_title" => "Pharmachy Number",
            "header_type" => "Number",
            "header_field" => 'pharmacy_number'
        ];
        
        // make array of object using headers as array keys
        $keys = array_keys($headers);
        for ($i = 1; $i < count($results); $i++) {
            $data['imported_data'][] = array_combine($keys, $results[$i]);
        }
        
        return $data;
    }

    public function previewContact(Request $request){
        $data = $request->validate([
            'contact_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'contact_type' => 'required|in:pharmacy,supplier,general-newsletter',
        ]);

        $file = $data['contact_file'];
        $path = '/temp';

        $file_name = uniqid() . '_' . $file->getClientOriginalName();
                
        // Store the file in MinIO
        //$file->storeAs('', $file_path, 'minio');

        // Store to local private storage
        Storage::disk('local')->putFileAs($path, $file, $file_name);

        $results = $this->readDataFromFile($file, true);
        $results['file_name'] = $file_name;

        return $this->successResponse($results, 'Imported data', 200);
    }

    function normalizeKeys(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            if ($value) {
                $newKey = strtolower(str_replace(' ', '_', $value));
                $normalized[$newKey] = $value;
            }
        }

        return $normalized;
    }

    public function saveImportContact(Request $request)
    {
        $validated = $request->validate([
            'file_name' => 'required|string',
            'contact_type' => 'required|in:pharmacy,supplier,general-newsletter',
        ]);

        // check if file exist, then start importing
        if (!Storage::disk('local')->exists('temp/'.$validated['file_name'])) {
            return $this->errorResponse('Error', 400, 'Failed to import data. File not found');
        }
        $file = Storage::disk('local')->path('temp/'.$validated['file_name']);
        $data = $this->readDataFromFile($file, false);

        switch($validated['contact_type']){
            case 'pharmacy':
                $import_status = $this->importPharmacy($data['imported_data']);
                break;
            case 'supplier':
                $import_status = $this->importSupplier($data['imported_data']);
                break;
            case 'general-newsletter':
                $import_status = $this->importGeneralNewsletter($data['imported_data']);
                break;
            case 'default':
                return $this->errorResponse('Error', 404, 'Invalid contact type');
                break;
        }

        if ($import_status) {
            return $this->successResponse(null, 'Data imported successfully', 200);
        }

        return $this->errorResponse('Error', 400, 'Failed to import data');
    }

    public function importPharmacy($imported_data)
    {
        $default_columns = [
            'contact_name', 'contact_no', 'address', 'post_code', 'city',
            'country', 'contact_person', 'email', 'phone_no', 'amount_purchase', 
            'average_purchase', 'total_purchase', 'last_purchase_date'
        ];

        $imported_data = $this->checkImportCustomFields($default_columns, $imported_data);

        for ($i=0; $i < count($imported_data); $i++) {
            $imported_data[$i]['contact_type_id'] = $this->contact_pharmacy->id;
            if (isset($imported_data[$i]['created_date'])) {
                $imported_data[$i]['created_date'] = \DateTime::createFromFormat('Y.m.d', $imported_data[$i]['created_at'])->format('Y-m-d');
            }
        }

        try {
            DB::beginTransaction();
            foreach ($imported_data as $key => $data) {
                $new_contact = B2BContacts::create($data);

                if ($data['contact_person']) {
                    ContactPersons::create([
                        'contact_person' => $new_contact->id,
                        'contact_name' => $data['name'],
                        'email' => $data['email'],
                        'phone_no' => $data['phone_no']
                    ]);
                }
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function importSupplier($imported_data)
    {
        $default_columns = [
            'contact_name', 'vat_id', 'post_code', 'city',
            'country', 'contact_person', 'email', 'phone_no', 'amount_purchase', 
            'average_purchase', 'total_purchase', 'last_purchase_date', 'created_at'
        ];
        
        $imported_data = $this->checkImportCustomFields($default_columns, $imported_data);

        for ($i=0; $i < count($imported_data); $i++) {
            $imported_data[$i]['contact_type_id'] = $this->contact_supplier->id;
            if (isset($imported_data[$i]['created_date'])) {
                $imported_data[$i]['created_date'] = \DateTime::createFromFormat('Y.m.d', $imported_data[$i]['created_at'])->format('Y-m-d');
            }
        }

        try {
            DB::beginTransaction();
            foreach ($imported_data as $key => $data) {
                $new_contact = B2BContacts::create($data);
                if ($data['contact_person']) {
                    ContactPersons::create([
                        'contact_person' => $new_contact->id,
                        'contact_name' => $data['contact_person'],
                        'email' => $data['email'],
                        'phone_no' => $data['phone_no']
                    ]);
                }
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function importGeneralNewsletter($imported_data)
    {
        $default_columns = [
            'contact_name', 'email', 'phone_no', 'email_subscription', 'whatsapp_subscription', 'created_at'
        ];
        
        $imported_data = $this->checkImportCustomFields($default_columns, $imported_data);

        for ($i=0; $i < count($imported_data); $i++) {
            $imported_data[$i]['contact_type_id'] = $this->contact_general_newsletter->id;
            if (isset($imported_data[$i]['created_date'])) {
                $imported_data[$i]['created_date'] = \DateTime::createFromFormat('Y.m.d', $imported_data[$i]['created_at'])->format('Y-m-d');
            }
            if (isset($imported_data[$i]['email_subscription'])) {
                $imported_data[$i]['email_subscription'] = strtolower($imported_data[$i]['email_subscription']) == 'yes' ? true : false;
            }
            if (isset($imported_data[$i]['whatsapp_subscription'])) {
                $imported_data[$i]['whatsapp_subscription'] = strtolower($imported_data[$i]['whatsapp_subscription']) == 'yes' ? true : false;
            }
            unset($imported_data[$i]['created_at']);
        }

        try {
            DB::beginTransaction();
            foreach (array_chunk($imported_data, 100) as $chunk) {
                B2BContacts::insert($chunk);
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function checkImportCustomFields($default_columns, $imported_data){
        $custom_fields = [];

        $imported_keys = array_keys($imported_data[0]);
        
        // check if custom fields exist
        foreach ($imported_keys as $key) {
            if (!in_array($key, $default_columns)) {
                $custom_fields[] = $key;
            }
        }
        
        // check mapping to database column
        // insert data to custom_fields if database column not exist
        // delete(unset) data from array of data
        if ($custom_fields) {
            foreach ($custom_fields as $key => $field) {
                for ($i=0; $i < count($imported_data); $i++) {
                    $custom[$field] = $imported_data[$i][$field];
                    $imported_data[$i]['custom_fields'] = json_encode($custom);
                    unset($imported_data[$i][$field]);
                }
            }
        }

        return $imported_data;
    }

    public function contactFilters(Request $request)
    {
        $validated = $request->validate([
            'contact_type' => 'required|in:pharmacy,supplier,general-newsletter',
        ]);

        $contact = $validated['contact_type'];
        $contact_type = [
            'pharmacy'=>$this->contact_pharmacy->id,
            'supplier'=>$this->contact_supplier->id,
            'general-newsletter'=>$this->contact_general_newsletter->id
        ];

        $filter_service = new FilterService;
        $filters = $filter_service->getFilterData($contact_type[$contact]);
        return $this->successResponse($filters,'All pharmacy data',200);
    }
}
