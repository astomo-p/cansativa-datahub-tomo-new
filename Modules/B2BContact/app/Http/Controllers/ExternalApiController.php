<?php

namespace Modules\B2BContact\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\B2BContact\Emails\ShopUploadFileMail;
use Modules\B2BContactAdjustment\Http\Controllers\B2BContactAdjustmentController;

class ExternalApiController extends Controller
{
    use \App\Traits\ApiResponder;

    public function uploadInfoShop(Request $request)
    {
        $data = $request->validate([
            'pharmacy_name' => 'required|string',
            'pharmacy_email' => 'required|string',
            'pharmacy_phone' => 'required|string',
            'narcotics_number' => 'required|string',
            'country' => 'required|string',
            'street' => 'required|string',
            'postal_code' => 'required|string',
            'city' => 'required|string',
            'upload_file' => 'required|file|max:2048',
        ]);

        $b2bController = new B2BContactAdjustmentController();
        $uploadRequest = new Request();
        $uploadRequest->files->set('file', $data['upload_file']);

        $uploadResponse = $b2bController->handleFileUpload($uploadRequest);
        $uploadData = json_decode($uploadResponse->getContent(), true);

        if ($uploadResponse->getStatusCode() !== 200) {
            return $this->errorResponse(
                'Failed to upload file: ' . ($uploadData['message'] ?? 'Unknown error'),
                400
            );
        }

        $minioBaseUrl = env('MINIO_ENDPOINT');
        
        $data['file_name'] = $uploadData['original_filename'];
        $data['file_url'] = $minioBaseUrl.'/datahub/'.$uploadData['minio_path'];
        $data['upload_date'] = Carbon::now()->format('Y.m.d');

        // Send to admin email
        try {
            Mail::to('fariz@kemang.sg')->send(new ShopUploadFileMail($data, $data['file_url']));
        } catch (\Exception $e) {
            // Log the error if email sending fails, but don't prevent user creation
            Log::error('Failed to send email to fariz@kemang.sg: ' . $e->getMessage());
        }

        return $this->successResponse($data, 'File finish uploaded', 200);
    }
}
