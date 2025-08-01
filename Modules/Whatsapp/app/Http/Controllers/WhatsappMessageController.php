<?php

namespace Modules\Whatsapp\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Whatsapp\Services\WhatsappAPIService;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Traits\ApiResponder;
use Illuminate\Support\Facades\Log;
use Modules\NewContactData\Models\Contacts as ModelsContacts;
use Modules\Whatsapp\Traits\WhatsappErrorHandler;
use Modules\Whatsapp\Models\MessageAssignment;
use Modules\Contacts\Entities\Contacts;
use Modules\Whatsapp\Entities\Message;
use Modules\Whatsapp\Models\Message as ModelsMessage;

class WhatsappMessageController extends Controller
{
    use ApiResponder, WhatsappErrorHandler;

    protected $whatsappService;

    public function __construct(WhatsappAPIService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function getMessageData(Request $request): JsonResponse
    {
        try {
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search', null);
            $filtersRaw = $request->input('filters', null);

            $filters = null;
            if ($filtersRaw) {
                try {
                    $decoded = urldecode($filtersRaw);
                    $filters = json_decode($decoded, true);
                } catch (\Exception $ex) {
                    Log::warning('Failed to parse filters', [
                        'raw' => $filtersRaw,
                        'error' => $ex->getMessage()
                    ]);
                }
            }

            $page = max(1, (int)$page);
            $perPage = max(1, (int)$perPage);

            $data = MessageAssignment::getMessageData($page, $perPage, $search, $filters);
            return $this->successResponse($data, 'Message data retrieved successfully');
        } catch (Exception $e) {
            Log::error('Error retrieving message data: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to retrieve message data: ' . $e->getMessage(),
                500
            );
        }
    }

    public function sendText(Request $request): JsonResponse
    {
        try {
            try {
                $validated = $request->validate([
                    'contactId' => 'required|exists:contacts,id',
                    'text' => 'required|string',
                    'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,xls,xlsx,mp3,mp4,wav,avi,zip,rar|max:16384',
                    'attachments.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,xls,xlsx,mp3,mp4,wav,avi,zip,rar|max:16384',
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return $this->validationErrorResponse($e->errors());
            }

            $contact = ModelsContacts::find($request->contactId);
            if (!$contact || empty($contact->phone_no)) {
                return $this->errorResponse(
                    'Contact not found or has no phone number',
                    400
                );
            }

            $mediaFile = null;
            $mediaType = 'text';
            $attachmentUrl = null;

            $files = [];

            if ($request->hasFile('attachment')) {
                $files[] = $request->file('attachment');
            }

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $files[] = $file;
                }
            }

            if (!empty($files)) {
                $file = $files[0];

                $mimeType = $file->getMimeType();
                if (str_contains($mimeType, 'image/')) {
                    $mediaType = 'image';
                } elseif (str_contains($mimeType, 'video/')) {
                    $mediaType = 'video';
                } elseif (str_contains($mimeType, 'audio/')) {
                    $mediaType = 'audio';
                } else {
                    $mediaType = 'document';
                }

                $storedFiles = [];
                foreach ($files as $attachmentFile) {
                    $originalName = $attachmentFile->getClientOriginalName();
                    $sanitizedName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalName);
                    $fileName = time() . '_' . $sanitizedName;
                    $path = $attachmentFile->storeAs('whatsapp', $fileName, 'public');
                    $appUrl = env('APP_URL', 'http://localhost');
                    $fileUrl = rtrim($appUrl, '/') . '/storage/' . $path;

                    $storedFiles[] = $fileUrl;
                }

                $mediaFile = $storedFiles[0];
                $attachmentUrl = json_encode($storedFiles);

                Log::info('Prepared attachments for WhatsApp message', [
                    'contactId' => $request->contactId,
                    'mediaType' => $mediaType,
                    'primaryUrl' => $mediaFile,
                    'allFiles' => count($storedFiles)
                ]);

                $response = $this->whatsappService->sendMediaMessage(
                    $contact->phone_no,
                    $mediaType,
                    $mediaFile,
                    $request->text
                );
            } else {
                $mediaType = 'text';
                $response = $this->whatsappService->sendTextMessage(
                    $contact->phone_no,
                    $request->text
                );
            }

            $errorResponse = $this->handleWhatsappError($response, 'Failed to send message');
            if ($errorResponse) {
                Log::error('Error sending text message: ' . $errorResponse['error']['message'], [
                    'request' => $request->all(),
                    'error' => $errorResponse['error']
                ]);

                $wamid = isset($errorResponse['error']['fbtrace_id'])
                    ? 'fbtrace_' . $errorResponse['error']['fbtrace_id']
                    : 'error_' . time();

                ModelsMessage::create([
                    'wamid' => $wamid,
                    'contact_id' => $request->contactId,
                    'status' => 'failed',
                    'direction' => 1,
                    'type' => $mediaType,
                    'media_file' => isset($attachmentUrl) ? $attachmentUrl : null,
                    'body_text' => $request->text,
                    'body' => $request->text,
                    'error_code' => $errorResponse['error']['code'] ?? 'API_ERROR',
                    'error_message' => $errorResponse['error']['message'] ?? 'WhatsApp API error'
                ]);

                return $this->errorResponse(
                    'Failed to send message: ' . $errorResponse['error']['message'],
                    400,
                    $errorResponse['error']['original_error']
                );
            }

            $messageId = $response['messages'][0]['id'] ?? null;

            ModelsMessage::create([
                'wamid' => $messageId,
                'contact_id' => $request->contactId,
                'status' => 'accepted',
                'direction' => 1,
                'type' => $mediaType,
                'media_file' => isset($attachmentUrl) ? $attachmentUrl : null,
                'body_text' => $request->text,
                'body' => $request->text
            ]);

            $contact->updateLastMessageAt();

            return $this->successResponse($response, 'Message sent successfully');
        } catch (Exception $e) {
            Log::error('Error sending text message: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->errorResponse(
                'Failed to send message: ' . $e->getMessage(),
                500,
                [
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage(),
                    'exception' => get_class($e)
                ]
            );
        }
    }

