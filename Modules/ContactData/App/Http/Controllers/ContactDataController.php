<?php

namespace Modules\ContactData\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\ContactData\App\Models\Contacts;
use Modules\ContactData\App\Models\ContactTypes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Client;
use Automattic\WooCommerce\Client as WooClient;


class ContactDataController extends Controller
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

    /**
     * constructor
     */
    public function __construct()
    {
        $this->contact_pharmacy = ContactTypes::where('contact_type_name', 'PHARMACY')->first();
        $this->contact_supplier = ContactTypes::where('contact_type_name', 'SUPPLIER')->first();
        $this->contact_community = ContactTypes::where('contact_type_name', 'COMMUNITY')->first();
        $this->contact_general_newsletter = ContactTypes::where('contact_type_name', 'GENERAL NEWSLETTER')->first();
        $this->contact_pharmacy_db = ContactTypes::where('contact_type_name', 'PHARMACY DATABASE')->first();
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
        $pharmacy = [];
        for($i = 1; $i <= 12; $i++){
            $pharmacy[$i] = ContactTypes::find($this->contact_pharmacy->id)->contacts()
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
            $supplier[$i] = ContactTypes::find($this->contact_supplier->id)->contacts()
            ->whereMonth('created_date', $i)
            ->whereYear('created_date', $now)
            ->count();
        }
        $supplier_result = [];
        foreach($supplier as $key => $value){
            $supplier_result[$months[$key]] = (int) $value;
        }

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
        $general_newsletter = [];
        for($i = 1; $i <= 12; $i++){
            $general_newsletter[$i] = ContactTypes::find($this->contact_general_newsletter->id)->contacts()
            ->whereMonth('created_date', $i)
            ->whereYear('created_date', $now)
            ->count();
        }
        $general_newsletter_result = [];
        foreach($general_newsletter as $key => $value){
            $general_newsletter_result[$months[$key]] = (int) $value;
        }

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

        $res = [
          'Pharmacies' => $pharmacy_result,
          'Suppliers' => $supplier_result,
          'General Newsletter' => $general_newsletter_result,
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
        if($request->type == 'pharmacies'){
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
        else if($request->type == 'subscribers'){
            array_push($res, [
                'total' => 20000,
                'delta' => '+20',
            ]);
        }
        else if($request->type == 'pharmacy-contacts'){
            array_push($res, [
                'total' => 150,
                'delta' => '-50',
            ]);
        }
        else {
            return $this->error('Invalid type',400);
        }
            
       return $this->successResponse($res,'Top contact card',200);
    }

    /**
     * Get all pharmacy data.
     *
     * @return Response
     */
    public function allPharmacyData(Request $request)
    {
        // default pagination setup
        $sort_column = explode('-',$request->get('sort', 'asc'))[0] ?? 'contacts.id';
        $sort_direction = explode('-',$request->get('sort', 'asc'))[1] ?? 'asc';
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $search = $request->get('search');

         //basic response metrics
        $records_total = ContactTypes::find($this->contact_pharmacy->id)->contacts()
        ->where('contacts.is_deleted', 'false')
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
            ->orderBy('contacts.'.$sort_column, $sort_direction)
            ->take($length)
            ->skip($start)
            ->get();
            $records_filtered = ContactTypes::find($this->contact_pharmacy->id)->contacts()
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'like', '%'.$search.'%')
                      ->orWhere('contacts.email', 'like', '%'.$search.'%');
            })
            ->where('contacts.is_deleted', 'false')
            ->count();
        } else {
            $results = ContactTypes::find($this->contact_pharmacy->id)->contacts()
            ->where('contacts.is_deleted', 'false')
            ->orderBy('contacts.'.$sort_column, $sort_direction)
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
       Contacts::insert($request_data);

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
        $sort_column = explode('-',$request->get('sort', 'asc'))[0] ?? 'contacts.id';
        $sort_direction = explode('-',$request->get('sort', 'asc'))[1] ?? 'asc';
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
            ->orderBy('contacts.'.$sort_column, $sort_direction)
            ->take($length)
            ->skip($start)
            ->get();

            $records_filtered = ContactTypes::find($this->contact_supplier->id)->contacts()
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'like', '%'.$search.'%')
                      ->orWhere('contacts.email', 'like', '%'.$search.'%');
            })
            ->count();
        } else {
            $results = ContactTypes::find($this->contact_supplier->id)->contacts()
            ->where('contacts.is_deleted', 'false')
            ->orderBy('contacts.'.$sort_column, $sort_direction)
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
       Contacts::insert($request_data);

        return $this->successResponse(null,'Supplier data added successfully',200);
     }

     /**
      * Update supplier data by ID
      */

        public function updateSupplierDataById(Request $request, $id)
        {
            $result = Contacts::find($id);
            if(!$result){
                return $this->errorResponse('Error',404, 'Supplier not found');
            }
            $request_data = json_decode($request->getContent(), true);

            // Update the contact
           Contacts::where('id', $id)->update($request_data);

            return $this->successResponse(null,'Supplier data updated successfully',200);
        }
      
     /**
      * Get supplier data by ID
       */   
    public function supplierDataById($id)
    {
        $result = Contacts::find($id);
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
        $result = Contacts::find($id);
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
        $sort_column = explode('-',$request->get('sort', 'asc'))[0] ?? 'contacts.id';
        $sort_direction = explode('-',$request->get('sort', 'asc'))[1] ?? 'asc';
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $search = $request->get('search');

        //basic response metrics
        $records_total = ContactTypes::find($this->contact_community->id)->contacts()
        ->where('contacts.is_deleted', 'false')
        ->count();
        $records_filtered = $records_total;

        if($search){
            $search = trim($search);
            $results = ContactTypes::find($this->contact_community->id)->contacts()
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'like', '%'.$search.'%')
                      ->orWhere('contacts.email', 'like', '%'.$search.'%');
            })
            ->where('contacts.is_deleted', 'false')
            ->orderBy('contacts.'.$sort_column, $sort_direction)
            ->take($length)
            ->skip($start)
            ->get();
            $records_filtered = ContactTypes::find($this->contact_community->id)->contacts()
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'like', '%'.$search.'%')
                      ->orWhere('contacts.email', 'like', '%'.$search.'%');
            })
            ->count();
        } else {
            $results = ContactTypes::find($this->contact_community->id)->contacts()
            ->where('contacts.is_deleted', 'false')
            ->orderBy('contacts.'.$sort_column, $sort_direction)
            ->take($length)
            ->skip($start)
            ->get();
        }

        
        $res = [
            'recordsTotal' => $records_total,
            'recordsFiltered' => $records_filtered,
            'data' => $results
        ];
       
       return $this->successResponse($res,'All community data',200);
    }

    /**
     * add community data
     */

     public function addCommunityData(Request $request)
     {
        $request_data = json_decode($request->getContent(), true);

        // Create the contact
       // Contacts::create($request_data);
       Contacts::insert($request_data);

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

            // Update the contact
           Contacts::where('id', $id)->update($request_data);

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
       return $this->successResponse($result,'Community data by ID',200);
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
        $sort_column = explode('-',$request->get('sort', 'asc'))[0] ?? 'contacts.id';
        $sort_direction = explode('-',$request->get('sort', 'asc'))[1] ?? 'asc';
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $search = $request->get('search');

        //basic response metrics
        $records_total = ContactTypes::find($this->contact_general_newsletter->id)->contacts()
        ->where('contacts.is_deleted', 'false')
        ->count();
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
            ->orderBy('contacts.'.$sort_column, $sort_direction)
            ->take($length)
            ->skip($start)
            ->get();
            $records_filtered = ContactTypes::find($this->contact_general_newsletter->id)->contacts()
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'like', '%'.$search.'%')
                      ->orWhere('contacts.email', 'like', '%'.$search.'%');
            })
            ->where('contacts.is_deleted', 'false')
            ->count();
        } else {
            $results = ContactTypes::find($this->contact_general_newsletter->id)->contacts()
            ->where('contacts.is_deleted', 'false')
            ->orderBy('contacts.'.$sort_column, $sort_direction)
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
       Contacts::insert($request_data);

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
        $sort_column = explode('-',$request->get('sort', 'asc'))[0] ?? 'contacts.id';
        $sort_direction = explode('-',$request->get('sort', 'asc'))[1] ?? 'asc';
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
            ->orderBy('contacts.'.$sort_column, $sort_direction)
            ->take($length)
            ->skip($start)
            ->get();
            $records_filtered = ContactTypes::find($this->contact_pharmacy_db->id)->contacts()
            ->where('contact_parent_id', $parentId)
            ->where(function($query) use ($search) {
                $query->where('contacts.contact_name', 'like', '%'.$search.'%')
                      ->orWhere('contacts.contact_no', 'like', '%'.$search.'%')
                      ->orWhere('contacts.email', 'like', '%'.$search.'%');
            })
            ->where('contacts.is_deleted', 'false')
            ->count();
        } else {
            $results = ContactTypes::find($this->contact_pharmacy_db->id)->contacts()
            ->where('contact_parent_id', $parentId)
            ->where('contacts.is_deleted', 'false')
            ->orderBy('contacts.'.$sort_column, $sort_direction)
            ->take($length)
            ->skip($start)
            ->get();
            $records_filtered = ContactTypes::find($this->contact_pharmacy_db->id)->contacts()
            ->where('contact_parent_id', $parentId)
            ->where('contacts.is_deleted', 'false')
            ->orderBy('contacts.'.$sort_column, $sort_direction)
            ->take($length)
            ->skip($start)
            ->count();
        }

        $res = [
            'recordsTotal' => $records_total,
            'recordsFiltered' => $records_filtered,
            'data' => $results
        ];

       return $this->successResponse($res,'Pharmacy database by parent ID',200);
    }

    /**
     * Update pharmacy database by ID.
     */

    public function updatePharmacyDatabaseByParentIdAndId(Request $request, $parentId, $id)
    {
        $result = Contacts::find($parentId)->pharmacyChilds()->where('id', $id)->get();
        if(!$result){
            return $this->errorResponse('Error',404, 'Pharmacy database not found');
        }

        DB::beginTransaction();
        $request_data = json_decode($request->getContent(), true);
        try {
            // Update the contact childs
            Contacts::find($parentId)->pharmacyChilds()
            ->where('id', $id)
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
        $parent = Contacts::find($parentId);
        if(!$parent){
            return $this->errorResponse('Error',404, 'Pharmacy database not found');
        }
        $result = $parent->pharmacyChilds()->where('id', $id)->first();

       return $this->successResponse($result,'Pharmacy database data by ID',200);
    }

    /**
     * Delete pharmacy database by id
     */

     public function deletePharmacyDatabaseByParentIdAndId($parentId, $id)
     {
        $parent = Contacts::find($parentId);
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
                'https://bb4mgd1.myrdbx.io',
                'ck_d9c04361efce8629f4a55dfcf475dbcfaa2d4cff',
                'cs_115ff19114a66132e3fdca922f74eb28dcebac74',
                [
                        'wp_api' => true,
                        'version' => 'wc/v3'
                ]
                );        
        $woo_response = $woocommerce->get('customers?page=1&per_page=3');
        $id = Contacts::all()->last()->id;
        foreach($woo_response as $key){
        $contact = new Contacts();
        $contact->id += $id ;
        $contact->contact_name = $key->billing->company;
        $contact->contact_no = "";
        $contact->address = "-";
        $contact->post_code = $key->billing->post_code;
        $contact->city = $key->billing->city;
        $contact->country = $key->billing["country"];
        $contact->contact_person = "-";
        $contact->email = $key->billing["email"];
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
        $contact->updated_date = date("Y-m_d H:i:s");
        $contact->save();
        }

        return $this->successResponse([
            'type' => dump($woo_response),
        ],'Success',200);
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('contactdata::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('contactdata::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('contactdata::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('contactdata::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}

     

