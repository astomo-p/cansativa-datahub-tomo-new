<?php

namespace Modules\NewContactData\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\NewContactData\Models\Contacts;
use Modules\NewContactData\Models\ContactTypes;
use Modules\NewContactData\Models\B2BContacts;
use Modules\NewContactData\Models\B2BContactTypes;
use Modules\NewAnalytics\Models\UserSavedPosts;
use Modules\NewAnalytics\Models\VisitorLikes;
use Modules\NewAnalytics\Models\UserComments;
use Modules\NewContactData\Models\HistoryExports;
use Modules\NewContactData\Models\Users;
use Modules\NewContactData\Models\SavedFilters;
use Modules\NewContactData\Models\SharedContactLogs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Client;
use Automattic\WooCommerce\Client as WooClient;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Modules\B2BContact\Helpers\FilterHelper;
use Illuminate\Database\Eloquent\Builder;
use Modules\B2BContactAdjustment\Http\Controllers\B2BContactAdjustmentController;
use Modules\NewContactData\Models\AccountKeyManagers; 
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Modules\AuditLog\Events\AuditLogged;
use Modules\AuditLog\Events\ContactLogged;
use Modules\AuditLog\Models\AuditLogs;
use Modules\B2BContact\Models\ColumnMappings;  
use Modules\NewContactData\Helpers\ContactFieldHelper;
use Modules\NewContactData\Models\ContactField;
use Modules\NewContactData\Models\ContactFieldValue;
use Modules\NewContactData\Helpers\TranslatorHelper;

