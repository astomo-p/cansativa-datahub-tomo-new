<?php

namespace Modules\B2BContactAdjustment\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\B2BContactAdjustment\Models\B2BContacts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\B2BContact\Services\B2BContactService;
use Modules\B2BContact\Services\FileService;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Configuration\Exceptions;
use GuzzleHttp\Client; 
use GuzzleHttp\Exception\RequestException; 

class B2BContactAdjustmentController extends Controller
{
    use \App\Traits\ApiResponder;

    protected $contact_service;
    protected $file_service;

    public function __construct()
    {
        $this->file_service = new FileService;
        $this->contact_service = new B2BContactService;
    }

    public function getContactDataById($contact_type_id, $id)
    {
        $baseQuery = B2BContacts::where('is_deleted', false)
                                ->where('contact_type_id', $contact_type_id);

        $contacts = B2BContacts::with('documents')
            ->where('id', $id)
            ->where('is_deleted', false) 
            ->first();

        if(!$contacts){
            return $this->errorResponse('Error', 404, 'Pharmacy not found');
        }

        $totalContacts = (clone $baseQuery)->count();

        $previousId = (clone $baseQuery)
            ->where('id', '<', $id)
            ->orderBy('id', 'desc')
            ->value('id');

        $nextId = (clone $baseQuery)
            ->where('id', '>', $id)
            ->orderBy('id', 'asc')
            ->value('id');

        $positionNumber = (clone $baseQuery)
            ->where('id', '<=', $id)
            ->count();

        if ($contacts['contact_person']) {
            $contacts['contact_person'] = [
                [
                    'name' => $contacts->contact_person,
                    'email' => $contacts->email,
                    'phone_no' => $contacts->phone_no,
                ]
            ];
            unset($contacts['email'], $contacts['phone_no']);
        }

        $result = collect($contacts);
        
        $result->put('position_number', $positionNumber);
        $result->put('previous_id', $previousId);
        $result->put('next_id', $nextId);
        $result->put('total_contacts', $totalContacts);

       return $this->successResponse($result, 'Pharmacy data by ID', 200);
    }

