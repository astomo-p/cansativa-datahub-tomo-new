<?php

namespace Modules\NewContactData\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Client;
use Modules\NewContactData\Models\Files;
use Modules\B2BContactAdjustment\Http\Controllers\B2BContactAdjustmentController;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use Modules\NewContactData\Helpers\ContactTypeHelper;


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
        $path = '';
        try {

            $files = $request->file('upload');
            $links = [];
            foreach ($files as $file) {
                $request_body = new Request();
                $request_body->files->set('file',$file);
                $response = (new B2BContactAdjustmentController())->handleFileUpload($request_body);
                $links[] = ($response->getData())->file_url;
        

                //$links[] = $file->getClientOriginalName();
                //$links[] = $file->storePubliclyAs($path,date('YmdHis') . '-' . $request->contact_type .'-doc.' . $file->getClientOriginalExtension(),'minio');

               /*   $response = Http::attach(
                'file', $file, '-' . $request->contact_type .'-doc.' . $file->getClientOriginalExtension()
                )->post(env('APP_URL').'/api/v1/datahub/minio-upload');
                $links[] = (json_decode($response,true))['file_url']; 
                */
               /*  $client = new Client([
                            // Base URI is used with relative requests
                            'base_uri' => env('APP_URL'),
                        ]);
 
                $response = $client->request('POST', '/api/v1/datahub/minio-upload', [
                    'multipart' => [
                        [
                            'name'     => 'file', // name value requires by endpoint
                            'contents' => $file,
                            'filename' => '-' . $request->contact_type .'-doc.' . $file->getClientOriginalExtension()
                        ],
                    ]
                ]);

                $links[] = (json_decode($response,true))['file_url']; */
        

            }

            $link = [];

            foreach ($links as $link_file) {
                $link[] = $link_file;
                
            }
            Files::insert([
                    "contact_id"=>$request->contact_id,
                    "file_name"=>json_encode($link)
                ]);

            // Return the path of the uploaded file
        return $this->successResponse(['link' => $link], 'File uploaded successfully', 200);
       

        }
          catch (\Exception $e) {
            return $this->errorResponse('File upload failed: ' . $e->getMessage(), 500);
          }
    }

    public function dataTypeImporter(Request $request)
    {
        $tempFile = $request->file('import'); 
        $reader = new XlsxReader();
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

        $new_results = [];
        foreach($results as $item){
            $keys = array_keys($item);
            foreach($keys as $value){
                $new_results[] = [
                "column"=>[$value=>$item[$value]],
                "data_type"=>ContactTypeHelper::checkDataType($item[$value])
            ];
            }
            
        }

        return $this->successResponse($new_results, 'File uploaded successfully', 200);
       
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