class NewContactDataController extends Controller
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

    private $contact_pharmacy = 0;
    private $contact_supplier = 0;
    private $contact_community = 0;
    private $contact_general_newsletter = 0;
    private $contact_pharmacy_db = 0;
    private $contact_subscriber = 0;

    /**
     * constructor
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

    /**
     * Get top five area community
     */

     public function topFiveAreaCommunity(Request $request)
     {
        $results = ContactTypes::find($this->contact_community->id)->contacts()
        ->selectRaw("contacts.post_code,COUNT(contacts.post_code) AS total_community")
        ->where('contacts.is_deleted', 'false')
        ->orderBy('total_community', 'desc')
        ->groupBy('contacts.post_code')
        ->take(5)->get();
        $res = [];
        foreach( $results as $result ){
            $res[] = [
                'post_code' => $result->post_code,
                'total_community' => (int) $result->total_community
            ];
        }
       return $this->successResponse($res,'Top five area community',200);
     }


    
    /**
     * Get top five pharmacies.
     *
     * @return Response
     */
    public function topFiveAreaPharmacies(Request $request)
    {
        $results = ContactTypes::find(1)->contacts()
        ->selectRaw("contacts.post_code,COUNT(contacts.post_code) AS total_pharmacies")
        ->where('contacts.is_deleted', 'false')
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
       
        $results = ContactTypes::find(1)->contacts()
        ->select('contacts.contact_name','contacts.total_purchase')
        ->where('contacts.is_deleted', 'false')
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
    /**
     * Get contact growth.
     *
     * @return Response
     */
    public function contactGrowth(Request $request)
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

        $total_community = 0;
        $total_pharmacy_db = 0;
        $community_result = [];
        $pharmacy_db_result = [];

        $month = Carbon::now()->month;

        for($i = 1; $i <= $month; $i++){
            $count_community = Contacts::where('contact_type_id', $this->contact_community->id)
                    ->whereMonth('created_date', $i)
                    ->whereYear('created_date', $now)
                    ->where('is_deleted', false)
                    ->count();
            $total_community = $total_community + $count_community;
            $community_result[$months[$i]] = $total_community;

            $count_pharmacy_db = Contacts::where('contact_type_id', $this->contact_pharmacy_db->id)
                    ->whereMonth('created_date', $i)
                    ->whereYear('created_date', $now)
                    ->where('is_deleted', false)
                    ->count();
            $total_pharmacy_db = $total_pharmacy_db + $count_pharmacy_db;
            $pharmacy_db_result[$months[$i]] = $total_pharmacy_db;
        }

        //community
        // $community = [];
        // for($i = 1; $i <= 12; $i++){
        //     $community[$i] = ContactTypes::find($this->contact_community->id)->contacts()
        //     ->whereMonth('created_date', $i)
        //     ->whereYear('created_date', $now)
        //     ->count();
        // }
        // $community_result = [];
        // foreach($community as $key => $value){
        //     $community_result[$months[$key]] = (int) $value;
        // }

        // //pharmacy db
        // $pharmacy_db = [];
        // for($i = 1; $i <= 12; $i++){
        //     $pharmacy_db[$i] = ContactTypes::find($this->contact_pharmacy_db->id)->contacts()
        //     ->whereMonth('created_date', $i)
        //     ->whereYear('created_date', $now)
        //     ->count();
        // }
        // $pharmacy_db_result = [];
        // foreach($pharmacy_db as $key => $value){
        //     $pharmacy_db_result[$months[$key]] = (int) $value;
        // }

        // //subscriber
        // $subscriber = [];
        // for($i = 1; $i <= 12; $i++){
        //     $subscriber[$i] = ContactTypes::find($this->contact_subscriber->id)->contacts()
        //     ->whereMonth('created_date', $i)
        //     ->whereYear('created_date', $now)
        //     ->count();
        // }
        // $subscriber_result = [];
        // foreach($subscriber as $key => $value){
        //     $subscriber_result[$months[$key]] = (int) $value;
        // }

        $res = [
          'Community' => $community_result,
          'Pharmacy Database' => $pharmacy_db_result
        ];
       return $this->successResponse($res,'Contact growth',200);
    }
    /**
     * Get top contact card.
     *
     * @return Response
     */
    public function topContactCard(Request $request)
    {
        $res = [];
        /* if($request->type == 'pharmacies'){
            array_push($res, [
                'total' => 1000,
                'delta' => '+100',
            ]);
        }
        else if($request->type == 'distributors'){
            array_push($res, [
                'total' => 500,
                'delta' => '-20',
            ]);
        }
        else if($request->type == 'pharmacy-contacts'){
            array_push($res, [
                'total' => 150,
                'delta' => '-50',
            ]);
        }
         else  */
         if($request->type == 'pharmacy-database'){

             $prev_month = date('m',strtotime('-1 Month'));
            $current_month = date('m');
            $prev_month_count = ContactTypes::find($this->contact_pharmacy_db->id)->contacts()
            ->whereMonth('created_date', $prev_month)
            ->count();
            $current_month_count = ContactTypes::find($this->contact_pharmacy_db->id)->contacts()
            ->whereMonth('created_date', $current_month)
            ->count();
            $diff =  $current_month_count - $prev_month_count;
            array_push($res, [
                'total' => $current_month_count,
                'delta' => $diff > 0 ? '+'.$diff : $diff,
            ]);
        }
        else if($request->type == 'subscribers'){

            $prev_month = date('m',strtotime('-1 Month'));
            $current_month = date('m');

            $prev_month_count = ContactTypes::find($this->contact_subscriber->id)->contacts()
            ->whereMonth('created_date', $prev_month)
            ->count();

            $current_month_count = ContactTypes::find($this->contact_subscriber->id)->contacts()
            ->whereMonth('created_date', $current_month)
            ->count();

            $diff =  $current_month_count - $prev_month_count;


            array_push($res, [
                'total' => $current_month_count,
                'delta' => $diff > 0 ? '+'.$diff : $diff,
            ]);
        }
        else if($request->type == 'community'){

            $prev_month = date('m',strtotime('-1 Month'));
            $current_month = date('m');

            $prev_month_count = ContactTypes::find($this->contact_community->id)->contacts()
            ->whereMonth('created_date', $prev_month)
            ->count();

            $current_month_count = ContactTypes::find($this->contact_community->id)->contacts()
            ->whereMonth('created_date', $current_month)
            ->count();

            $diff =  $current_month_count - $prev_month_count;


            array_push($res, [
                'total' => $current_month_count,
                'delta' => $diff > 0 ? '+'.$diff : $diff,
            ]);
        }
        else if($request->type == 'general-newsletters'){
            $prev_month = date('m',strtotime('-1 Month'));
            $current_month = date('m');

            $prev_month_count = ContactTypes::find($this->contact_general_newsletter->id)->contacts()
            ->whereMonth('created_date', $prev_month)
            ->count();

            $current_month_count = ContactTypes::find($this->contact_general_newsletter->id)->contacts()
            ->whereMonth('created_date', $current_month)
            ->count();

            $diff =  $current_month_count - $prev_month_count;


            array_push($res, [
                'total' => $current_month_count,
                'delta' => $diff > 0 ? '+'.$diff : $diff,
            ]);
        }
        else {
            return $this->errorResponse('Invalid type',400);
        }
            
       return $this->successResponse($res,'Top contact card',200);
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
                'delta' => $diff > 0 ? '+'.$diff : $diff,
            ]);
        }
        else if($request->type == 'suppliers'){
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
                'delta' => $diff > 0 ? '+'.$diff : $diff,
            ]);
        }
        else if($request->type == 'pharmacy-db'){
            $prev_month = date('m',strtotime('-1 Month'));
            $current_month = date('m');
            $prev_month_count = B2BContactTypes::find($this->contact_pharmacy_db->id)->contacts()
            ->whereMonth('created_date', $prev_month)
            ->count();
            $current_month_count = B2BContactTypes::find($this->contact_pharmacy_db->id)->contacts()
            ->whereMonth('created_date', $current_month)
            ->count();
            $diff =  $current_month_count - $prev_month_count;
            array_push($res, [
                'total' => $current_month_count,
                'delta' => $diff > 0 ? '+'.$diff : $diff,
            ]);
        }
        else {
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

        //pharmacy db
        $pharmacy_db = [];
        for($i = 1; $i <= 12; $i++){
            $pharmacy_db[$i] = B2BContactTypes::find($this->contact_pharmacy_db->id)->contacts()
            ->whereMonth('created_date', $i)
            ->whereYear('created_date', $now)
            ->count();
        }   
        $pharmacy_db_result = [];
        foreach($pharmacy_db as $key => $value){
            $pharmacy_db_result[$months[$key]] = (int) $value;
        }

        $res = [
          'Pharmacies' => $pharmacy_result,
          'Suppliers' => $supplier_result,
          'Pharmacy Database' => $pharmacy_db_result
        ];
       return $this->successResponse($res,'Contact growth',200);
    }

    /**
     * Get top five pharmacies by database.
     */

    public function topFivePharmaciesByDatabase(Request $request)
    {

        $childs = B2BContacts::whereNotNull('contact_parent_id')
        ->selectRaw('COUNT(*) as total, contact_parent_id')
        ->where('is_deleted', 'false')
        ->where('contact_type_id',$this->contact_pharmacy_db->id)
        ->groupBy('contact_parent_id')
        ->orderBy('total', 'desc')
        ->take(5)
        ->get();

        $parent_id = $childs->map(function($element){
            return $element->contact_parent_id;
        });

        $parents = B2BContacts::where('contact_parent_id', null)
        ->orWhere('contact_parent_id', 0)
        ->where('is_deleted', 'false')
        ->whereIn('id',$parent_id)
        ->select('id','contact_name')
        ->get();

        $res = [];

        foreach($parents as $parent){
          foreach($childs as $child){
            if($child->contact_parent_id == $parent->id){
                array_push($res,[
                    "pharmacy"=>$parent->contact_name,
                    "database_size"=>$child->total
                    ]);
            }
          }
        }

       
       return $this->successResponse($res,'Top five pharmacies by database',200);
    }

    // B2B top cards

    /**
     * Get all pharmacy data.
     *
     * @return Response
     */
    public function allPharmacyData(Request $request)
    {
        // default pagination setup
        $sort_column = $request->get('sort') == '' ? 'contacts.id' :  'contacts.' . explode('-',$request->get('sort'))[0];
        $sort_direction = $request->get('sort') == '' ? 'asc' :  explode('-',$request->get('sort'))[1];;
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $search = $request->get('search');

        // query strings
        $query_strings = $request->all();
        $querys = ['postcode','city','country','last_purchase','amount_purchase','total_purchase','average_purchase','created_at_start_date','created_at_end_date'];
        $query_where = [];
        foreach($query_strings as $string => $value){
            if (in_array($string,$querys)){
                if($string == 'created_at_start_date'){
                    array_push($query_where,['created_date','>=',$value]);
                } else if($string == 'created_at_end_date'){    
                    array_push($query_where,['created_date','<=',$value]);
                } else {
                    array_push($query_where,[$string,'=',$value]);
                }
            }
        }

         //basic response metrics
        $records_total = ContactTypes::find($this->contact_pharmacy->id)->contacts()
        ->where('contacts.is_deleted', 'false')
        ->where($query_where)
        ->count();
        $records_filtered = $records_total;

        if($search){
            $search = trim($search);
            $results = ContactTypes::find($this->contact_pharmacy->id)->contacts()
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'like', '%'.$search.'%')
                      ->orWhere('contacts.email', 'like', '%'.$search.'%');
            })
            ->where('contacts.is_deleted', 'false')
            ->where($query_where)
            ->orderBy($sort_column, $sort_direction);
            $records_filtered = $results
            ->count();
            $results = $results 
            ->take($length)
            ->skip($start)
            ->get();
        } else {
            $results = ContactTypes::find($this->contact_pharmacy->id)->contacts()
            ->where('contacts.is_deleted', 'false')
            ->where($query_where)
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
            'data' => $results
        ];
       return $this->successResponse($res,'All pharmacy data',200);
    }

    /**
     * Get pharmacy data by ID.
     *
     * @return Response
     */
    public function pharmacyDataById($id)
    {
        $result = Contacts::find($id);
        if(!$result){
            return $this->errorResponse('Error',404, 'Pharmacy not found');
        }
        $res = [
            'contact_name' => $result->contact_name,
            'post_code' => $result->post_code,
            'total_purchase' => (int) $result->total_purchase
        ];
       return $this->successResponse($result,'Pharmacy data by ID',200);
    }

    /** 
     * Update pharmacy data by ID.
     */

    public function updatePharmacyDataById(Request $request, $id)
    {
        $result = Contacts::find($id);
        if(!$result){
            return $this->errorResponse('Error',404, 'Pharmacy not found');
        }
        $request_data = json_decode($request->getContent(), true);

        // Update the contact
       Contacts::where('id', $id)->update($request_data);

        return $this->successResponse(null,'Pharmacy data updated successfully',200);
    }

    /**
     * Add pharmacy data.
     */
    public function addPharmacyData(Request $request)
    {
        $request_data = json_decode($request->getContent(), true);

        // Create the contact
       // Contacts::create($request_data);
       B2BContacts::insert($request_data);

        return $this->successResponse(null,'Pharmacy data added successfully',200);
    }

    /**
     * Delete pharmacy data by ID.
     */

    public function deletePharmacyDataById($id)
    {
        $result = Contacts::find($id);
        if(!$result){
            return $this->errorResponse('Error',404, 'Pharmacy not found');
        }

        DB::beginTransaction();
        try {
            // Soft Delete the contact childs
            Contacts::find($id)->pharmacyChilds()->update(['is_deleted' => true]);
            // Soft delete the contact
            $result->is_deleted = true;
            $result->save();
            DB::commit();
            return $this->successResponse(null,'Pharmacy data deleted successfully',200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error',500, 'Failed to delete pharmacy data');
        }

         }

    /** 
     * Get all supplier data.
     */
    public function allSupplierData(Request $request)
    {
        // default pagination setup
        $sort_column = $request->get('sort') == '' ? 'contacts.id' :  'contacts.' . explode('-',$request->get('sort'))[0];
        $sort_direction = $request->get('sort') == '' ? 'asc' :  explode('-',$request->get('sort'))[1];;
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $search = $request->get('search');

        //basic response metrics
        $records_total = ContactTypes::find($this->contact_supplier->id)->contacts()
        ->where('contacts.is_deleted', 'false')
        ->count();
        $records_filtered = $records_total;

        if($search){
            $search = trim($search);
            $results = ContactTypes::find($this->contact_supplier->id)->contacts()
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'like', '%'.$search.'%')
                      ->orWhere('contacts.email', 'like', '%'.$search.'%');
            })
            ->where('contacts.is_deleted', 'false')
            ->orderBy($sort_column, $sort_direction);
            $records_filtered = $results
            ->count();
            $results = $results 
            ->take($length)
            ->skip($start)
            ->get();
        } else {
            $results = ContactTypes::find($this->contact_supplier->id)->contacts()
            ->where('contacts.is_deleted', 'false')
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
            'data' => $results
        ];
       
       return $this->successResponse($res,'All supplier data',200);
    }

    /**
     * Add supplier data
     */

     public function addSupplierData(Request $request)
     {
        $request_data = json_decode($request->getContent(), true);

        // Create the contact
       // Contacts::create($request_data);
       B2BContacts::insert($request_data);

        return $this->successResponse(null,'Supplier data added successfully',200);
     }

     /**
      * Update supplier data by ID
      */

        public function updateSupplierDataById(Request $request, $id)
        {
            $result = B2BContacts::find($id);
            if(!$result){
                return $this->errorResponse('Error',404, 'Supplier not found');
            }
            $request_data = json_decode($request->getContent(), true);

            // Update the contact
           B2BContacts::where('id', $id)->update($request_data);

            return $this->successResponse(null,'Supplier data updated successfully',200);
        }
      
     /**
      * Get supplier data by ID
       */   
    public function supplierDataById($id)
    {
        $result = B2BContacts::find($id);
        if(!$result){
            return $this->errorResponse('Error',404, 'Supplier not found');
        }
       return $this->successResponse($result,'Supplier data by ID',200);
    }

    /**
     * Delete supplier data by ID
     */
    public function deleteSupplierDataById($id)
    {
        $result = B2BContacts::find($id);
        if(!$result){
            return $this->errorResponse('Error',404, 'Supplier not found');
        }

        // Soft delete the contact
        $result->is_deleted = true;
        $result->save();

        return $this->successResponse(null,'Supplier data deleted successfully',200);
    }

    /**
     * Get all community data.
     */

    public function allCommunityData(Request $request)
    {

        //weird multi sort
        //$multi_sort = false;
        $sorts = [];
        if($request->get('sort') == ''){
           $sorts = [['contacts.id','desc']];
        } else {
            $cleaned_sorts = preg_replace(['/\[/','/\]/','/"/'],'', $request->get('sort'));
            $sorts = array_map(function($sort){
                $sorted = explode('_',$sort);
                $sort_dir = array_slice($sorted,-1,1);
                $sort_column = implode('_',array_slice($sorted,0,-1));
                return ['contacts.' . $sort_column,$sort_dir[0]];
            },explode(',',$cleaned_sorts));  
        }

        // default pagination setup
       // $sort_column = $request->get('sort') == '' ? 'contacts.id' :  'contacts.' . explode('-',$request->get('sort'))[0];
       // $sort_direction = $request->get('sort') == '' ? 'asc' :  explode('-',$request->get('sort'))[1];;
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $search = $request->get('search');

        // query strings
        $query_strings = $request->all();

        //basic response metrics
        $records_total = ContactTypes::find($this->contact_community->id)->contacts()
        ->where('contacts.is_deleted', false) 
        ->count();
        $records_filtered = $records_total;

        if($search){
            $search = trim($search);
            $results = ContactTypes::find($this->contact_community->id)->contacts()
            ->when($request->get('account_creation_start_date'),function($query,$row){
                $query->where('created_date','>=',"'".$row."'"); 
            })
            ->when($request->get('account_creation_end_date'),function($query,$row){
                $query->where('created_date','<=',"'".$row."'"); 
            })
            ->when($request->get('last_login_start_date'),function($query,$row){
                $query->where('created_date','>=',"'".$row."'");
            })
            ->when($request->get('last_login_end_date'),function($query,$row){  
                $query->where('created_date','<=',"'".$row."'");
            })
            ->when($request->get('amount_likes'),function($query,$row){
                $query->addSelect([
                            'amount_likes'=>UserSavedPosts::selectRaw('COUNT(user_saved_posts.is_like) AS total_likes')
                                                ->where('user_saved_posts.is_like',true)
                                                ->havingRaw('COUNT(user_saved_posts.is_like) <= ?',[$row])
                                                ->whereColumn('contacts.user_id','=','user_saved_posts.user_id')
                ]);
            })
            ->when($request->get('amount_comments'),function($query,$row){
                $query->addSelect([
                            'amount_comments'=>UserComments::selectRaw('COUNT(user_comments.id) AS total_comments')
                                                ->havingRaw('COUNT(user_comments.id) <= ?',[$row])
                                                ->whereColumn('contacts.user_id','=','user_comments.user_id')
                ]);
            })
            ->when($request->get('amount_submissions'),function($query,$row){
                $query->addSelect([
                            'amount_submissions'=>VisitorLikes::selectRaw('COUNT(posts.id) AS total_submissions')
                                                ->havingRaw('COUNT(posts.id) <= ?',[$row])
                                                ->whereColumn('contacts.user_id','=','posts.published_by')
                ]);
            })
            ->when($request->get('subscribed_to_whatsapp'),function($query,$row){
                $query->where('whatsapp_subscription',true);
            })
            ->when($request->get('subscribed_to_email'),function($query,$row){
                $query->where('cansativa_newsletter',true);
            })
            ->when($request->get('applied_filters'),function(Builder $query,$row){
                 foreach ($row as $key => $filter) {
                 $filtered = is_array($filter) ? $filter : json_decode($filter, true);
                 $query = FilterHelper::getFilterQuery($query, $filtered); 
                }
            })
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'like', '%'.$search.'%')
                      ->orWhere('contacts.email', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_name', 'like', '%'. strtoupper($search) .'%');
            })
            ->where('contacts.is_deleted', 'false')->select()
            //->orderBy($sort_column, $sort_direction)->select()
            ->addSelect('created_date as account_creation')
            ->addSelect('created_date as last_login')
           // ->addSelect('cansativa_newsletter as email_subscription')
            ->addSelect([
                            'amount_likes'=>UserSavedPosts::selectRaw('COUNT(user_saved_posts.is_like) AS total_likes')
                                                ->where('user_saved_posts.is_like',true)
                                                ->whereColumn('contacts.user_id','=','user_saved_posts.user_id')
                ])
            ->addSelect([
                            'amount_comments'=>UserComments::selectRaw('COUNT(user_comments.id) AS total_comments')
                                                ->whereColumn('contacts.user_id','=','user_comments.user_id')
                ])
            ->addSelect([
                            'amount_submissions'=>VisitorLikes::selectRaw('COUNT(posts.id) AS total_submissions')
                                                ->whereColumn('contacts.user_id','=','posts.published_by')
                ]);
            
            $records_filtered = $results
            ->count();

            foreach($sorts as $sort){
               $results = $results->orderBy($sort[0],$sort[1]);
            }

            $results = $results
            ->take($length)
            ->skip($start)
            ->get();
           
        } else {
            $results = ContactTypes::find($this->contact_community->id)->contacts()
            ->when($request->get('account_creation_start_date'),function($query,$row){
                $query->where('created_date','>=',"'".$row."'"); 
            })
            ->when($request->get('account_creation_end_date'),function($query,$row){
                $query->where('created_date','<=',"'".$row."'"); 
            })
            ->when($request->get('last_login_start_date'),function($query,$row){
                $query->where('created_date','>=',"'".$row."'");
            })
            ->when($request->get('last_login_end_date'),function($query,$row){  
                $query->where('created_date','<=',"'".$row."'");
            })
            ->when($request->get('amount_likes'),function($query,$row){
                $query->addSelect([
                            'amount_likes'=>UserSavedPosts::selectRaw('COUNT(user_saved_posts.is_like) AS total_likes')
                                                ->where('user_saved_posts.is_like',true)
                                                ->havingRaw('COUNT(user_saved_posts.is_like) <= ?',[$row])
                                                ->whereColumn('contacts.user_id','=','user_saved_posts.user_id')
                ]);
            })
            ->when($request->get('amount_comments'),function($query,$row){
                $query->addSelect([
                            'amount_comments'=>UserComments::selectRaw('COUNT(user_comments.id) AS total_comments')
                                                ->havingRaw('COUNT(user_comments.id) <= ?',[$row])
                                                ->whereColumn('contacts.user_id','=','user_comments.user_id')
                ]);
            })
            ->when($request->get('amount_submissions'),function($query,$row){
                $query->addSelect([
                            'amount_submissions'=>VisitorLikes::selectRaw('COUNT(posts.id) AS total_submissions')
                                                ->havingRaw('COUNT(posts.id) <= ?',[$row])
                                                ->whereColumn('contacts.user_id','=','posts.published_by')
                ]);
            })
            ->when($request->get('subscribed_to_whatsapp'),function($query,$row){
                $query->where('whatsapp_subscription',true);
            })
            ->when($request->get('subscribed_to_email'),function($query,$row){
                $query->where('cansativa_newsletter',true);
            })
            ->when($request->get('applied_filters'),function(Builder $query,$row){
                 foreach ($row as $key => $filter) {
                 $filtered = is_array($filter) ? $filter : json_decode($filter, true);
                 $query = FilterHelper::getFilterQuery($query, $filtered); 
                }
                
            })
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'like', '%'.$search.'%')
                      ->orWhere('contacts.email', 'like', '%'.$search.'%');
            })
            ->where('contacts.is_deleted', 'false') 
            //->orderBy($sort_column, $sort_direction)
            
            ->select()
            ->addSelect('created_date as account_creation')
            ->addSelect('created_date as last_login') 
           // ->addSelect('cansativa_newsletter as email_subscription')
            ->addSelect([
                            'amount_likes'=>UserSavedPosts::selectRaw('COUNT(user_saved_posts.is_like) AS total_likes')
                                                ->where('user_saved_posts.is_like',true)
                                                ->whereColumn('contacts.user_id','=','user_saved_posts.user_id')
                ])
            ->addSelect([
                            'amount_comments'=>UserComments::selectRaw('COUNT(user_comments.id) AS total_comments')
                                                ->whereColumn('contacts.user_id','=','user_comments.user_id')
                ])
            ->addSelect([
                            'amount_submissions'=>VisitorLikes::selectRaw('COUNT(posts.id) AS total_submissions')
                                                ->whereColumn('contacts.user_id','=','posts.published_by')
                ]);
            
            $records_filtered = $results
            ->count();

            foreach($sorts as $sort){
               $results = $results->orderBy($sort[0],$sort[1]);
            }

            /* if($request->get('applied_filters')){
                foreach ($request->get('applied_filters') as $key => $filter) {
                    $filtered = json_decode($filter, true);
                    $results = FilterHelper::getFilterQuery($results, $filtered);
                    //dd($results->toSql());
                }
            } */

            $results = $results 
            ->take($length)
            ->skip($start)
            ->get(); 
        }

        $formatted_results = [];

        foreach($results as $item){
            $check = isset($item->custom_fields);
            //$check_key_mgr = isset($item->account_key_manager_id);
            if($check){
                $fields = json_decode($item->custom_fields, true);
                foreach($fields as $key => $value){
                    $item->$key = $value;
                }
                $formatted_results[] = $item->except('custom_fields');
            }  else {
                $formatted_results[] = $item;
            }

            if(!is_null($item->account_key_manager_id)){
                $key_mgr_data = AccountKeyManagers::where('id',$item->account_key_manager_id)->get();
                 foreach($key_mgr_data as $items){
                     $item->{'key_manager_name'} = $items->manager_name;
                     $item->{'key_manager_email'} = $items->email;
                     $item->{'key_manager_phone'} = $items->phone;
                     $item->{'key_manager_auto_reply'} = $items->auto_reply;
                     $item->{'key_manager_message_template'} = $items->message_template_name;
                } 
                $formatted_results[] = $item->except('account_key_manager_id');
            }
        }
        
        foreach($formatted_results as $item){
             $item = ContactFieldHelper::getContactFieldData($item['id'],$item);
        }
        
        $res = [
            'recordsTotal' => $records_total,
            'recordsFiltered' => $records_filtered,
            'data' => $formatted_results  
        ];
       
       return $this->successResponse($res,'All community data',200);
    }

    /**
     * add community data
     */

     public function addCommunityData(Request $request)
     {
        // Format the request data
       $result = [];
       // Define your start date (e.g., the first day of reading)
        $startDate = Carbon::create(2025, 9, 20); // adjust as needed
        $currentDate = Carbon::today();

        // Calculate how many days have passed
        $dayIndex = $startDate->diffInDays($currentDate);

        // Calculate offset
        $offset = $dayIndex < 0 ? 0 : $dayIndex * 15;

        $user_data = Users::where('user_type','user')->skip($offset)->take(15)->get();
        foreach($user_data as $user){
            $formatted_request_data = [];

           $formatted_request_data['contact_name'] = $user->full_name;
           $formatted_request_data['email'] = $user->email; 
           $formatted_request_data['phone_no'] = $user->phone;
           $formatted_request_data['wa_subscription'] = $user->accept_wa_promotion;
           $formatted_request_data['email_subscription'] = $user->accept_email_promotion;
           $formatted_request_data['comments'] = Users::join('user_comments','users.id','=','user_comments.user_id')->where('users.id',$user->id)->count();
           $formatted_request_data['likes'] =Users::join('user_saved_posts',function($join){
                    $join->on('users.id','=','user_saved_posts.user_id')
                        ->where('user_saved_posts.is_like','=','true');
                })->where('users.id',$user->id)->count();
            $formatted_request_data['submissions'] = Users::join('conversations',function($join){
                    $join->on('users.id','=','conversations.sender_id')
                        ->where('conversations.conversation_type','=','submission');
                })->where('users.id',$user->id)->count();
            $formatted_request_data['contact_type_id'] = $this->contact_community->id;
            $formatted_request_data['created_by'] = 12;

            $account_key_mgr_id = AccountKeyManagers::insertGetId([
                    "manager_name" => $user->full_name,
                    "message_template_name" => null,
                    "auto_reply" => null,
                    "email" => $user->email,
                    "phone" => $user->phone
                ]); 
            $formatted_request_data['account_key_manager_id'] = $account_key_mgr_id;
             array_push($result,$formatted_request_data);
        }
       
       

        

        // Create the contact
       // Contacts::create($request_data);
       Contacts::insert($result);
        $inserted_id = [];
        $inserted_id = Contacts::orderBy('id','desc')->take(count($result))->pluck('id');
       $recorded = [];
        foreach($inserted_id as $id){
                $recorded[] = [
            "type"=>"text",
            "contact_flag" => "b2c",
            "contact_id" => $id,
            "creator_email" => $request->get('creator_email','admin@example.com'),
            "creator_name" => $request->get('creator_name','admin'),
            "description" => json_encode([
                "title"=>"",
                "from"=>"",
                "template"=>"",
                "filename"=> "",
                "campaign_image"=>""
            ])
            ];
        }

        SharedContactLogs::insert($recorded);
        event(new AuditLogged(AuditLogs::MODULE_COMMUNITY, 'Create new Contact'));

        return $this->successResponse($result,'Community data added successfully',200);
     }

     /**
      * update community data by ID
      */

        public function updateCommunityDataById(Request $request, $id)
        {
            $result = Contacts::find($id);
            if(!$result){
                return $this->errorResponse('Error',404, 'Community not found');
            }
            $request_data = json_decode($request->getContent(), true);

            // Format the request data
            $formatted_request_data = [];

            $blocked = ['likes','comments','submissions'];

            foreach($request_data as $key => $value){
                /* if($key == 'email_subscription'){
                    $formatted_request_data['cansativa_newsletter'] = $value;
                } else {
                    $formatted_request_data[$key] = $value;
                } if(in_array($key,$blocked)){} 
            
            else {  */ 
               $formatted_request_data[$key] = $value;
             // }
            }

            // Update the contact
           Contacts::where('id', $id)->update($formatted_request_data);

           SharedContactLogs::insert([
            "type"=>"text",
            "contact_flag" => "b2c",
            "contact_id" => $id,
            "creator_email" => $request->get('creator_email','admin@example.com'),
            "creator_name" => $request->get('creator_name','admin'),
            "description" => json_encode([
                "title"=>"",
                "from"=>"",
                "template"=>"",
                "filename"=> "",
                "campaign_image"=>""
            ])
            ]);  
            event(new AuditLogged(AuditLogs::MODULE_COMMUNITY, 'Edit Contact'));

            return $this->successResponse(null,'Community data updated successfully',200);
        }

      /**
       * get community data by id
       *  */  

      public function communityDataById(Request $request,$id)
      {
         $result = Contacts::where('id', $id);
        if($result->count() == 0){
            return $this->errorResponse('Error',404, 'Community not found');
        }

        $formatted_results = []; 

         foreach($result->get() as $item){
            $check = isset($item->custom_fields);
            //$check_key_mgr = isset($item->account_key_manager_id);
            if($check){
                $fields = json_decode($item->custom_fields, true);
                foreach($fields as $key => $value){
                    $item->$key = $value;
                }
                $formatted_results[] = $item->except('custom_fields');
            }  else {
                $formatted_results[] = $item;
            }

            if(!is_null($item->account_key_manager_id)){
                $key_mgr_data = AccountKeyManagers::where('id',$item->account_key_manager_id)->get();
                 foreach($key_mgr_data as $items){
                     $item->{'key_manager_name'} = $items->manager_name;
                     $item->{'key_manager_email'} = $items->email;
                     $item->{'key_manager_phone'} = $items->phone;
                     $item->{'key_manager_auto_reply'} = $items->auto_reply;
                     $item->{'key_manager_message_template'} = $items->message_template_name;
                } 
                $formatted_results[] = $item->except('account_key_manager_id');
            }

            $formatted_results[] = ContactFieldHelper::getContactFieldData($item->id,$item);   
        
        }         

        //$result = count($formatted_results > 0) ? $formatted_results[0] : null;

       return $this->successResponse($formatted_results[0],'Community data by ID',200);
      } 

      /**
       * delete community data by id
       */
        public function deleteCommunityDataById(Request $request,$id)
        {
            $result = Contacts::where('id',$id)
           // ->where('is_deleted', 'false')
            ->count(); 
            if($result == 0){
                return $this->errorResponse('Error',404, 'Community not found');
            }

            // Soft delete the contact
             $result = Contacts::find($id)->delete();
           // $result->is_deleted = true;
           // $result->save();

             SharedContactLogs::insert([
            "type"=>"text",
            "contact_flag" => "b2c",
            "contact_id" => $id,
            "creator_email" => $request->get('creator_email','admin@example.com'),
            "creator_name" => $request->get('creator_name','admin'),
            "description" => json_encode([
                "title"=>"",
                "from"=>"",
                "template"=>"",
                "filename"=> "",
                "campaign_image"=>""
            ])
            ]);

            event(new AuditLogged(AuditLogs::MODULE_COMMUNITY, 'Delete Contact'));

            return $this->successResponse(null,'Community data deleted successfully',200);
        }

     /** 
      * add general newsletter data
      */
        public function addGeneralNewsletterData(Request $request)
        {
            $request_data = json_decode($request->getContent(), true);

            // Create the contact
           // Contacts::create($request_data);
           Contacts::insert($request_data);

            return $this->successResponse(null,'General newsletter data added successfully',200);
        }

    /**
     * get all general newsletter data
     */
     
     public function allGeneralNewsletterData(Request $request)
     {
        // default pagination setup
        $sort_column = $request->get('sort') == '' ? 'contacts.id' :  'contacts.' . explode('-',$request->get('sort'))[0];
        $sort_direction = $request->get('sort') == '' ? 'asc' :  explode('-',$request->get('sort'))[1];;
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $search = $request->get('search');

        // query strings
        $query_strings = $request->all();
        $querys = ['created_at_start_date','created_at_end_date'];
        $query_where = [];
        foreach($query_strings as $string => $value){
            if (in_array($string,$querys)){
                if($string == 'created_at_start_date'){
                    array_push($query_where,['created_date','>=',$value]);
                } else if($string == 'created_at_end_date'){    
                    array_push($query_where,['created_date','<=',$value]);
                } else {
                    array_push($query_where,[$string,'=',$value]);
                }
            }
        }

        //basic response metrics
        $records_total = ContactTypes::find($this->contact_general_newsletter->id)->contacts()
        ->where('contacts.is_deleted', 'false');

        
            $records_total->where($query_where);
        


        $records_total = $records_total->count();
        $records_filtered = $records_total;

        if($search){
            $search = trim($search);
            $results = ContactTypes::find($this->contact_general_newsletter->id)->contacts()
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'like', '%'.$search.'%')
                      ->orWhere('contacts.email', 'like', '%'.$search.'%');
            })
            ->where('contacts.is_deleted', 'false')
            ->where($query_where)
            ->orderBy($sort_column, $sort_direction);
            $records_filtered = $results
            ->count();
            $results = $results 
            ->take($length)
            ->skip($start)
            ->get(); 
        } else {
            $results = ContactTypes::find($this->contact_general_newsletter->id)->contacts()
            ->where('contacts.is_deleted', 'false')
            ->where($query_where)
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
            'data' => $results
        ];
       
       return $this->successResponse($res,'All general newsletter data',200);
     }

     /**
      * update general newsletter data by ID
      */

        public function updateGeneralNewsletterDataById(Request $request, $id)
        {
            $result = Contacts::find($id);
            if(!$result){
                return $this->errorResponse('Error',404, 'General newsletter not found');
            }
            $request_data = json_decode($request->getContent(), true);

            // Update the contact
           Contacts::where('id', $id)->update($request_data);

            return $this->successResponse(null,'General newsletter data updated successfully',200);
        }

    /**
     *  Delete general newsletter data by ID
     */ 
    public function deleteGeneralNewsletterDataById($id)
    {
        $result = Contacts::find($id);
        if(!$result){
            return $this->errorResponse('Error',404, 'General newsletter not found');
        }

        // Soft delete the contact
        $result->is_deleted = true;
        $result->save();

        return $this->successResponse(null,'General newsletter data deleted successfully',200);
    }

    /**
     * Get general newsletter data by ID
     */

    public function generalNewsletterDataById($id)
    {
        $result = Contacts::find($id);
        if(!$result){
            return $this->errorResponse('Error',404, 'General newsletter not found');
        }
       return $this->successResponse($result,'General newsletter data by ID',200);
    }

    /**
     * Add pharmacy database data
     */

    public function addPharmacyDatabase(Request $request)
    {
        $request_data = json_decode($request->getContent(), true);

        // validate contact_parent_id
        Validator::make($request_data, [
            'contact_parent_id' => 'required|integer'
        ],$messages = ['contact_parent_id.required'=>'Contact parent ID is required. This ID comes from the B2B Pharmacy ID'])->validate();
 

        // Create the contact
       // Contacts::create($request_data);

        // Format the request data
        $formatted_request_data = [];

        $blocked = ['likes','comments','submissions'];

        foreach($request_data as $key => $value){
           /*  if($key == 'email_subscription'){
                $formatted_request_data['cansativa_newsletter'] = $value;
            } else  if(in_array($key,$blocked)){} 
            else */ if($key == 'account_key_manager'){
                $account_key_mgr_data = $value;
                $account_key_mgr_id = AccountKeyManagers::insertGetId([
                    "manager_name" => $account_key_mgr_data["key_manager_name"],
                    "message_template_name" => $account_key_mgr_data["message_template_name"] ?? null,
                    "auto_reply" => $account_key_mgr_data["auto_reply"],
                    "email" => $account_key_mgr_data["email"],
                    "phone" => $account_key_mgr_data["phone"]
                ]);
                $formatted_request_data['account_key_manager_id'] = $account_key_mgr_id;
            }
            else {
                $formatted_request_data[$key] = $value;
            }
        }

      $formatted_request_data['contact_type_id'] = $this->contact_pharmacy_db->id;
      $formatted_request_data['created_by'] = 12;

        $contactId = Contacts::insertGetId($formatted_request_data);
        $userName = Auth::user()->user_name ?? 'cansativa';
        $userEmail = Auth::user()->email ?? 'cansativa';
        $description = [
            'log_type' => "contacts",
            'title' => "Added manually by ". $userName
        ];
        event(new ContactLogged('add_contact', 'b2b', $contactId, null, $description, $userName, $userEmail));
        event(new AuditLogged(AuditLogs::MODULE_PHARMACY_DB, 'Create new Contact'));

        return $this->successResponse(null,'Pharmacy database data added successfully',200);
    }

    /**
     * Get pharmacy database by parent ID.
     */

    public function pharmacyDatabaseByParentId(Request $request,$parentId)
    {
        $results = ContactTypes::find($this->contact_pharmacy_db->id)->contacts()
        ->where('contact_parent_id', $parentId)
        ->where('contacts.is_deleted', 'false')
        ->get();

        if($results->isEmpty()){
            return $this->errorResponse('Error',404, 'Pharmacy database not found');
        }

        //weird multi sort
        //$multi_sort = false;
        $sorts = [];
        if($request->get('sort') == ''){
           $sorts = [['contacts.id','desc']];
        } else {
            $cleaned_sorts = preg_replace(['/\[/','/\]/','/"/'],'', $request->get('sort'));
            $sorts = array_map(function($sort){
                $sorted = explode('_',$sort);
                $sort_dir = array_slice($sorted,-1,1);
                $sort_column = implode('_',array_slice($sorted,0,-1));
                return ['contacts.' . $sort_column,$sort_dir[0]];
            },explode(',',$cleaned_sorts));  
        }

        //default pagination setup
       // $sort_column = explode('-',$request->get('sort'))[0] ?? 'contacts.id';
       // $sort_direction = explode('-',$request->get('sort'))[1] ?? 'asc';
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $search = $request->get('search');

        //basic response metrics
        $records_total = $results->count();
        $records_filtered = $records_total;

        if($search){
            $search = trim($search);
            $results = ContactTypes::find($this->contact_pharmacy_db->id)->contacts()
            ->where('contact_parent_id', $parentId)
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'like', '%'.$search.'%')
                      ->orWhere('contacts.email', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_name', 'like', '%'. strtoupper($search) .'%');
            })
            ->when($request->get('applied_filters'),function(Builder $query,$row){
                 foreach ($row as $key => $filter) {
                 $filtered = is_array($filter) ? $filter : json_decode($filter, true);
                 $query = FilterHelper::getFilterQuery($query, $filtered); 
                }
            })
            ->where('contacts.is_deleted', 'false');
            //->orderBy($sort_column, $sort_direction);
            $records_filtered = $results
            ->count();

            foreach($sorts as $sort){
               $results = $results->orderBy($sort[0],$sort[1]);
            }

            $results = $results 
            ->take($length)
            ->skip($start)
            ->get();
        } else {
            $results = ContactTypes::find($this->contact_pharmacy_db->id)->contacts()
            ->where('contact_parent_id', $parentId)
            ->when($request->get('applied_filters'),function(Builder $query,$row){
                 foreach ($row as $key => $filter) {
                 $filtered = is_array($filter) ? $filter : json_decode($filter, true);
                 $query = FilterHelper::getFilterQuery($query, $filtered); 
                }
            })
            ->where('contacts.is_deleted', 'false');
           // ->orderBy($sort_column, $sort_direction);
            $records_filtered = $results
            ->count();

            foreach($sorts as $sort){
               $results = $results->orderBy($sort[0],$sort[1]);
            }

            $results = $results 
            ->take($length)
            ->skip($start)
            ->get();
        }

         $formatted_results = [];

        foreach($results as $item){
            $check = isset($item->custom_fields);
            if($check){
                $fields = json_decode($item->custom_fields, true);
                foreach($fields as $key => $value){
                    $item->$key = $value;
                }
                $formatted_results[] = $item->except('custom_fields');
            } else {
                $formatted_results[] = $item;
            }

            if(!is_null($item->account_key_manager_id)){
                $key_mgr_data = AccountKeyManagers::where('id',$item->account_key_manager_id)->get();
                 foreach($key_mgr_data as $items){
                     $item->{'key_manager_name'} = $items->manager_name;
                     $item->{'key_manager_email'} = $items->email;
                     $item->{'key_manager_phone'} = $items->phone;
                     $item->{'key_manager_auto_reply'} = $items->auto_reply;
                     $item->{'key_manager_message_template'} = $items->message_template_name;
                } 
                $formatted_results[] = $item->except('account_key_manager_id');
            }

             $formatted_results[] = ContactFieldHelper::getContactFieldData($item->id,$item);
            
        }

        $res = [
            'recordsTotal' => $records_total,
            'recordsFiltered' => $records_filtered,
            'data' => $formatted_results
        ];

       return $this->successResponse($res,'Pharmacy database by parent ID',200);
    }

    /**
     * Update pharmacy database by ID.
     */

    public function updatePharmacyDatabaseByParentIdAndId(Request $request, $parentId, $id)
    {
       // $parent = B2BContacts::find($parentId)->get();

        $result = Contacts::where('id', $id)
        ->where('contact_parent_id', $parentId)
        ->first();
        if(!$result){
            return $this->errorResponse('Error',404, 'Pharmacy database not found');
        }

        try {
            $request_data = json_decode($request->getContent(), true);
            DB::beginTransaction();
            // Fill data first
            $result->fill($request_data);

            // Compare before save
            $dirty = collect($result->getDirty())->except(['updated_date']);
            $original = $result->getOriginal();

            // log changes
            $userName = Auth::user()->user_name ?? 'cansativa';
            $userEmail = Auth::user()->email ?? 'cansativa';

            $editPhone = false;
            $editcountryCode = false;
            foreach ($dirty as $attr => $newValue) {
                $field_name = ColumnMappings::where('field_name', $attr)->where('contact_type_id', 5)->value('display_name');
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
                event(new ContactLogged('edit_contact', 'b2b', $result->id, null, $description, $userName, $userEmail));
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
                event(new ContactLogged('edit_contact', 'b2b', $result->id, null, $description, $userName, $userEmail));    
            }

            // save updated data
            $result->save();
            $result->refresh();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error',500, 'Failed to update pharmacy database: ' . $e->getMessage());
        }

        event(new AuditLogged(AuditLogs::MODULE_PHARMACY_DB, 'Edit Contact'));

        return $this->successResponse(null,'Pharmacy database data updated successfully',200);
    }

    /**
     * Get pharmacy database data by ID.
     */

    public function pharmacyDatabaseByParentIdAndId(Request $request, $parentId, $id)
    {
        $parent = Contacts::where('contact_parent_id', $parentId)
        ->where('id',$id)
        ->where('is_deleted', 'false')
        ->count();
        if($parent == 0){
            return $this->errorResponse('Error',404, 'Pharmacy database not found');
        }
        $result = Contacts::where('id', $id)
            ->where('contact_parent_id', $parentId)->first();

        $formatted_results = []; 

        foreach($result->toArray() as $key => $value){
            if($key == 'custom_fields'){
               if(!is_null($value)){
                $json = json_decode($value, true);
                foreach($json as $field_key => $field_value){
                    $formatted_results[$field_key] = $field_value;
                }
               }
            } else if($key == 'account_key_manager_id'){
                $key_mgr_data = AccountKeyManagers::where('id',$value)->get();
                 foreach($key_mgr_data as $items){
                    $formatted_results['key_manager_name'] = $items->manager_name;
                    $formatted_results['key_manager_email'] = $items->email;
                    $formatted_results['key_manager_phone'] = $items->phone;
                    $formatted_results['key_manager_auto_replay'] = $items->auto_reply;
                    $formatted_results['key_manager_message_template'] = $items->message_template_name;
                } 
            }
            else {
            $formatted_results[$key] = $value; 
            }
            
        }

        $formatted_results = ContactFieldHelper::getContactFieldData($formatted_results['id'],$formatted_results);
        
        
                  
        
    
        return $this->successResponse($formatted_results,'Pharmacy database data by ID',200);
    }

    /**
     * Delete pharmacy database by id
     */

     public function deletePharmacyDatabaseByParentIdAndId(Request $request,$parentId, $id)
     {
        $parent = Contacts::where('contact_parent_id', $parentId)
        ->where('id',$id)
       // ->where('is_deleted', 'false')
        ->count();
        if($parent == 0){
            return $this->errorResponse('Error',404, 'Pharmacy database not found');
        }
        
        DB::beginTransaction();
        try {
            // Soft delete the contact
            Contacts::where('id',$id)->delete();//->update(['is_deleted' => true]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error',500, 'Failed to delete pharmacy database: ' . $e->getMessage());
        }

        event(new AuditLogged(AuditLogs::MODULE_PHARMACY_DB, 'Delete Contact'));
        return $this->successResponse(['parent'=>$parentId,'id'=>$id],'Pharmacy database data deleted successfully',200);
     }


     /**
      * Add subscriber data
      */

        public function addSubscriberData(Request $request)
        {
            $request_data = json_decode($request->getContent(), true);

            // Create the contact
           // Contacts::create($request_data);
           Contacts::insert($request_data);

            return $this->successResponse(null,'Subscriber data added successfully',200);
        }


        /**
         *  Get all subscriber data
         */

         public function allSubscriberData(Request $request)
         {
            // default pagination setup
            $sort_column = $request->get('sort') == '' ? 'contacts.id' :  'contacts.' . explode('-',$request->get('sort'))[0];
            $sort_direction = $request->get('sort') == '' ? 'asc' :  explode('-',$request->get('sort'))[1];;
            $start = $request->get('start', 0);
            $length = $request->get('length', 10);
            $search = $request->get('search');

            //basic response metrics
            $records_total = ContactTypes::find($this->contact_subscriber->id)->contacts()
            ->where('contacts.is_deleted', 'false')
            ->count();
            $records_filtered = $records_total;

            if($search){
            $search = trim($search);
            $results = ContactTypes::find($this->contact_subscriber->id)->contacts()
            ->when($request->get('amount_likes'),function($query,$row){
                $query->addSelect([
                            'amount_likes'=>UserSavedPosts::selectRaw('COUNT(user_saved_posts.is_like) AS total_likes')
                                                ->where('user_saved_posts.is_like',true)
                                                ->havingRaw('COUNT(user_saved_posts.is_like) <= ?',[$row])
                                                ->whereColumn('contacts.user_id','=','user_saved_posts.user_id')
                ]);
            })
            ->when($request->get('amount_comments'),function($query,$row){
                $query->addSelect([
                            'amount_comments'=>UserComments::selectRaw('COUNT(user_comments.id) AS total_comments')
                                                ->havingRaw('COUNT(user_comments.id) <= ?',[$row])
                                                ->whereColumn('contacts.user_id','=','user_comments.user_id')
                ]);
            })
            ->when($request->get('amount_submissions'),function($query,$row){
                $query->addSelect([
                            'amount_submissions'=>VisitorLikes::selectRaw('COUNT(posts.id) AS total_submissions')
                                                ->havingRaw('COUNT(posts.id) <= ?',[$row])
                                                ->whereColumn('contacts.user_id','=','posts.published_by')
                ]);
            })
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'like', '%'.$search.'%')
                      ->orWhere('contacts.email', 'like', '%'.$search.'%');
            })
            ->where('contacts.is_deleted', 'false')
            ->orderBy($sort_column, $sort_direction);

            $records_filtered = $results
            ->count();
            $results = $results
            ->take($length)
            ->skip($start)
            ->get();
        } else {
            $results = ContactTypes::find($this->contact_subscriber->id)->contacts()
            ->when($request->get('amount_likes'),function($query,$row){
                $query->addSelect([
                            'amount_likes'=>UserSavedPosts::selectRaw('COUNT(user_saved_posts.is_like) AS total_likes')
                                                ->where('user_saved_posts.is_like',true)
                                                ->havingRaw('COUNT(user_saved_posts.is_like) <= ?',[$row])
                                                ->whereColumn('contacts.user_id','=','user_saved_posts.user_id')
                ]);
            })
            ->when($request->get('amount_comments'),function($query,$row){
                $query->addSelect([
                            'amount_comments'=>UserComments::selectRaw('COUNT(user_comments.id) AS total_comments')
                                                ->havingRaw('COUNT(user_comments.id) <= ?',[$row])
                                                ->whereColumn('contacts.user_id','=','user_comments.user_id')
                ]);
            })
            ->when($request->get('amount_submissions'),function($query,$row){
                $query->addSelect([
                            'amount_submissions'=>VisitorLikes::selectRaw('COUNT(posts.id) AS total_submissions')
                                                ->havingRaw('COUNT(posts.id) <= ?',[$row])
                                                ->whereColumn('contacts.user_id','=','posts.published_by')
                ]);
            })
            ->where('contacts.is_deleted', 'false')
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
                'data' => $results
            ];

           return $this->successResponse($res,'All subscriber data',200);
         }       

    /**
     * update subscriber data by ID
     */     

                public function updateSubscriberDataById(Request $request, $id)
                {
                    $result = Contacts::find($id);
                    if(!$result){
                        return $this->errorResponse('Error',404, 'Subscriber not found');
                    }
                    $request_data = json_decode($request->getContent(), true);

                    // Update the contact
                   Contacts::where('id', $id)->update($request_data);

                    return $this->successResponse(null,'Subscriber data updated successfully',200);
                }

    /**
     * Get subscriber data by ID
     */

    public function subscriberDataById($id)
    {
        $result = Contacts::find($id);
        if(!$result){
            return $this->errorResponse('Error',404, 'Subscriber not found');
        }
       return $this->successResponse($result,'Subscriber data by ID',200);
    }

    /**
     * Delete subscriber data by ID
     */

    public function deleteSubscriberDataById($id)
    {
        $result = Contacts::find($id);
        if(!$result){
            return $this->errorResponse('Error',404, 'Subscriber not found');
        }

        // Soft delete the contact
        $result->is_deleted = true;
        $result->save();

        return $this->successResponse(null,'Subscriber data deleted successfully',200);
    }


    /**
     * Upload file to MinIO.
     */
    public function minioUpload(Request $request)
    {
        // Validate the request
        $request->validate([
            'file_name' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048', // Adjust the validation rules as needed
        ]);

        // Get the file from the request
        $file = $request->file('file_name');

        // Define the path where you want to store the file
        $path = '/b2c/contact/community';

        try {
            // Check if the file already exists
           // Store the file in MinIO 
      // Storage::disk('minio')->put($path, file_get_contents($file));

       $link = $file->store($path,'minio');

            // Return the path of the uploaded file
        return $this->successResponse(['link' => env('MINIO_URL') . '/' .$link], 'File uploaded successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error', 500, 'Failed to upload: ' . $e->getMessage());
        }
        
    }

    /**
     * Get WooCommerce customers.   
     */

    public function woocommerceCustomers(Request $request)
    {
       
        $woocommerce = new WooClient(
                env('WOOCOMERCE_API_URL'),
                env('WOOCOMMERCE_CLIENT_KEY'),
                env('WOOCOMMERCE_CLIENT_SECRET'),
                [
                        'timeout' => '29',
                        'wp_api' => true,
                        'version' => 'wc/v2'
                ]
                );        
      $woo_response = $woocommerce->get('customers?per_page=15');
         // $id = Contacts::orderBy('id','desc')->first()->id;
        $last_id = 0;
        foreach($woo_response as $key){
       // $last_id = $last_id == 0 ? $id + 1 : $last_id + 1;
        $check = Contacts::where('email', $key->billing->email)
        ->where('contact_name', $key->billing->company)
        ->first();
        if($check){
            continue; // Skip if contact already exists
        }
        DB::beginTransaction();
        try {
        $contact = new Contacts();
       // $contact->id = $last_id ;
        $contact->contact_name = $key->billing->company;
        $contact->contact_no = "";
        $contact->address = $key->billing->address_1 . " " . $key->billing->address_2;
        $contact->post_code = $key->billing->postcode;
        $contact->city = $key->billing->city;
        $contact->country = $key->billing->country;
        $contact->state = $key->billing->state;
        $contact->contact_person = "-";
        $contact->email = $key->billing->email;
        $contact->phone_no = "-";
        $contact->amount_purchase = "0.00";
        $contact->total_purchase = "0.00";
        $contact->average_purchase = "0.00";
        $contact->last_purchase_date = "2025-05-20";
        $contact->cansativa_newsletter = null;
        $contact->community_user = null;
        $contact->whatsapp_subscription = null;
        $contact->contact_type_id = 1;
        $contact->contact_parent_id = null;
        $contact->created_by = 12;
        $contact->created_date = date("Y-m-d H:i:s");
        $contact->updated_by = 12;
        $contact->updated_date = date("Y-m-d H:i:s");
        $contact->save();  
        DB::commit();
        } catch (Exceptions $e) {
            DB::rollback();
            return $this->errorResponse('Error',500, $e->message);
        }
        } 

        return $this->successResponse([
            //'type' => dump($woo_response),
         // $woo_response,
         //  $last_id,
           "tes"
        ],'Success',200);
    }
    

    /**
     * xlsx export
     */

     public function exportData(Request $request)
     {
            $contact = $request->contact_type; 
            $contact_type = [
                'community'=>$this->contact_community->id,
                'subscriber'=>$this->contact_subscriber->id,
                'pharmacy-database'=>$this->contact_pharmacy_db->id
            ];

           if(!array_key_exists($contact,$contact_type)){
                return $this->errorResponse('invalid contact_type',400);
           }

           $results = ContactTypes::find($contact_type[$contact])
           ->contacts()
           ->when($request->get('parent_id'),function ($query,$parent_id){
                $query->where("contacts.contact_parent_id",$parent_id);
           })
           ->when($request->get('applied_filters'),function(Builder $query,$row){
                 foreach ($row as $key => $filter) {
                 $filtered = is_array($filter) ? $filter : json_decode($filter, true);
                 $query = FilterHelper::getFilterQuery($query, $filtered); 
                }
            }) 


/* 
            ->when($request->input('applied_filters.amount_likes'),function($query,$filter){
                $amount_likes_id = UserSavedPosts::select('user_id')
                                ->where('is_like',true)
                                ->groupBy('user_id')
                                ->havingRaw('COUNT(user_id) <= ?',[$filter])
                                ->pluck('user_id');
                $query->whereIn('contacts.user_id',$amount_likes_id);
           })
          ->when($request->input('applied_filters.amount_comments'),function($query,$filter){
                 $amount_comments_id = UserComments::select('user_id')
                                  ->groupBy('user_id')
                                  ->havingRaw('COUNT(user_id) <= ?',[$filter])
                                  ->pluck('user_id');
                 $query->whereIn('contacts.user_id',$amount_comments_id);
          })
          ->when($request->input('applied_filters.amount_submissions'),function($query,$filter){
                     $amount_submissions_id = VisitorLikes::select('published_by')
                                    ->groupBy('published_by')
                                    ->havingRaw('COUNT(published_by) <= ?',[$filter])
                                    ->pluck('published_by');
                     $query->whereIn('contacts.user_id',$amount_submissions_id);
         })
         ->when($request->input('applied_filters.account_creation_start_date'),function($query,$filter){
                $query->whereRaw('contacts.created_date >= ' ."'" . $filter . "'");
         })
         ->when($request->input('applied_filters.account_creation_end_date'),function($query,$filter){
                $query->whereRaw('contacts.created_date <= ' . "'" . $filter . "'");
         })
          ->when($request->input('applied_filters.last_login_start_date'),function($query,$filter){
                $query->whereRaw('contacts.created_date >= ' ."'" . $filter . "'");
         })
         ->when($request->input('applied_filters.last_login_end_date'),function($query,$filter){
                $query->whereRaw('contacts.created_date <= ' . "'" . $filter . "'");
         })
         ->when($request->input('applied_filters.city',''),function($query,$filter){
                $query->where('contacts.city', 'like', '%' . $filter . '%');
         }) */

           ->addSelect([
                            'total_likes'=>UserSavedPosts::selectRaw('COUNT(user_saved_posts.is_like) AS total_likes')
                                                ->where('user_saved_posts.is_like',true)
                                                ->whereColumn('contacts.user_id','=','user_saved_posts.user_id'),
                            'total_submissions'=>VisitorLikes::selectRaw('COUNT(posts.published_by) AS total_submissions')
                                                ->whereColumn('contacts.user_id','=','posts.published_by'),
                           'total_comments'=> UserComments::selectRaw('COUNT(user_comments.user_id) AS total_comments')
                                                ->whereColumn('contacts.user_id','=','user_comments.user_id'),
                            'account_creation'=>Contacts::selectRaw('created_date')
                                                ->whereColumn('contacts.user_id','=','id'),
                            'last_login'=>Contacts::selectRaw('created_date')
                                                ->whereColumn('contacts.user_id','=','id'),
                        ])
           ->where('is_deleted',false);

           $count = $results->count();

           $limit = 25;

           $chunk_size = ceil($count / $limit);

           $chunk = 0;

           $spreadsheet = new Spreadsheet();

           $default_header = [];

           switch($contact) {
             case 'community':
                $default_header = ['Full Name','Email','Phone Number','WhatsApp Subscription','Email Subscription','Likes','Comments','Submissions','Account Creation','Latest Login'];
                break;
           }

           while($chunk < $chunk_size){
                $data = $results
                        ->skip($chunk * $limit)
                        ->take($limit)
                        ->get();

                $custom_fields = []; 
                $custom_fields_with_id = [];       
                foreach($data as $item){
                    $custom_fields[] = ContactFieldHelper::getContactFieldDataCustomOnly($item->id);
                    $custom_fields_with_id[] = ContactFieldHelper::getContactFieldDataCustomOnlyWithContactId($item->id);
                }          

                //var_dump($custom_fields);

                if($contact == 'general_newsletter' || $contact == 'subscriber'){

                $sheet = $chunk == 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
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
                $sheet->setCellValue('D' . $rows, $row['whatsapp_subscription']);
                $sheet->setCellValue('E' . $rows, $row['cansativa_newsletter']);
                $sheet->setCellValue('F' . $rows, date('d F Y',strtotime($row['created_date'])));
                $rows++;
                }

                } else {       

                $sheet = $chunk == 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
                $col = 'A';
                $count = 0;
                foreach($default_header as $header){
                    $col_name = TranslatorHelper::getTranslate($header,'de');
                    $sheet->setCellValue($col . '1', $col_name);
                    $col++;
                    $count++;
                /* $sheet->setCellValue('A1', 'Full Name');
                $sheet->setCellValue('B1', 'Email'); 
                $sheet->setCellValue('C1', 'Phone Number');
                $sheet->setCellValue('D1', 'Whatsapp Subscription');
                $sheet->setCellValue('E1', 'Email Subscription');
                $sheet->setCellValue('F1', 'Likes');
                $sheet->setCellValue('G1', 'Comments');
                $sheet->setCellValue('H1', 'Submissions');
                $sheet->setCellValue('I1', 'Account Creation');
                $sheet->setCellValue('J1', 'Latest Login'); */
                }
 
 
                foreach($custom_fields as $items){
                   // var_dump($items);
                        if (count($items) > 0){
                            // contain extra contact fields
                            foreach($items as $key => $val){
                                $sheet->setCellValue($col . '1', $key);
                                $col++;
                            }
                            break;
                        }
                 }

               


                $rows = 2;  
                foreach($data as $row){
                $sheet->setCellValue('A' . $rows, $row['contact_name']);
                $sheet->setCellValue('B' . $rows, $row['email']);
                $sheet->setCellValue('C' . $rows, $row['phone_no']);
                $sheet->setCellValue('D' . $rows, $row['whatsapp_subscription']);
                $sheet->setCellValue('E' . $rows, $row['cansativa_newsletter']);
                $sheet->setCellValue('F' . $rows, $row['total_likes']);
                $sheet->setCellValue('G' . $rows, $row['total_comments']);
                $sheet->setCellValue('H' . $rows, $row['total_submissions']);
                $sheet->setCellValue('I' . $rows, date('d F Y',strtotime($row['account_creation'])));
                $sheet->setCellValue('J' . $rows, date('d F Y',strtotime($row['created_date'])));

                            foreach($custom_fields_with_id as $items){
                                if(count($items) > 1){
                                    //continue from static default data column
                                    $col = 'K';
                                    //contains extra contacts field
                                    if($items['contact_id'] == $row['id']){
                                        foreach($items as $key => $val){
                                            if($key != 'contact_id'){
                                                $sheet->setCellValue($col . $rows, $val);
                                                $col++;
                                            }
                                        }
                                    //echo $col;
                                    }
                                    
                                }
                                
                            }

                $rows++;
                }

                }

                $chunk++;

           }

        
            $filename = date('YmdHis') . "-" . $contact . ".xlsx";
            $path = public_path($filename);
            //$path = sys_get_temp_dir() . "/" . $filename;
            $writer = new Xlsx($spreadsheet); 
            $writer->save($path);

          /*   $filed = new UploadedFile(
                        $path, // Path to the file
                        $filename, // Original file name
                        null, // MIME type (optional, null will auto-detect)
                        null, // File size (optional, null will auto-detect)
                        true // Test mode (true for temporary files)
                    );

            $request_body = new Request();
            $request_body->files->set('file',$filed);
            $response = (new B2BContactAdjustmentController())->handleFileUpload($request_body);
            $links = ($response->getData())->file_url; */

           $brevo_id = 0;
           $recipient = [];

           if($request->get('export_to') == 'whatsapp'){

            $recipient = Contacts::where('user_id',$request->get('user_id'))->get();
            
                try {
                    $endpoint = env('WHATSAPP_API_URL') . '/' . env('WHATSAPP_PHONE_NUMBER_ID') . '/messages';
                    $response = Http::withToken(env('WHATSAPP_API_TOKEN'))
                    ->post($endpoint, [
                        'messaging_product' => 'whatsapp',
                        'recipient_type' => 'individual',
                        'to' => str_replace('+', '', $recipient[0]->country_code) . $recipient[0]->phone_no,
                        'type' => 'template',
                        'template' => [
                            'name' => $request->get('wa_template_name','report_template_cta'),
                            'language' => [
                                'code' => 'en'
                            ],
                            'components' => [
                               [
                                 'type' => 'button',
                                 'sub_type' => 'url',
                                 'index' => 0,
                                 'parameters' => [[
                                                'type' => 'text',
                                                'text' => $links
                                                 ]]
                               ]
                            ]
                        ]
                    ]);

                    $responseData = $response->throw();
                }
                catch(Exception $e){
                    return $this->errorResponse('Error',500, 'Failed to send whatsapp: ' . $e->getMessage());
                }

          } 
          
          if($request->get('export_to') == 'email'){

            $recipient = Contacts::where('user_id',$request->get('user_id'))->get();

            try {

            $campaign = Http::withHeaders([
                'api-key' => env('BREVO_API_KEY'),
                'content-type' => 'application/json',
                'accept' => 'application/json'
            ])->post(env('BREVO_API_URL') . '/smtp/email', [
                'name' => 'Contact Data Export',
                'subject' => 'Your Contact Data Report is Ready',
                'sender' => [
                    'name' => env('MAIL_FROM_NAME','Cansativa'),
                    'email' => env('MAIL_FROM_ADDRESS_PROD','notification@cansativa.de'),
                ],
                'htmlContent' => "<html><body><h1>Please download your report</h1><p><a href='".$links."'>Here</a></p></body></html>",
                'to' => [
                    [
                        'email' => $recipient[0]->email
                    ]
                ]
            ]);

            $campaign->throw();

            $brevo_id = $campaign;

        }
            catch(Exception $e){
                return $this->errorResponse('Error',500, 'Failed to send email: ' . $e->getMessage());
 
            }

        }


               $export_to = $request->get('export_to') == 'email' ? 'Email Subscription' : ($request->get('export_to') == 'whatsapp' ? 'WhatsApp Subscription' : 'XLSX-Export');

               HistoryExports::insert([
                'contact_name' => $request->contact_name,
                'contact_type' => $request->contact_type,
                'applied_filters' => ($request->get("applied_filters")) == "" ? null : json_encode($request->applied_filters),
                'export_to'=> $export_to,
                'amount_contacts' => $count,
               // 'amount_of_contacts' => $count,
                'created_date' => date('Y-m-d H:i:s')
            ]);    

            

           return $this->successResponse([
                //"filename"=>$links
                "filename"=>null
            ],'successfully exported file',200);

           
     }

     public function exportDataB2B(Request $request)
     {
            $contact = $request->contact_type;
            $contact_type = [
                'pharmacy'=>$this->contact_pharmacy->id,
                'supplier'=>$this->contact_supplier->id,
                'general_newsletter'=>$this->contact_general_newsletter->id
            ];

           if(!array_key_exists($contact,$contact_type)){
                return $this->errorResponse('invalid contact_type',400);
           }

           $count = B2BContactTypes::find($contact_type[$contact])
           ->contacts()
           ->where('is_deleted',false)
           ->count();

           $limit = 25;

           $chunk_size = ceil($count / $limit);

           $chunk = 0;

           $spreadsheet = new Spreadsheet();

           while($chunk < $chunk_size){
                $data = B2BContactTypes::find($contact_type[$contact])
                        ->contacts()
                        ->where('is_deleted',false)
                        ->skip($chunk * $limit)
                        ->take($limit)
                        ->get();

                $sheet = $chunk == 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
                $sheet->setCellValue('A1', 'Pharmacy Name');
                $sheet->setCellValue('B1', 'Pharmacy Number');
                $sheet->setCellValue('C1', 'Address');
                $sheet->setCellValue('D1', 'Postcode');
                $sheet->setCellValue('E1', 'Country');
                $sheet->setCellValue('F1', 'State');
                $sheet->setCellValue('G1', 'Contact Person');
                $sheet->setCellValue('H1', 'Email'); 
                $sheet->setCellValue('I1', 'Phone Number');
                $sheet->setCellValue('J1', 'Amount of Purchase');
                $sheet->setCellValue('K1', 'Average of Purchase');
                $sheet->setCellValue('L1', 'Total Purchase');
                $sheet->setCellValue('M1', 'Last Purchase Date');
                $sheet->setCellValue('N1', 'Created At');
                $rows = 2;
                foreach($data as $row){
                $sheet->setCellValue('A' . $rows, $row['contact_name']);
                $sheet->setCellValue('B' . $rows, $row['contact_no']);
                $sheet->setCellValue('C' . $rows, $row['address']);
                $sheet->setCellValue('D' . $rows, $row['postcode']);
                $sheet->setCellValue('E' . $rows, $row['country']);
                $sheet->setCellValue('F' . $rows, $row['state']);
                $sheet->setCellValue('G' . $rows, $row['contact_person']);
                $sheet->setCellValue('H' . $rows, $row['email']);
                $sheet->setCellValue('I' . $rows, $row['phone_no']);
                $sheet->setCellValue('J' . $rows, $row['amount_purchase']);
                $sheet->setCellValue('K' . $rows, $row['average_purchase']);
                $sheet->setCellValue('L' . $rows, $row['total_purchase']);
                $sheet->setCellValue('M' . $rows, date('d F Y',strtotime($row['last_purchase_date'])));
                $sheet->setCellValue('N' . $rows, date('d F Y',strtotime($row['created_date'])));
                
                $rows++;
                }

                $chunk++;

           }

        
            $filename = date('YmdHis') . "-" . $contact . ".xlsx";
            $writer = new Xlsx($spreadsheet); 
            $writer->save($filename);

           return $this->successResponse([
                "filename"=>url('public/' . $filename)
            ],'successfully exported file',200);

           
     }

      public function exportContactLogs(Request $request)
     {
           
           $count = SharedContactLogs::count();

           $limit = 25;

           $chunk_size = ceil($count / $limit);

           $chunk = 0;

           $spreadsheet = new Spreadsheet();

           while($chunk < $chunk_size){
                $data = SharedContactLogs::skip($chunk * $limit)
                        ->take($limit)
                        ->get();

                $sheet = $chunk == 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
                $sheet->setCellValue('A1', 'Log Type');
                $sheet->setCellValue('B1', 'Contact Type');
                $sheet->setCellValue('C1', 'Contact ID');
                $sheet->setCellValue('D1', 'Campaign ID');
                $sheet->setCellValue('E1', 'Creator Email');
                $sheet->setCellValue('F1', 'Creator Name');
                $sheet->setCellValue('G1', 'Description');
                $sheet->setCellValue('H1', 'Created At');
                $rows = 2;
                foreach($data as $row){
                $sheet->setCellValue('A' . $rows, $row['type']);
                $sheet->setCellValue('B' . $rows, $row['contact_flag']);
                $sheet->setCellValue('C' . $rows, $row['contact_id']);
                $sheet->setCellValue('D' . $rows, $row['campaign_id']);
                $sheet->setCellValue('E' . $rows, $row['creator_email']);
                $sheet->setCellValue('F' . $rows, $row['creator_name']);
                $sheet->setCellValue('G' . $rows, $row['description']);
                $sheet->setCellValue('H' . $rows, date('d F Y',strtotime($row['created_date'])));
                
                $rows++;
                }

                $chunk++;

           }

        
            $filename = date('YmdHis') . "-contact-logs.xlsx";
            $path = public_path($filename);
            $writer = new Xlsx($spreadsheet); 
            $writer->save($path);

           return $this->successResponse([
                "filename"=>url($path)
            ],'successfully exported file',200);

           
     }

     public function sampleData(Request $request)
     {
           /*  $contact = $request->contact_type;
            $contact_type = [
                'pharmacy'=>$this->contact_pharmacy->id,
                'supplier'=>$this->contact_supplier->id,
                'general_newsletter'=>$this->contact_general_newsletter->id
            ];

           if(!array_key_exists($contact,$contact_type)){
                return $this->errorResponse('invalid contact_type',400);
           } */

           $contact_types_b2c = ContactTypes::all();
           $contact_types_b2b = B2BContactTypes::all();

           $spreadsheet = new Spreadsheet();

           foreach($contact_types_b2c as $contact){

            $data = ContactTypes::find($contact->id)
                        ->contacts()
                        ->get();

                $sheet = $spreadsheet->createSheet();
                $sheet->setTitle($contact->contact_type_name);
                $sheet->setCellValue('A1', 'Pharmacy Name');
                $sheet->setCellValue('B1', 'Pharmacy Number');
                $sheet->setCellValue('C1', 'Address');
                $sheet->setCellValue('D1', 'Postcode');
                $sheet->setCellValue('E1', 'Country');
                $sheet->setCellValue('F1', 'State');
                $sheet->setCellValue('G1', 'Contact Person');
                $sheet->setCellValue('H1', 'Email'); 
                $sheet->setCellValue('I1', 'Phone Number');
                $sheet->setCellValue('J1', 'Amount of Purchase');
                $sheet->setCellValue('K1', 'Average of Purchase');
                $sheet->setCellValue('L1', 'Total Purchase');
                $sheet->setCellValue('M1', 'Last Purchase Date');
                $sheet->setCellValue('N1', 'Created At');
                $rows = 2;
                foreach($data as $row){
                $sheet->setCellValue('A' . $rows, $row['contact_name']);
                $sheet->setCellValue('B' . $rows, $row['contact_no']);
                $sheet->setCellValue('C' . $rows, $row['address']);
                $sheet->setCellValue('D' . $rows, $row['postcode']);
                $sheet->setCellValue('E' . $rows, $row['country']);
                $sheet->setCellValue('F' . $rows, $row['state']);
                $sheet->setCellValue('G' . $rows, $row['contact_person']);
                $sheet->setCellValue('H' . $rows, $row['email']);
                $sheet->setCellValue('I' . $rows, $row['phone_no']);
                $sheet->setCellValue('J' . $rows, $row['amount_purchase']);
                $sheet->setCellValue('K' . $rows, $row['average_purchase']);
                $sheet->setCellValue('L' . $rows, $row['total_purchase']);
                $sheet->setCellValue('M' . $rows, date('d F Y',strtotime($row['last_purchase_date'])));
                $sheet->setCellValue('N' . $rows, date('d F Y',strtotime($row['created_date'])));
                
                $rows++;
                }


                }

                foreach($contact_types_b2b as $contact){

                $data = B2BContactTypes::find($contact->id)
                            ->contacts()
                            ->get();

                $sheet = $spreadsheet->createSheet();
                $sheet->setTitle($contact->contact_type_name);
                $sheet->setCellValue('A1', 'Pharmacy Name');
                $sheet->setCellValue('B1', 'Pharmacy Number');
                $sheet->setCellValue('C1', 'Address');
                $sheet->setCellValue('D1', 'Postcode');
                $sheet->setCellValue('E1', 'Country');
                $sheet->setCellValue('F1', 'State');
                $sheet->setCellValue('G1', 'Contact Person');
                $sheet->setCellValue('H1', 'Email'); 
                $sheet->setCellValue('I1', 'Phone Number');
                $sheet->setCellValue('J1', 'Amount of Purchase');
                $sheet->setCellValue('K1', 'Average of Purchase');
                $sheet->setCellValue('L1', 'Total Purchase');
                $sheet->setCellValue('M1', 'Last Purchase Date');
                $sheet->setCellValue('N1', 'Created At');
                $rows = 2;
                foreach($data as $row){
                $sheet->setCellValue('A' . $rows, $row['contact_name']);
                $sheet->setCellValue('B' . $rows, $row['contact_no']);
                $sheet->setCellValue('C' . $rows, $row['address']);
                $sheet->setCellValue('D' . $rows, $row['postcode']);
                $sheet->setCellValue('E' . $rows, $row['country']);
                $sheet->setCellValue('F' . $rows, $row['state']);
                $sheet->setCellValue('G' . $rows, $row['contact_person']);
                $sheet->setCellValue('H' . $rows, $row['email']);
                $sheet->setCellValue('I' . $rows, $row['phone_no']);
                $sheet->setCellValue('J' . $rows, $row['amount_purchase']);
                $sheet->setCellValue('K' . $rows, $row['average_purchase']);
                $sheet->setCellValue('L' . $rows, $row['total_purchase']);
                $sheet->setCellValue('M' . $rows, date('d F Y',strtotime($row['last_purchase_date'])));
                $sheet->setCellValue('N' . $rows, date('d F Y',strtotime($row['created_date'])));
                
                $rows++;
                }


                }

           

        
            $filename = date('YmdHis') . "-datahub.xlsx";
            $path = sys_get_temp_dir() . "/" . $filename;
           // $path = public_path($filename);
            $writer = new Xlsx($spreadsheet); 
            $writer->save($path);

            //$filed = Storage::disk('local')->get($path);

            $filed = new UploadedFile(
                        $path, // Path to the file
                        $filename, // Original file name
                        null, // MIME type (optional, null will auto-detect)
                        null, // File size (optional, null will auto-detect)
                        true // Test mode (true for temporary files)
                    );

            $request_body = new Request();
            $request_body->files->set('file',$filed);
            $response = (new B2BContactAdjustmentController())->handleFileUpload($request_body);
            $links = ($response->getData())->file_url;

           return $this->successResponse([
                "filename"=>$links
            ],'successfully exported file',200);

           
     }

     /**
      * xlsx import
      */

     public function importData(Request $request)
     {
        $response = (new B2BContactAdjustmentController())->handleFileUpload($request);
        $file_path = ($response->getData())->minio_path;
        $file_source = Storage::disk('minio')->get($file_path);

        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        file_put_contents($tempFile, $file_source);
         // Detect file type from URL
            $extension = strtolower(pathinfo(parse_url($file_path, PHP_URL_PATH), PATHINFO_EXTENSION));

         // Use correct reader
            $reader = match ($extension) {
                'xlsx' => new XlsxReader(),
                'csv'  => new Csv(),
                default => throw new \Exception("Unsupported file type: $extension")
            };
        $spreadsheet = $reader->load($tempFile);
        $worksheet = $spreadsheet->getActiveSheet()->toArray();
        $results = [];
        $first_row = $worksheet[0];  
        $count = 1;  
        
        while($count < count($worksheet)){
            $row = $worksheet[$count];
            $data = [];
            for($i=0; $i < count($first_row); $i++){
                if(isset($row[$i])){
                    $data[$first_row[$i]] = $row[$i];
                } else {
                    $data[$first_row[$i]] = null;
                }
            }
            $results[] = $data;
            $count++;
        }

        $parent_names = [];
        foreach($results as $key=>$result){
            array_push($parent_names,$result['associated_pharmacy']);
        }

        $contact_type = $request->contact_type;
        $contact_type_id = 0;
        switch($contact_type){
            case 'pharmacy-database':
                $contact_type_id = $this->contact_pharmacy_db->id;
                break; 
            case 'community':
                $contact_type_id = $this->contact_community->id;
                break;
        }

        if($contact_type == 'pharmacy-database'){
            cache()->remember('imported',now()->addMinutes(1),function() use ($parent_names){
                    return B2BContacts::select('id','contact_name')->whereIn('contact_name',$parent_names)->get();
            });
            $cache = cache('imported'); 
        }  

        $inserted = [];
        foreach($results as $key=>$result){
            $data = [];
            for($i=0; $i < count($result); $i++){
               if($first_row[$i] == 'associated_pharmacy' && $contact_type == 'pharmacy-database'){   
                $parent_ids = $cache->where('contact_name',$result[$first_row[$i]])->pluck('id')->all();
                $data['contact_parent_id'] = $parent_ids[0];
               } 
               else if($first_row[$i] == 'associated_pharmacy' && $contact_type != 'pharmacy-database'){
                $data['contact_parent_id'] = 0;
               } 
               else {
               $data[$first_row[$i]] = $result[$first_row[$i]];
               }
               $data['contact_type_id'] = $contact_type_id;
               $data['cansativa_newsletter'] = $result['cansativa_newsletter'] == 'yes' ? true : false;
               $data['whatsapp_subscription'] = $result['whatsapp_subscription'] == 'yes' ? true : false;
               $data['email_subscription'] = $result['email_subscription'] == 'yes' ? true : false;
               $data['community_user'] = $result['community_user'] == 'yes' ? true : false;
            }
            array_push($inserted,$data);
        } 

        //Contacts::insert($inserted);
       
        return $this->successResponse($inserted,'successfully read uploaded contact data',200);
     } 

     /**
      * Get all contact types data
      */

     public function contactTypesData(Request $request)
     {
         $results = ContactTypes::all();
         return $this->successResponse($results,'successfully retrieved all contact types data',200);
     } 

     /**
      * Get all history exports data
      */

     public function historyExports(Request $request)
     {
        $results = HistoryExports::all();
         return $this->successResponse($results,'successfully retrieved all history exports data',200);
     }

     /**
      * Add history export data
      */
     public function historyExportsAdd(Request $request)
     {
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

        $result = HistoryExports::insert($data);

        if (!$result) {
            return $this->errorResponse('Error', 400, 'Failed to create new export');
        }
        
        return $this->successResponse($result, 'New export created', 200);
     }

     /**
      * Get all history exports data
      */

     public function historyExportsById(Request $request,$id)
     {
        $results = HistoryExports::where('id',$id)->get();
         return $this->successResponse($results,'successfully retrieved all history exports data',200);
     }
    
    /**
     * Get saved filters data
     */

    public function savedFilters(Request $request)
    {
        $results = SavedFilters::when($request->get('filter_name'),function($query,$row){
            $query->orWhere('filter_name','ilike','%'.$row.'%');
        })->when($request->get('filter_id'),function($query,$row){
            $query->orWhere('id',$row);
        })->get();

        $final_results = [];

        foreach($results as $key => $row){
            $final_results[] = [
                "id"=>$row['id'],
                "filter_name"=>$row['filter_name'],
                "applied_filters"=>json_decode($row['applied_filters']),
                "contact_type_name"=>ContactTypes::where('id',$row['contact_type_id'])->pluck('contact_type_name')->first(),
            ];
        }

        return $this->successResponse($final_results,'successfully retrieved all saved filters data',200);
    }

    /**
     * Add saved filters data
     */

    public function savedFiltersAdd(Request $request)
    {
        
        $request_data = json_decode($request->getContent(),true);
        SavedFilters::insert($request_data);

        return $this->successResponse([],'successfully add saved filters data',200);

    }

    /**
     * Delete saved filter data by id
     */
    public function deleteSavedFilterById(Request $request,$id)
    {
        $saved_filter = SavedFilters::find($id)->where('is_deleted','false');
        if(is_null($saved_filter)){
            return $this->errorResponse('Error',404,'saved filter data is not found');
        }

         // Soft delete the contact
            $saved_filter->is_deleted = true;
            $saved_filter->save();

             SharedContactLogs::insert([
            "type"=>"text",
            "contact_flag" => "b2c",
            "contact_id" => $id,
            "creator_email" => $request->get('creator_email','admin@example.com'),
            "creator_name" => $request->get('creator_name','admin'),
            "description" => json_encode([
                "title"=>"",
                "from"=>"",
                "template"=>"",
                "filename"=> "",
                "campaign_image"=>""
            ])
            ]);

            return $this->successResponse(null,'Community data deleted successfully',200);


    }

    /**
     * Update saved filters by ID
     */

    public function updateSavedFiltersById(Request $request)
    {
        $saved_filter = SavedFilters::find($id)->where('is_deleted','false');
        if(is_null($saved_filter)){
            return $this->errorResponse('Error',404,'saved filter data is not found');
        }

         $request_data = json_decode($request->getContent(), true);

        // Update saved filter
       SavedFilters::where('id', $id)->update($request_data);

        return $this->successResponse(null,'Saved Filter data updated successfully',200);
    
        
    }


    /**
     * Get community data with scroll
     */

    public function communityDataScroll(Request $request)
    {
        $total = Contacts::where('contact_type_id',$this->contact_community->id)
        ->where('is_deleted',false)
        ->count();

        $page = $request->get('page', 1);  

        $community = Contacts::where('contact_type_id',$this->contact_community->id)
        ->where('is_deleted',false);

        $list_community_id = $community->orderBy('id','asc')->pluck('id')->all(); 

        $results = null;

        if($page == 1 || $page < 1){
            $results = $community
            ->where('id',$list_community_id[0])->get();
        } else if($page > $total) {

        }
        else { 
            $results = $community
            ->where('id',$list_community_id[$page - 1])
            ->get(); 
        }

        return $this->successResponse([
            'total' => $total,
            'page' => (int) $page,
            'detail' => $results
        ],'successfully retrieved community data with scroll',200);
    }

    /**
     * Get pharmacy database data with scroll
     */

    public function pharmacyDatabaseScroll(Request $request)
    {
        $total = Contacts::where('contact_type_id',$this->contact_pharmacy_db->id)
        ->where('is_deleted',false)
        ->count();  
        $page = $request->get('page', 1);
        $pharmacy = Contacts::where('contact_type_id',$this->contact_pharmacy_db->id)
        ->where('is_deleted',false);
        $list_pharmacy_id = $pharmacy->orderBy('id','asc')->pluck('id')->all();
        $results = null;
        if($page == 1 || $page < 1){
            $results = $pharmacy
            ->where('id',$list_pharmacy_id[0])->get();
        } else if($page > $total) {

        }
        else { 
            $results = $pharmacy
            ->where('id',$list_pharmacy_id[$page - 1])
            ->get(); 
        }

        return $this->successResponse([
            'total' => $total,
            'page' => (int) $page,
            'detail' => $results
        ],'successfully retrieved pharmacy database data with scroll',200);
    }


    /**
     * Get community data user stats
     */

    public function communityDataUserStats(Request $request)
    {
        $community = Contacts::where('contact_type_id',$this->contact_community->id)
        ->where('is_deleted',false);

        $total = $community->count();

        $new_this_month = $community->whereMonth('created_date', date('m'))
        ->whereYear('created_date', date('Y'))->count();

        return $this->successResponse([
            'total_user' => $total,
            'new_user_this_month' => $new_this_month
        ],'successfully retrieved community data user stats',200);
    }

    /**
     * Get pharmacy database user stats
     */

    public function pharmacyDatabaseUserStats(Request $request)
    {
        $pharmacy = Contacts::where('contact_type_id',$this->contact_pharmacy_db->id)
        ->where('is_deleted',false);

        $total = $pharmacy->count();

        $new_this_month = $pharmacy->whereMonth('created_date', date('m'))
        ->whereYear('created_date', date('Y'))->count();

        return $this->successResponse([
            'total_user' => $total,
            'new_user_this_month' => $new_this_month
        ],'successfully retrieved pharmacy database user stats',200);
    }

    /**
     * Get all pharmacy database
     */
    public function pharmacyDatabaseAll(Request $request)
    {
         $results = ContactTypes::find($this->contact_pharmacy_db->id)->contacts() 
        ->where('contacts.is_deleted', 'false')
        ->get();

        if($results->isEmpty()){
            return $this->errorResponse('Error',404, 'Pharmacy database not found');
        }

        //weird multi sort
        //$multi_sort = false;
        $sorts = [];
        if($request->get('sort') == ''){
           $sorts = [['contacts.id','desc']];
        } else {
            $cleaned_sorts = preg_replace(['/\[/','/\]/','/"/'],'', $request->get('sort'));
            $sorts = array_map(function($sort){
                $sorted = explode('_',$sort);
                $sort_dir = array_slice($sorted,-1,1);
                $sort_column = implode('_',array_slice($sorted,0,-1));
                return ['contacts.' . $sort_column,$sort_dir[0]];
            },explode(',',$cleaned_sorts));  
        }

        //default pagination setup
       // $sort_column = explode('-',$request->get('sort'))[0] ?? 'contacts.id';
       // $sort_direction = explode('-',$request->get('sort'))[1] ?? 'asc';
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $search = $request->get('search');

        //basic response metrics
        $records_total = $results->count();
        $records_filtered = $records_total;

        if($search){
            $search = trim($search);
            $results = ContactTypes::find($this->contact_pharmacy_db->id)->contacts() 
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'like', '%'.$search.'%')
                      ->orWhere('contacts.email', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_name', 'like', '%'. strtoupper($search) .'%');
            })
            ->when($request->get('applied_filters'),function(Builder $query,$row){
                 foreach ($row as $key => $filter) {
                 $filtered = is_array($filter) ? $filter : json_decode($filter, true);
                 $query = FilterHelper::getFilterQuery($query, $filtered); 
                }
            })
            ->where('contacts.is_deleted', 'false');
            //->orderBy($sort_column, $sort_direction);
            $records_filtered = $results
            ->count();

            foreach($sorts as $sort){
               $results = $results->orderBy($sort[0],$sort[1]);
            }

            $results = $results 
            ->take($length)
            ->skip($start)
            ->get();
        } else {
            $results = ContactTypes::find($this->contact_pharmacy_db->id)->contacts() 
            ->when($request->get('applied_filters'),function(Builder $query,$row){
                 foreach ($row as $key => $filter) {
                 $filtered = is_array($filter) ? $filter : json_decode($filter, true);
                 $query = FilterHelper::getFilterQuery($query, $filtered); 
                }
            })
            ->where('contacts.is_deleted', 'false');
           // ->orderBy($sort_column, $sort_direction);
            $records_filtered = $results
            ->count();

            foreach($sorts as $sort){
               $results = $results->orderBy($sort[0],$sort[1]);
            }

            $results = $results 
            ->take($length)
            ->skip($start)
            ->get();
        }

         $formatted_results = [];

        foreach($results as $item){
            $check = isset($item->custom_fields);
            if($check){
                $fields = json_decode($item->custom_fields, true);
                foreach($fields as $key => $value){
                    $item->$key = $value;
                }
                $formatted_results[] = $item->except('custom_fields');
            } else {
                $formatted_results[] = $item;
            }

            if(!is_null($item->account_key_manager_id)){
                $key_mgr_data = AccountKeyManagers::where('id',$item->account_key_manager_id)->get();
                 foreach($key_mgr_data as $items){
                     $item->{'key_manager_name'} = $items->manager_name;
                     $item->{'key_manager_email'} = $items->email;
                     $item->{'key_manager_phone'} = $items->phone;
                     $item->{'key_manager_auto_reply'} = $items->auto_reply;
                     $item->{'key_manager_message_template'} = $items->message_template_name;
                } 
                $formatted_results[] = $item->except('account_key_manager_id');
            }
            
        }

        $res = [
            'recordsTotal' => $records_total,
            'recordsFiltered' => $records_filtered,
            'data' => $formatted_results
        ];

       return $this->successResponse($res,'Pharmacy database all',200);
    }


    /**
     * Save imported contact data
     */

    public function importSave(Request $request)
    {
        $contact_type = $request->contact_type;
        $contact_type_id = 0;
        $default_columns = [];
        switch($contact_type){
            case 'pharmacy-database':
                $default_columns = [
                    'contact_name', 'contact_no', 'address', 'post_code', 'city','country', 
                    'state', 'contact_person', 'email', 'phone_no', 
                    'amount_purchase', 'average_purchase', 'total_purchase', 
                    'last_purchase_date', 'whatsapp_subscription', 'cansativa_newsletter', 'email_subscription'
                ];
                $contact_type_id = $this->contact_pharmacy_db->id;
                break;
            case 'community':
                $default_columns = [
                    'contact_name', 'contact_no', 'address', 'post_code', 'city','country', 
                    'state', 'contact_person', 'email', 'phone_no', 
                    'amount_purchase', 'average_purchase', 'total_purchase', 
                    'last_purchase_date', 'whatsapp_subscription', 'cansativa_newsletter', 'email_subscription'
                ];
                $contact_type_id = $this->contact_community->id;
                break;
        }

        $request_data = json_decode($request->getContent(), true);
        $imported_data = []; 
        $imported_custom_data = [];
        $imported_custom_data_value = [];

        /**
         * Example of request_data structure:
         *  [
         *      "id": [1,2,3],
         *     "contact_name": ["John Doe", "Jane Smith", "Alice Johnson"],
         *  ]
         * 
         */

        foreach($request_data as $key => $array_data){
             foreach($array_data as $data){
             array_push($imported_data,[]);
             }
             break;
        }

        $contact_field_columns = [];

        for($i = 0; $i < count($imported_data); $i++){
            $field_column = [];
            foreach($request_data as $key => $array_data){
                if(in_array($key, $default_columns)){
                  $imported_data[$i][$key] = $array_data[$i];
                } else if($key == 'full_name'){
                    $imported_data[$i]['contact_name'] = $array_data[$i];
                } else {
                    //import custom contact field here
                    $field_column[$key] = $array_data[$i];
                    }  
            }
            $contact_field_columns[] = $field_column;
            $imported_data[$i]['contact_type_id'] = $contact_type_id;
            $imported_data[$i]['created_by'] = $request->user_id ?? 12;
            $imported_data[$i]['user_id'] = $request->user_id ?? 12;
            $imported_data[$i]['created_date'] = date('Y-m-d H:i:s');
        }

        foreach($contact_field_columns as $column) {
            $keys = array_keys($column);
            $values = array_values($column);
            $fielder = [];
           for($i = 0; $i < count($keys); $i++){
              $fielder[] = ContactFieldHelper::pushContactField($keys[$i],$values[$i]);
           }
           $imported_custom_data[] = $fielder;
        }


      Contacts::insert($imported_data);
       
       foreach($imported_custom_data as $items){
           foreach($items as $item){
              $check = ContactField::where('field_name',$item['field_name'])->count();
             if($check == 0){
                 ContactField::insert([
                    "field_name"=> $item['field_name'],
                    "field_type"=> $item['field_type'],
                    "description"=> $item['description']
                ]); 
             } 
           }
       }
      

       $contact_field_value = [];

       $inserted_id = Contacts::orderBy('id','desc')->take(count($imported_data))->pluck('id');
       $recorded = [];
       $contact_field_value = [];
       $counter = 0;
        foreach($inserted_id as $id){
                $recorded[] = [
            "type"=>"import",
            "contact_flag" => "b2c",
            "contact_id" => $id,
            "creator_email" => $request->get('creator_email','admin@example.com'),
            "creator_name" => $request->get('creator_name','admin'),
            "description" => json_encode([
                "title"=>"",
                "from"=>"",
                "template"=>"",
                "filename"=> $request->get('imported_filename'),
                "campaign_image"=>""
            ])
            ];
            //insert custom contact field here
           // var_dump(json_encode($imported_custom_data[$counter]));
            $contact_field_value[] = ContactFieldHelper::pushFieldValue($imported_custom_data[$counter],$id);
            $counter++;
        }

       SharedContactLogs::insert($recorded);
       foreach($contact_field_value as $value){
                ContactFieldValue::insert($value);  
       }

        

        return $this->successResponse(null,'successfully saved imported contact data',200);

    }

    /**
     * Update contact subscription from login
     */
    public function updateContactSubscriptionFromLogin(Request $request)
    {
        $data = json_decode($request->getContent(),true);

        if($request->get("email") != "" && $request->get("phone") != ""){
            $check = Contacts::where('phone_no',$request->get("phone"))
            ->where('email',$request->get("email"))
            ->count();
            if($check == 0) {
                return $this->errorResponse('contact not found',404);
            }  
            Contacts::where('email',$request->get("email"))
            ->where('phone_no',$request->get("phone"))
            ->update(
                [
                    "temporary_email_subscription"=>$data["temp_email_subs"],
                    "temporary_whatsapp_subscription"=>$data["temp_wa_subs"]
                ]);
        } 
        else if($request->get("email") != ""){
            $check = Contacts::where('email',$request->get("email")) 
            ->count();
            if($check == 0) {
                return $this->errorResponse('contact not found',404);
            }
            Contacts::where('email',$request->get("email"))->update(
                [
                    "temporary_email_subscription"=>$data["temp_email_subs"],
                    "temporary_whatsapp_subscription"=>$data["temp_wa_subs"]
                ]);
        } else if($request->get("phone") != ""){
            $check = Contacts::where('phone_no',$request->get("phone"))
            ->count();
            if($check == 0) {
                return $this->errorResponse('contact not found',404);
            }
            Contacts::where('phone_no',$request->get("phone"))->update(
                [
                    "temporary_email_subscription"=>$data["temp_email_subs"],
                    "temporary_whatsapp_subscription"=>$data["temp_wa_subs"]
                ]);
        }
        
        return $this->successResponse(null,'successfully saved imported contact data',200);
    }

    /**
     * Get contact metrics
     */
    public function getMetricsData()
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
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('newcontactdata::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('newcontactdata::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('newcontactdata::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('newcontactdata::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}