    public function sendTemplate(Request $request): JsonResponse
    {
        try {
            try {
                $validated = $request->validate([
                    'templateId' => 'required|exists:wa_chat_templates,id',
                    'contactId' => 'required|exists:contacts,id'
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return $this->validationErrorResponse($e->errors());
            }

            $response = $this->whatsappService->sendTemplateMessage(
                $request->templateId,
                $request->contactId
            );

            $errorResponse = $this->handleWhatsappError($response, 'Failed to send template message');
            if ($errorResponse) {
                Log::error('Error sending template message: ' . $errorResponse['error']['message'], [
                    'request' => $request->all(),
                    'error' => $errorResponse['error']
                ]);

                return $this->errorResponse(
                    'Failed to send template message: ' . $errorResponse['error']['message'],
                    400,
                    $errorResponse['error']['original_error']
                );
            }

            return $this->successResponse($response, 'Template message sent successfully');
        } catch (Exception $e) {
            Log::error('Error sending template message: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->errorResponse(
                'Failed to send template message: ' . $e->getMessage(),
                500,
                [
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage(),
                    'exception' => get_class($e)
                ]
            );
        }
    }

    public function listTemplates(): JsonResponse
    {
        try {
            $response = $this->whatsappService->getTemplates();

            $errorResponse = $this->handleWhatsappError($response, 'Failed to fetch templates');
            if ($errorResponse) {
                Log::error('Error retrieving templates: ' . $errorResponse['error']['message'], [
                    'error' => $errorResponse['error']
                ]);

                return $this->errorResponse(
                    'Failed to fetch templates: ' . $errorResponse['error']['message'],
                    400,
                    $errorResponse['error']['original_error']
                );
            }

            return $this->successResponse($response, 'Templates retrieved successfully');
        } catch (Exception $e) {
            Log::error('Error retrieving templates: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to retrieve templates: ' . $e->getMessage(),
                500,
                [
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage()
                ]
            );
        }
    }

    public function createTemplate(Request $request): JsonResponse
    {
        try {
            try {
                $validated = $request->validate([
                    'name' => 'required|string',
                    'category' => 'required|string|in:UTILITY,MARKETING,AUTHENTICATION',
                    'language' => 'required|string',
                    'components' => 'required|array',
                    'parameter_format' => 'nullable|string|in:NAMED,POSITIONAL'
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return $this->validationErrorResponse($e->errors());
            }

            $response = $this->whatsappService->createTemplate($request->all());

            $errorResponse = $this->handleWhatsappError($response, 'Failed to create template');
            if ($errorResponse) {
                Log::error('Error creating template: ' . $errorResponse['error']['message'], [
                    'request' => $request->all(),
                    'error' => $errorResponse['error']
                ]);

                return $this->errorResponse(
                    'Failed to create template: ' . $errorResponse['error']['message'],
                    400,
                    $errorResponse['error']['original_error']
                );
            }

            return $this->successResponse($response, 'Template created successfully', 201);
        } catch (Exception $e) {
            Log::error('Error creating template: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->errorResponse(
                'Failed to create template: ' . $e->getMessage(),
                500,
                [
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage(),
                    'exception' => get_class($e)
                ]
            );
        }
    }

    public function deleteTemplate(Request $request): JsonResponse
    {
        try {
            try {
                $validated = $request->validate([
                    'name' => 'required|string',
                    'template_id' => 'nullable|string'
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return $this->validationErrorResponse($e->errors());
            }

            $response = $this->whatsappService->deleteTemplate(
                $request->name,
                $request->template_id
            );

            $errorResponse = $this->handleWhatsappError($response, 'Failed to delete template');
            if ($errorResponse) {
                Log::error('Error deleting template: ' . $errorResponse['error']['message'], [
                    'request' => $request->all(),
                    'error' => $errorResponse['error']
                ]);

                return $this->errorResponse(
                    'Failed to delete template: ' . $errorResponse['error']['message'],
                    400,
                    $errorResponse['error']['original_error']
                );
            }

            return $this->successResponse($response, 'Template deleted successfully');
        } catch (Exception $e) {
            Log::error('Error deleting template: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->errorResponse(
                'Failed to delete template: ' . $e->getMessage(),
                500,
                [
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage(),
                    'exception' => get_class($e)
                ]
            );
        }
    }

    public function getMessageDetailsByContact(int $contactId, Request $request): JsonResponse
    {
        try {
            $contact = ModelsContacts::find($contactId);
            if (!$contact) {
                return $this->errorResponse('Contact not found', 404);
            }

            $page = $request->input('page', 1);
            $perPage = 25;

            $page = max(1, (int)$page);

            $query = ModelsMessage::where('contact_id', $contactId)
                ->where('status', '!=', 'failed')
                ->orderBy('created_at', 'desc');

            $total = $query->count();
            $offset = ($page - 1) * $perPage;
            $messages = $query->skip($offset)->take($perPage)->get();

            $messageIds = $messages->pluck('id')->toArray();
            if (!empty($messageIds)) {
                ModelsMessage::whereIn('id', $messageIds)
                    ->where('is_read', false)
                    ->update(['is_read' => true]);
            }

            $responseData = [
                'chatInfo' => [
                    'contactName' => $contact->contact_name ?? 'Unknown',
                    'contactNo' => $contact->contact_no ?? '-',
                    'phoneNo' => $contact->phone_no ?? '-',
                    'address' => $contact->address ?? '-',
                    'postCode' => $contact->post_code ?? '-',
                    'city' => $contact->city ?? '-',
                    'country' => $contact->country ?? '-',
                    'contactPerson' => $contact->contact_person ?? '-',
                    'email' => $contact->email ?? '-',
                    'amountPurchase' => $contact->amount_purchase ?? '-',
                    'averagePurchase' => $contact->average_purchase ?? '-',
                    'totalPurchase' => $contact->total_purchase ?? '-',
                    'lastPurchaseDate' => $contact->last_purchase_date ?? '-',
                    'createdDate' => $contact->created_date ?? '-'
                ],
                'messages' => $messages,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage)
                ]
            ];

            return $this->successResponse(
                $responseData,
                'Chats retrieved successfully'
            );
        } catch (Exception $e) {
            Log::error('Error retrieving message details by contact: ' . $e->getMessage(), [
                'contactId' => $contactId,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to retrieve message details: ' . $e->getMessage(),
                500
            );
        }
    }
}
