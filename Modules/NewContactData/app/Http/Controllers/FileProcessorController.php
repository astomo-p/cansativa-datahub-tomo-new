<?php

namespace Modules\NewContactData\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileProcessorController extends Controller
{
     /**
     * List of traits used by the controller.
     *
     * @return void
     */
    use \App\Traits\ApiResponder;
    /**
     * Upload a document.
     */
    public function documentUpload(Request $request)
    {
        // Validate the request
        $request->validate([
            'upload.*' => 'required|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
        ]);
        // Define the path where you want to store the file
        $path = '/b2c/contact/community';
        try {

            $files = $request->file('upload');
            $links = [];
            foreach ($files as $file) {

                //$links[] = $file->getClientOriginalName();
                  $links[] = $file->storePubliclyAs($path,date('YmdHis') . '-doc.' . $file->getClientOriginalExtension(),'minio');

            }

            $link = [];

            foreach ($links as $link_file) {
                $link[] = env('MINIO_URL') . '/' . $link_file;
            }

            // Return the path of the uploaded file
        return $this->successResponse(['link' => $link], 'File uploaded successfully', 200);
       

        }
          catch (\Exception $e) {
            return $this->errorResponse('File upload failed: ' . $e->getMessage(), 500);
          }
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
