<?php

namespace Modules\CustomAnalytics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomAnalyticsController extends Controller
{
     /**
     * List of traits used in this controller.
     */
    use \App\Traits\ApiResponder;

    public function allData(Request $request)
    {
        return $this->successResponse([],"success all data",201);
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('customanalytics::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('customanalytics::create');
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
        return view('customanalytics::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('customanalytics::edit');
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
