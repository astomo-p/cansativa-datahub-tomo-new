<?php

namespace Modules\NewContactData\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\NewContactData\Models\WaTemplateAttributes;

class WaTemplateAttributesController extends Controller
{
    /**
     * List of traits used by the controller.
     *
     * @return void
     */
    use \App\Traits\ApiResponder;

    /**
     * Add wa template attributes
     */
    public function addWaTemplateAttributes(Request $request)
    {
        $request_data = json_decode($request->getContent(), true);

            // Create the contact
           // Contacts::create($request_data);
           WaTemplateAttributes::insert($request_data);

            return $this->successResponse(null,'data added successfully',200);
       
    }

    /**
     * get all wa template attributes
     */
    public function allWaTemplateAttributes(Request $request)
    {
        $result = WaTemplateAttributes::where('is_deleted',false)->get();
         return $this->successResponse($result,'get all data successfully',200);
       
    }

    /**
     * delete wa template attributes by id
     */
    public function deleteWaTemplateAttributes(Request $request,$id)
    {
        $result = WaTemplateAttributes::where('is_deleted',false)->where('id',$id)->count();
        if($result == 0){
            return $this->errorResponse('Error',404, 'data not found');
        }
        WaTemplateAttributes::where('id',$id)->update(["is_deleted"=>true]);
         return $this->successResponse(null,'delete data successfully',200);

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