    public function updateContact(Request $request, int $type, int $id)
    {
        $configs = [
            1 => [ // PHARMACY
                'contact_type_property' => 'contact_pharmacy',
                'not_found_message'     => 'Pharmacy not found',
                'success_message'       => 'Pharmacy data updated',
                'has_contact_person'    => true,
                'has_files'             => true,
                'validation_rules'      => [
                    'company_name' => 'nullable|string|max:255', 'email' => 'nullable|email|max:255',
                    'phone_no' => 'nullable|string|max:25', 'website' => 'nullable|url|max:255',
                    'address_line_1' => 'nullable|string|max:255', 'city' => 'nullable|string|max:100',
                    'state' => 'nullable|string|max:100', 'postal_code' => 'nullable|string|max:10',
                    'license_no' => 'nullable|string|max:100', 'pharmacist_in_charge' => 'nullable|string|max:255',
                    'contact_person' => 'nullable|array', 'files' => 'nullable|array|max:3',
                    'files.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
                ],
            ],
            2 => [ // SUPPLIER
                'contact_type_property' => 'contact_supplier',
                'not_found_message'     => 'Supplier not found',
                'success_message'       => 'Supplier data updated',
                'has_contact_person'    => true,
                'has_files'             => true,
                'validation_rules'      => [
                    'company_name' => 'nullable|string|max:255', 'email' => 'nullable|email|max:255',
                    'phone_no' => 'nullable|string|max:25', 'website' => 'nullable|url|max:255',
                    'tax_id' => 'nullable|string|max:50', 
                    'address_line_1' => 'nullable|string|max:255', 'city' => 'nullable|string|max:100',
                    'contact_person' => 'nullable|array', 'files' => 'nullable|array|max:3',
                    'files.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
                ],
            ],
            3 => [ // GENERAL NEWSLETTER
                'contact_type_property' => 'contact_general_newsletter',
                'not_found_message'     => 'General newsletter contact not found',
                'success_message'       => 'General newsletter data updated successfully',
                'has_contact_person'    => false,
                'has_files'             => false,
                'validation_rules'      => [
                    'name' => 'nullable|string|max:255',
                    'email' => [
                        'nullable', 'email', 'max:255',
                    ],
                    'phone_no' => 'nullable|string|max:25',
                    'whatsapp_subscription' => 'nullable|boolean',
                    'email_subscription' => 'nullable|boolean',
                ],
                'field_map'             => ['email_subscription' => 'cansativa_newsletter']
            ],
            4 => [ // COMMUNITY
                'contact_type_property' => 'contact_community', 
                'not_found_message'     => 'Community contact not found',
                'success_message'       => 'Community contact data updated',
                'has_contact_person'    => false,
                'has_files'             => false,
                'validation_rules'      => [
                    'name' => 'nullable|string|max:255',
                     'email' => [
                        'nullable', 'email', 'max:255',
                    ],
                    'community_role' => 'nullable|string|max:100' 
                ],
            ],
        ];

        if (!isset($configs[$type])) {
            return $this->errorResponse('Error', 404, 'Invalid contact type specified.');
        }
        $config = $configs[$type];

        $validatedData = $request->validate($config['validation_rules']);
        $contact = B2BContacts::where('id', $id)
            ->where('contact_type_id', $this->{$config['contact_type_property']}->id)
            ->where('is_deleted', false)
            ->first();

        if (!$contact) {
            return $this->errorResponse('Error', 404, $config['not_found_message']);
        }

        DB::beginTransaction();
        try {
            if (isset($config['field_map'])) {
                foreach ($config['field_map'] as $from => $to) {
                    if (isset($validatedData[$from])) {
                        $validatedData[$to] = $validatedData[$from];
                        unset($validatedData[$from]);
                    }
                }
            }
            if ($config['has_contact_person'] && $request->has('contact_person')) {
                foreach ($request->input('contact_person') as $contact_person) {
                    if (isset($contact_person['email'])) {
                        $validatedData['email'] = $contact_person['email'];
                    }
                    if (isset($contact_person['phone_no'])) {
                        $validatedData['phone_no'] = $contact_person['phone_no'];
                    }
                    if (isset($contact_person['name'])) {
                        $validatedData['contact_person'] = $contact_person['name'];
                    }
                    break;
                }
            }
            $validatedData['updated_date'] = now();
            $contact->update($validatedData);
            if ($config['has_files'] && $request->hasFile('files')) {
                $this->file_service->uploadFile($contact->id, $request->file('files'), '/uploads/contact-data');
            }
            DB::commit();
            return $this->successResponse(null, $config['success_message'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update contact [type: {$type}, id: {$id}]: " . $e->getMessage());
            return $this->errorResponse('Error', 400, 'Update data failed due to a server error.');
        }
    }


    public function handleFileUpload(Request $request)
    {
        // 1. Validate the incoming file
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName(); 
        $disk = 'minio'; 

        $storedFilename = uniqid() . '-' . $originalFilename;
        $minioPath = ''; 

        try {
            $minioPath = Storage::disk($disk)->putFileAs('', $file, $storedFilename);

            if (!$minioPath) {
                Log::error('MinIO upload failed for file: ' . $originalFilename);
                return response()->json(['message' => 'Failed to upload file to storage.'], 500);
            }

        } catch (\Exception $e) {
            Log::error('MinIO upload exception for file: ' . $originalFilename . ' - ' . $e->getMessage());
            return response()->json(['message' => 'Error during initial file upload to storage.'], 500);
        }

        $temporaryUrlExpiration = Carbon::now()->addDays(2); 
        $temporaryFileUrl = '';

        try {
            $temporaryFileUrl = Storage::disk($disk)->temporaryUrl($minioPath, $temporaryUrlExpiration);
        } catch (\Exception $e) {
            Log::error('Failed to generate temporary URL for MinIO file: ' . $minioPath . ' - ' . $e->getMessage());
            Storage::disk($disk)->delete($minioPath);
            return response()->json(['message' => 'Failed to prepare file for scanning (temporary URL issue).'], 500);
        }

        $bytescaleBaseUrl   = env('BYTESCALE_URL');
        $bytescaleAccountId = env('BYTESCALE_ACCOUNT');
        $bytescaleApiKey    = env('BYTESCALE_APIKEY');

        if (!$bytescaleBaseUrl || !$bytescaleAccountId || !$bytescaleApiKey) {
            Log::error('Bytescale API configuration missing or incomplete. Check .env and services.php.');
            Storage::disk($disk)->delete($minioPath); // Clean up uploaded file
            return response()->json(['message' => 'Server configuration error for Bytescale service.'], 500);
        }

        $client = new Client();
        $bytescaleScanEndpoint = "{$bytescaleBaseUrl}/v2/accounts/{$bytescaleAccountId}/uploads/url";

        $shouldScan = filter_var(env('BYTESCALE_SCAN', false), FILTER_VALIDATE_BOOLEAN);
        if (!$shouldScan) {
            return response()->json([
                'message' => 'File uploaded successfully (scan skipped).',
                'original_filename' => $originalFilename,
                'minio_path' => $minioPath,
                'bytescale_file_path' => null,
                'bytescale_file_url' => null,
                'bytescale_etag' => null,
                'minio_temp_url_expires_at' => $temporaryUrlExpiration->toDateTimeString(),
                'file_url' => $temporaryFileUrl,
            ]);
        }

        try {
            $bytescaleApiResponse = $client->post($bytescaleScanEndpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $bytescaleApiKey,
                ],
                'json' => [
                    'url' => $temporaryFileUrl,
                ],
                'verify' => false, 
            ]);

            $bytescaleResult = json_decode($bytescaleApiResponse->getBody()->getContents(), true);

            if ($bytescaleApiResponse->getStatusCode() === 200 && 
                isset($bytescaleResult['filePath'], $bytescaleResult['fileUrl'])) {

                return response()->json([
                    'message' => 'File uploaded and scanned successfully.',
                    'original_filename' => $originalFilename,
                    'minio_path' => $minioPath,
                    'bytescale_file_path' => $bytescaleResult['filePath'], 
                    'bytescale_file_url' => $bytescaleResult['fileUrl'],   
                    'bytescale_etag' => $bytescaleResult['etag'] ?? null,
                    'minio_temp_url_expires_at' => $temporaryUrlExpiration->toDateTimeString(), 
                    'file_url' => $temporaryFileUrl,
                ]);
            } else {
                Log::warning('Bytescale scan completed but returned unexpected data for file: ' . $minioPath . '. Response: ' . $bytescaleApiResponse->getBody()->getContents());
                Storage::disk($disk)->delete($minioPath);
                return response()->json(['message' => 'File scan completed but result was inconclusive. File deleted.'], 422);
            }

        } catch (RequestException $e) {
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response body.';
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;

            Log::error('Bytescale API request failed for file: ' . $minioPath . '. Status: ' . $statusCode . '. Error: ' . $e->getMessage() . '. Response: ' . $responseBody);

            Storage::disk($disk)->delete($minioPath); // Delete file from MinIO
            return response()->json(['message' => 'File scan failed by Bytescale service. Details: ' . $responseBody], 422);

        } catch (\Exception $e) {
            // Catch any other unexpected exceptions
            Log::critical('CRITICAL ERROR during Bytescale scan for file: ' . $minioPath . ' - ' . $e->getMessage(), ['exception' => $e]);
            Storage::disk($disk)->delete($minioPath); // Delete file from MinIO
            return response()->json(['message' => 'An unexpected server error occurred during file scan.'], 500);
        }
    }


