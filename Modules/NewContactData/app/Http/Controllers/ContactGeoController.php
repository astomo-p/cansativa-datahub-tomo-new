<?php

namespace Modules\NewContactData\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\NewContactData\Models\Contacts;

class ContactGeoController extends Controller
{
     /**
     * List of traits used by the controller.
     *
     * @return void
     */
    use \App\Traits\ApiResponder;


    /**
     * Get countries
     */
    public function getCountries(Request $request)
    {
        $countries = Contacts::select('country')->whereNotNull('country')->groupBy('country')->get();
        $records_total = count($countries);
        $start = $request->get('start',0);
        $length = $request->get('length',10);
        $raw = Contacts::select('country')->whereNotNull('country')->groupBy('country')
                ->skip($start)
                ->take($length)
                /* ->when($request->get('search'),function($query,$row){
                $query->where('city','like',"'%".$row."%'"); 
            }) */;
        $data = $raw->pluck('country');  
        $records_filtered = count($data);  
        return $this->successResponse([
            'recordsTotal'=>$records_total,
            'recordsFiltered'=>$records_filtered,
            'data'=>$data
        ],'successfully get countries',200);
    }

    /**
     * Get cities by country
     */
    public function getCitiesByCountry(Request $request,$country)
    {
        $cities = Contacts::select('city')->whereNotNull('city')->where('country',$country)->groupBy('city')->get();
        $records_total = count($cities);
        $start = $request->get('start',0);
        $length = $request->get('length',10);
        $raw = Contacts::select('city')->whereNotNull('city')->where('country',$country)->groupBy('city')
                ->skip($start)
                ->take($length)
                /* ->when($request->get('search'),function($query,$row){
                $query->where('city','like',"'%".$row."%'"); 
            }) */;
        $data = $raw->pluck('city');  
        $records_filtered = count($data);  
        return $this->successResponse([
            'recordsTotal'=>$records_total,
            'recordsFiltered'=>$records_filtered,
            'data'=>$data
        ],'successfully get cities',200);
    }

     /**
     * Get postcodes by city and country
     */
    public function getPostcodesByCityAndCountry(Request $request,$country,$city)
    {
        $postcodes = Contacts::select('post_code')->whereNotNull('post_code')->where('city',$city)->where('country',$country)->groupBy('post_code')->get();
        $records_total = count($postcodes);
        $start = $request->get('start',0);
        $length = $request->get('length',10);
        $raw = Contacts::select('post_code')->whereNotNull('post_code')->where('city',$city)->where('country',$country)->groupBy('post_code')
                ->skip($start)
                ->take($length)
                /* ->when($request->get('search'),function($query,$row){
                $query->where('city','like',"'%".$row."%'"); 
            }) */;
        $data = $raw->pluck('post_code');  
        $records_filtered = count($data);  
        return $this->successResponse([
            'recordsTotal'=>$records_total,
            'recordsFiltered'=>$records_filtered,
            'data'=>$data
        ],'successfully get post codes',200);
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
