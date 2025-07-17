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
use Modules\Users\Models\Users;
use Modules\NewContactData\Models\SavedFilters;
use Modules\NewContactData\Models\SharedContactLogs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Client;
use Automattic\WooCommerce\Client as WooClient;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use Illuminate\Support\Facades\Http;

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
        $this->contact_pharmacy = B2BContactTypes::where('contact_type_name', 'PHARMACY')->first();
        $this->contact_supplier = B2BContactTypes::where('contact_type_name', 'SUPPLIER')->first();
        $this->contact_community = ContactTypes::where('contact_type_name', 'COMMUNITY')->first();
        $this->contact_general_newsletter = B2BContactTypes::where('contact_type_name', 'GENERAL NEWSLETTER')->first();
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

        //pharmacy
       /*  $pharmacy = [];
        for($i = 1; $i <= 12; $i++){
            $pharmacy[$i] = ContactTypes::find($this->contact_pharmacy->id)->contacts()
            ->whereMonth('created_date', $i)
            ->whereYear('created_date', $now)
            ->count();
        }
        $pharmacy_result = [];
        foreach($pharmacy as $key => $value){
            $pharmacy_result[$months[$key]] = (int) $value;
        } */

        //supplier
        /* $supplier = [];
        for($i = 1; $i <= 12; $i++){
            $supplier[$i] = ContactTypes::find($this->contact_supplier->id)->contacts()
            ->whereMonth('created_date', $i)
            ->whereYear('created_date', $now)
            ->count();
        }
        $supplier_result = [];
        foreach($supplier as $key => $value){
            $supplier_result[$months[$key]] = (int) $value;
        } */

        //community
        $community = [];
        for($i = 1; $i <= 12; $i++){
            $community[$i] = ContactTypes::find($this->contact_community->id)->contacts()
            ->whereMonth('created_date', $i)
            ->whereYear('created_date', $now)
            ->count();
        }
        $community_result = [];
        foreach($community as $key => $value){
            $community_result[$months[$key]] = (int) $value;
        }

        //general newsletter
       /*  $general_newsletter = [];
        for($i = 1; $i <= 12; $i++){
            $general_newsletter[$i] = ContactTypes::find($this->contact_general_newsletter->id)->contacts()
            ->whereMonth('created_date', $i)
            ->whereYear('created_date', $now)
            ->count();
        }
        $general_newsletter_result = [];
        foreach($general_newsletter as $key => $value){
            $general_newsletter_result[$months[$key]] = (int) $value;
        } */

        //pharmacy db
        $pharmacy_db = [];
        for($i = 1; $i <= 12; $i++){
            $pharmacy_db[$i] = ContactTypes::find($this->contact_pharmacy_db->id)->contacts()
            ->whereMonth('created_date', $i)
            ->whereYear('created_date', $now)
            ->count();
        }
        $pharmacy_db_result = [];
        foreach($pharmacy_db as $key => $value){
            $pharmacy_db_result[$months[$key]] = (int) $value;
        }

        //subscriber
        $subscriber = [];
        for($i = 1; $i <= 12; $i++){
            $subscriber[$i] = ContactTypes::find($this->contact_subscriber->id)->contacts()
            ->whereMonth('created_date', $i)
            ->whereYear('created_date', $now)
            ->count();
        }
        $subscriber_result = [];
        foreach($subscriber as $key => $value){
            $subscriber_result[$months[$key]] = (int) $value;
        }

        $res = [
          //'Pharmacies' => $pharmacy_result,
          //'Suppliers' => $supplier_result,
          //'General Newsletter' => $general_newsletter_result,
          'Community' => $community_result,
          'Pharmacy Database' => $pharmacy_db_result,
          'Subscriber' => $subscriber_result
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
         if($request->type == 'subscribers'){

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
        // default pagination setup
        $sort_column = $request->get('sort') == '' ? 'contacts.id' :  'contacts.' . explode('-',$request->get('sort'))[0];
        $sort_direction = $request->get('sort') == '' ? 'asc' :  explode('-',$request->get('sort'))[1];;
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
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'like', '%'.$search.'%')
                      ->orWhere('contacts.email', 'like', '%'.$search.'%');
            })
            ->where('contacts.is_deleted', 'false')
            ->orderBy($sort_column, $sort_direction)->select()
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
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'like', '%'.$search.'%')
                      ->orWhere('contacts.email', 'like', '%'.$search.'%');
            })
            ->where('contacts.is_deleted', 'false') 
            ->orderBy($sort_column, $sort_direction)
            
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
        $request_data = json_decode($request->getContent(), true);

        // Format the request data
        $formatted_request_data = [];

        $blocked = ['likes','comments','submissions'];

        foreach($request_data as $key => $value){
            /* if($key == 'email_subscription'){
                $formatted_request_data['cansativa_newsletter'] = $value;
            } else */ if(in_array($key,$blocked)){} 
            
            else {
                $formatted_request_data[$key] = $value;
            }
        }

        // Create the contact
       // Contacts::create($request_data);
       Contacts::insert($formatted_request_data);

        $inserted_id = Contacts::orderBy('id','desc')->take(count($formatted_request_data))->pluck('id');
       $recorded = [];
        foreach($inserted_id as $id){
                $recorded[] = [
            "type"=>"text",
            "contact_flag" => "b2c",
            "contact_id" => $id,
            "creator_email" => $request->get('creator_email'),
            "creator_name" => $request->get('creator_name'),
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

        return $this->successResponse(null,'Community data added successfully',200);
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

            foreach($request_data as $key => $value){
                /* if($key == 'email_subscription'){
                    $formatted_request_data['cansativa_newsletter'] = $value;
                } else {
                    $formatted_request_data[$key] = $value;
                } */
               $formatted_request_data[$key] = $value;
            }

            // Update the contact
           Contacts::where('id', $id)->update($formatted_request_data);

            return $this->successResponse(null,'Community data updated successfully',200);
        }

      /**
       * get community data by id
       *  */  

      public function communityDataById(Request $request,$id)
      {
         $result = Contacts::find($id);
        if(!$result){
            return $this->errorResponse('Error',404, 'Community not found');
        }

        $formatted_results = []; 

        foreach($result->toArray() as $key => $value){
            if($key == 'custom_fields'){
                $json = json_decode($value, true);
                foreach($json as $field_key => $field_value){
                    $formatted_results[$field_key] = $field_value;
                }
            }
            else {
            $formatted_results[$key] = $value; 
            }
        }          

       return $this->successResponse([$formatted_results],'Community data by ID',200);
      } 

      /**
       * delete community data by id
       */
        public function deleteCommunityDataById($id)
        {
            $result = Contacts::find($id);
            if(!$result){
                return $this->errorResponse('Error',404, 'Community not found');
            }

            // Soft delete the contact
            $result->is_deleted = true;
            $result->save();

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

        // Create the contact
       // Contacts::create($request_data);

        // Format the request data
        $formatted_request_data = [];

        $blocked = ['likes','comments','submissions'];

        foreach($request_data as $key => $value){
           /*  if($key == 'email_subscription'){
                $formatted_request_data['cansativa_newsletter'] = $value;
            } else */ if(in_array($key,$blocked)){} 
            
            else {
                $formatted_request_data[$key] = $value;
            }
        }

       Contacts::insert($formatted_request_data);

       $inserted_id = Contacts::orderBy('id','desc')->take(count($formatted_request_data))->pluck('id');
       $recorded = [];
        foreach($inserted_id as $id){
                $recorded[] = [
            "type"=>"import",
            "contact_flag" => "b2c",
            "contact_id" => $id,
            "creator_email" => $request->get('creator_email'),
            "creator_name" => $request->get('creator_name'),
            "description" => json_encode([
                "title"=>"",
                "from"=>"",
                "template"=>"",
                "filename"=> $request->get('imported_filename'),
                "campaign_image"=>""
            ])
            ];
        }

       SharedContactLogs::insert($recorded);

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

        //default pagination setup
        $sort_column = explode('-',$request->get('sort'))[0] ?? 'contacts.id';
        $sort_direction = explode('-',$request->get('sort'))[1] ?? 'asc';
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
            $results = ContactTypes::find($this->contact_pharmacy_db->id)->contacts()
            ->where('contact_parent_id', $parentId)
            ->where('contacts.is_deleted', 'false')
            ->orderBy($sort_column, $sort_direction);
            $records_filtered = $results
            ->count();
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
        ->get();
        if(!$result){
            return $this->errorResponse('Error',404, 'Pharmacy database not found');
        }

        DB::beginTransaction();
        $request_data = json_decode($request->getContent(), true);
        try {
            // Update the contact childs
            Contacts::where('id', $id)
            ->where('contact_parent_id', $parentId)
            ->update($request_data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error',500, 'Failed to update pharmacy database: ' . $e->getMessage());
        }

        return $this->successResponse(null,'Pharmacy database data updated successfully',200);
    }

    /**
     * Get pharmacy database data by ID.
     */

    public function pharmacyDatabaseByParentIdAndId(Request $request, $parentId, $id)
    {
        $parent = Contacts::where('contact_parent_id', $parentId)->get();
        if(!$parent){
            return $this->errorResponse('Error',404, 'Pharmacy database not found');
        }
        $result = Contacts::where('id', $id)
            ->where('contact_parent_id', $parentId)->first();

        $formatted_results = []; 

        foreach($result->toArray() as $key => $value){
            if($key == 'custom_fields'){
                $json = json_decode($value, true);
                foreach($json as $field_key => $field_value){
                    $formatted_results[$field_key] = $field_value;
                }
            }
            else {
            $formatted_results[$key] = $value; 
            }
        }          
        
    

       return $this->successResponse($formatted_results,'Pharmacy database data by ID',200);
    }

    /**
     * Delete pharmacy database by id
     */

     public function deletePharmacyDatabaseByParentIdAndId($parentId, $id)
     {
        $parent = Contacts::where('contact_parent_id', $parentId)->get();
        if(!$parent){
            return $this->errorResponse('Error',404, 'Pharmacy database not found');
        }
        
        DB::beginTransaction();
        try {
            // Soft delete the contact
            Contacts::find($id)->update(['is_deleted' => true]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error',500, 'Failed to delete pharmacy database: ' . $e->getMessage());
        }

        return $this->successResponse(null,'Pharmacy database data deleted successfully',200);
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
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048', // Adjust the validation rules as needed
        ]);

        // Get the file from the request
        $file = $request->file('file');

        // Define the path where you want to store the file
        $path = 'uploads/contact-data/';

        try {
            // Check if the file already exists
           // Store the file in MinIO
        //$file->storeAs('', $path, 'minio');
        Storage::disk('minio')->put($path, file_get_contents($file));

            // Return the path of the uploaded file
        return $this->successResponse(['path' => $path], 'File uploaded successfully', 200);
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
                'pharmacy-db'=>$this->contact_pharmacy_db->id
            ];

           if(!array_key_exists($contact,$contact_type)){
                return $this->errorResponse('invalid contact_type',400);
           }

           $results = ContactTypes::find($contact_type[$contact])
           ->contacts()
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
         })

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

           while($chunk < $chunk_size){
                $data = $results
                        ->skip($chunk * $limit)
                        ->take($limit)
                        ->get();
                 

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
                $sheet->setCellValue('A1', 'Full Name');
                $sheet->setCellValue('B1', 'Email'); 
                $sheet->setCellValue('C1', 'Phone Number');
                $sheet->setCellValue('D1', 'Whatsapp Subscription');
                $sheet->setCellValue('E1', 'Email Subscription');
                $sheet->setCellValue('F1', 'Likes');
                $sheet->setCellValue('G1', 'Comments');
                $sheet->setCellValue('H1', 'Submissions');
                $sheet->setCellValue('I1', 'Account Creation');
                $sheet->setCellValue('J1', 'Latest Login');
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
                $rows++;
                }

                }

                $chunk++;

           }

        
            $filename = date('YmdHis') . "-" . $contact . ".xlsx";
            $writer = new Xlsx($spreadsheet); 
            $writer->save($filename);

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
                        'to' => $recipient[0]->phone_no,
                        'type' => 'template',
                        'template' => [
                            'name' => 'report_template_cta',
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
                                                'text' => 'public/' . $filename
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
                    'name' => 'Cansativa',
                    'email' => env('BREVO_SENDER_EMAIL','siroja@kemang.sg'),
                ],
                'htmlContent' => "<html><body><h1>Please download your report</h1><p><a href='".url('public/' . $filename)."'>Here</a></p></body></html>",
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

             HistoryExports::insert([
                'contact_name' => $request->contact_name,
                'contact_type' => $request->contact_type,
                'applied_filters' => json_encode($request->applied_filters),
                'export_to'=> $request->get('export_to','.xlsx'),
                'amount_contacts' => $count,
                'created_date' => date('Y-m-d H:i:s')
            ]);  

            

           return $this->successResponse([
                "filename"=>url('public/' . $filename)
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

     /**
      * xlsx import
      */

     public function importData(Request $request)
     {
        $file = $request->file('contact_file');
        $reader = new XlsxReader();
        $spreadsheet = $reader->load($file);
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
                "applied_filters"=>json_decode($row['applied_filters'])
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

        for($i = 0; $i < count($imported_data); $i++){
            foreach($request_data as $key => $array_data){
                if(in_array($key, $default_columns)){
                  $imported_data[$i][$key] = $array_data[$i];
                } else if($key == 'full_name'){
                    $imported_data[$i]['contact_name'] = $array_data[$i];
                }  
            }
            $imported_data[$i]['contact_type_id'] = $contact_type_id;
            $imported_data[$i]['created_by'] = $request->user_id ?? 12;
            $imported_data[$i]['user_id'] = $request->user_id ?? 12;
            $imported_data[$i]['created_date'] = date('Y-m-d H:i:s');
        }

        
        $json_array = [];

        for($i = 0; $i < count($imported_data); $i++){
            foreach($request_data as $key => $array_data){
                if(!in_array($key, $default_columns)){
                  $json_array[$i][$key] = $array_data[$i];
                } 
            }
        }

        for($i = 0; $i < count($imported_data); $i++){
            $imported_data[$i]['custom_fields'] = json_encode($json_array[$i]);
        }

       Contacts::insert($imported_data);

       $inserted_id = Contacts::orderBy('id','desc')->take(count($imported_data))->pluck('id');
       $recorded = [];
        foreach($inserted_id as $id){
                $recorded[] = [
            "type"=>"import",
            "contact_flag" => "b2c",
            "contact_id" => $id,
            "creator_email" => $request->get('creator_email'),
            "creator_name" => $request->get('creator_name'),
            "description" => json_encode([
                "title"=>"",
                "from"=>"",
                "template"=>"",
                "filename"=> $request->get('imported_filename'),
                "campaign_image"=>""
            ])
            ];
        }

       SharedContactLogs::insert($recorded);

        

        return $this->successResponse(null,'successfully saved imported contact data',200);

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