    public function handleFileUploadWithScan(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // Max 10MB 
        ]);

        $file = $request->file('file');
        $filename = $file->getClientOriginalName(); 
        $filePathInMinio = ''; 

        try {
            $filePathInMinio = Storage::disk('minio')->putFileAs('', $file, $filename);

            $scanExpiration = Carbon::now()->addMinutes(5);
            $temporaryUrlForScan = Storage::disk('minio')->temporaryUrl($filePathInMinio, $scanExpiration);

            $bytescaleApiResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('BYTESCALE_API_KEY'), 
                'Content-Type' => 'application/json',
            ])->post(env('BYTESCALE_SCAN_API_ENDPOINT'), [
                'fileUrl' => $temporaryUrlForScan,
            ]);

            if ($bytescaleApiResponse->successful() && $bytescaleApiResponse->json('scan_status') === 'clean') {
                $userExpiration = Carbon::now()->addHours(24);
                $temporaryUrlForUser = Storage::disk('minio')->temporaryUrl($filePathInMinio, $userExpiration);

                return response()->json([
                    'message' => 'File uploaded and scanned successfully.',
                    'filename' => $filename,
                    'path' => $filePathInMinio,
                    'url' => $temporaryUrlForUser,
                    'expires_at' => $userExpiration->toDateTimeString(),
                ]);
            } else {
                // Log the Bytescale response for debugging.
                Log::error('Bytescale scan failed or detected issue for file: ' . $filename, [
                    'bytescale_response' => $bytescaleApiResponse->json(),
                    'status' => $bytescaleApiResponse->status(),
                ]);

                // Delete the file from Minio bucket.
                Storage::disk('minio')->delete($filePathInMinio);

                return response()->json([
                    'message' => 'File scan failed or detected malicious content. Upload rejected.',
                    'errors' => $bytescaleApiResponse->json(), 
                ], 400); 
            }

        } catch (Exceptions $e) {
            Log::error('File upload or scan error: ' . $e->getMessage(), [
                'file' => $filename,
                'exception' => $e->getTraceAsString(),
            ]);

            if (Storage::disk('minio')->exists($filePathInMinio)) {
                Storage::disk('minio')->delete($filePathInMinio);
            }

            return response()->json([
                'message' => 'An error occurred during file upload or scanning.',
                'error' => $e->getMessage(),
            ], 500); // Internal Server Error
        }
    }

}
