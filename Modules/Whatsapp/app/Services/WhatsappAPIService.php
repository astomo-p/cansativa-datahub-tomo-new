<?php

namespace Modules\Whatsapp\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\NewContactData\Models\Contacts;
use Modules\Whatsapp\Models\Message;
use Modules\Whatsapp\Models\Template;
use Modules\Whatsapp\Models\WhatsappChatTemplate;
use Modules\Whatsapp\Traits\WhatsappServiceErrorHandler;

class WhatsappAPIService
{
    use WhatsappServiceErrorHandler;
    protected $baseUrl = 'https://graph.facebook.com/v22.0/';
    protected $phoneNumberId;
    protected $businessAccountId;
    protected $accessToken;

    public function __construct()
    {
        $this->phoneNumberId = config('whatsapp.phone_number_id');
        $this->businessAccountId = config('whatsapp.business_account_id');
        $this->accessToken = config('whatsapp.api_token');
    }

    public function sendTextMessage(string $to, string $message)
    {
        $endpoint = $this->baseUrl . $this->phoneNumberId . '/messages';

        try {
            $response = Http::withToken($this->accessToken)
                ->post($endpoint, [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'body' => $message
                    ]
                ]);

            $responseData = $response->json();

            if (!isset($responseData['error'])) {
                try {
                    $contact = Contacts::firstOrCreate(
                        ['phone_no' => $to]
                    );

                    if ($contact->wasRecentlyCreated) {
                        $contact->status = 'hidden';
                        $contact->created_date = now();
                        $contact->created_by = 1;
                        $contact->save();
                    }

                    Message::create([
                        'wamid' => $responseData['messages'][0]['id'] ?? null,
                        'contact_id' => $contact->id,
                        'status' => 'accepted',
                        'direction' => 1,
                        'type' => 'text',
                        'body' => $message,
                        'body_text' => $message
                    ]);

                    $contact->updateLastMessageAt();
                } catch (\Exception $e) {
                    Log::error('Error saving text message to database: ' . $e->getMessage());
                }
            } else {
                try {
                    $contact = Contacts::firstOrCreate(
                        ['phone_no' => $to]
                    );

                    if ($contact->wasRecentlyCreated) {
                        $contact->status = 'hidden';
                        $contact->created_at = now();
                        $contact->created_by = 1;
                        $contact->save();
                    }

                    $wamid = isset($responseData['error']['fbtrace_id'])
                        ? 'fbtrace_' . $responseData['error']['fbtrace_id']
                        : 'error_' . time();

                    Message::create([
                        'wamid' => $wamid,
                        'contact_id' => $contact->id,
                        'status' => 'failed',
                        'direction' => 1,
                        'type' => 'text',
                        'body' => $message,
                        'body_text' => $message,
                        'error_code' => $responseData['error']['code'] ?? 'API_ERROR',
                        'error_message' => $responseData['error']['message'] ?? 'WhatsApp API error'
                    ]);

                    Log::error('Failed to send text message: ', [
                        'to' => $to,
                        'error' => $responseData['error'],
                        'wamid' => $wamid
                    ]);
                } catch (\Exception $dbEx) {
                    Log::critical('Failed to save error message: ' . $dbEx->getMessage());
                }
            }

            return $responseData;
        } catch (\Exception $e) {
            Log::error('Exception sending text message: ' . $e->getMessage());

            try {
                $contact = Contacts::firstOrCreate(
                    ['phone_no' => $to]
                );

                if ($contact->wasRecentlyCreated) {
                    $contact->status = 'hidden';
                    $contact->created_at = now();
                    $contact->created_by = 1;
                    $contact->save();
                }

                Message::create([
                    'wamid' => 'exception_' . time(),
                    'contact_id' => $contact->id,
                    'status' => 'failed',
                    'direction' => 1,
                    'type' => 'text',
                    'body' => $message,
                    'body_text' => $message,
                    'error_code' => 'EXCEPTION',
                    'error_message' => substr($e->getMessage(), 0, 500)
                ]);
            } catch (\Exception $dbEx) {
                Log::critical('Failed to save exception message: ' . $dbEx->getMessage());
            }

            return $this->formatExceptionAsError($e, 'sendTextMessage');
        }
    }

    public function sendTemplateMessage(string $templateId, string $contactId)
    {
        try {
            $template = WhatsappChatTemplate::find($templateId);
            if (!$template) {
                Log::error('Template not found', ['templateId' => $templateId]);
                return [
                    'error' => [
                        'code' => 'TEMPLATE_ERROR',
                        'message' => 'Template not found'
                    ]
                ];
            }

            $contact = Contacts::find($contactId);
            if (!$contact || empty($contact->phone_no)) {
                Log::error('Contact not found or has no phone number', ['contactId' => $contactId]);
                return [
                    'error' => [
                        'code' => 'CONTACT_ERROR',
                        'message' => 'Contact not found or has no phone number'
                    ]
                ];
            }

            $endpoint = $this->baseUrl . $this->phoneNumberId . '/messages';
            $message = $template->message;

            $response = Http::withToken($this->accessToken)
                ->post($endpoint, [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $contact->phone_no,
                    'type' => 'template',
                    'template' => [
                        'name' => $template->template_name,
                        'language' => [
                            'code' => $template->language_code
                        ]
                    ]
                ]);

            $responseData = $response->json();

            if (!isset($responseData['error'])) {
                try {
                    Message::create([
                        'wamid' => $responseData['messages'][0]['id'] ?? null,
                        'contact_id' => $contactId,
                        'status' => 'accepted',
                        'direction' => 1,
                        'type' => 'template',
                        'template_name' => $template->template_name,
                        'template_language' => $template->language_code,
                        'body' => $message,
                        'body_text' => $message
                    ]);

                    $contact->updateLastMessageAt();
                } catch (\Exception $e) {
                    Log::error('Error saving template message to database: ' . $e->getMessage());
                }
            } else {
                try {
                    $wamid = isset($responseData['error']['fbtrace_id'])
                        ? 'fbtrace_' . $responseData['error']['fbtrace_id']
                        : 'error_' . time();

                    Message::create([
                        'wamid' => $wamid,
                        'contact_id' => $contactId,
                        'status' => 'failed',
                        'direction' => 1,
                        'type' => 'template',
                        'template_name' => $template->template_name,
                        'template_language' => $template->language_code,
                        'body' => $message,
                        'body_text' => $message,
                        'error_code' => $responseData['error']['code'] ?? 'API_ERROR',
                        'error_message' => $responseData['error']['message'] ?? 'WhatsApp API error'
                    ]);

                    Log::error('Failed to send template message: ', [
                        'to' => $contact->phone_no,
                        'template' => $template->template_name,
                        'error' => $responseData['error']
                    ]);
                } catch (\Exception $dbEx) {
                    Log::critical('Failed to save error template message: ' . $dbEx->getMessage());
                }
            }

            return $responseData;
        } catch (\Exception $e) {
            Log::error('Exception sending template message: ' . $e->getMessage());

            try {
                $contact = Contacts::find($contactId);
                $template = WhatsappChatTemplate::find($templateId);

                $templateName = $template ? $template->template_name : 'unknown';
                $languageCode = $template ? $template->language_code : 'en';
                $message = $template ? $template->message : '';

                Message::create([
                    'wamid' => 'exception_' . time(),
                    'contact_id' => $contactId,
                    'status' => 'failed',
                    'direction' => 1,
                    'type' => 'template',
                    'template_name' => $templateName,
                    'template_language' => $languageCode,
                    'body' => $message,
                    'body_text' => $message,
                    'error_code' => 'EXCEPTION',
                    'error_message' => substr($e->getMessage(), 0, 500)
                ]);
            } catch (\Exception $dbEx) {
                Log::critical('Failed to save exception template message: ' . $dbEx->getMessage());
            }

            return $this->formatExceptionAsError($e, 'sendTemplateMessage');
        }
    }

    public function sendNewsLetterTemplate(string $templateId, string $contactId)
    {
        try {
            $template = Template::find($templateId);

            Log::info('Found template', [
                'templateId' => $templateId,
                'templateName' => $template ? $template->name : 'not found'
            ]);

            if (!$template) {
                Log::error('Template not found', ['templateId' => $templateId]);
                return [
                    'error' => [
                        'code' => 'TEMPLATE_ERROR',
                        'message' => 'Template not found'
                    ]
                ];
            }

            $contact = Contacts::find($contactId);
            if (!$contact || empty($contact->phone_no)) {
                Log::error('Contact not found or has no phone number', ['contactId' => $contactId]);
                return [
                    'error' => [
                        'code' => 'CONTACT_ERROR',
                        'message' => 'Contact not found or has no phone number'
                    ]
                ];
            }

            $endpoint = $this->baseUrl . $this->phoneNumberId . '/messages';

            // Remove "format" key from each component if present
            // Decode components from JSON if necessary
            $components = $template->components;
            if (is_string($components)) {
                $components = json_decode($components, true);
            }
            if (!is_array($components)) {
                $components = [];
            }

            // Transform components: lowercase type/format, remove "format", and convert "text" to parameters
            $waComponents = [];
            foreach ($components as $component) {
                $waComponent = [];

                // Lowercase type
                $waComponent['type'] = strtolower($component['type'] ?? '');

                // Remove "format" key if present
                if (isset($component['format'])) {
                    // Optionally, you can use the format for parameter type if needed
                    unset($component['format']);
                }

                // Convert "text" to parameters if present
                if (isset($component['text'])) {
                    $waComponent['parameters'][] = [
                        'type' => 'text',
                        'text' => $component['text']
                    ];
                }

                // If "buttons" exist, copy as is (WhatsApp expects "buttons" key)
                if (isset($component['buttons'])) {
                    $waComponent['buttons'] = $component['buttons'];
                }

                // If already has "parameters", copy as is
                if (isset($component['parameters'])) {
                    $waComponent['parameters'] = $component['parameters'];
                }

                $waComponents[] = $waComponent;
            }

            $response = Http::withToken($this->accessToken)
                ->post($endpoint, [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $contact->phone_no,
                    'type' => 'template',
                    'template' => [
                        'name' => $template->name,
                        'language' => [
                            'code' => $template->language
                        ],
                        'components' => $waComponents
                    ]
                ]);

            Log::info('Sending WhatsApp template message', [
                'to' => $contact->phone_no,
                'templateId' => $templateId,
                'templateName' => $template->name,
                'language' => $template->language,
                'components' => $waComponents
            ]);
            Log::info('WhatsApp API response', ['response' => $response->json()]);

            $responseData = $response->json();

            if (!isset($responseData['error'])) {
                try {
                    Message::create([
                        'wamid' => $responseData['messages'][0]['id'] ?? null,
                        'contact_id' => $contactId,
                        'status' => 'accepted',
                        'direction' => 1,
                        'type' => 'template',
                        'template_name' => $template->name,
                        'template_language' => $template->language,
                        'body' => $template->body,
                        'body_text' => $template->body
                    ]);

                    $contact->updateLastMessageAt();
                } catch (\Exception $e) {
                    Log::error('Error saving template message to database: ' . $e->getMessage());
                }
            } else {
                try {
                    $wamid = isset($responseData['error']['fbtrace_id'])
                        ? 'fbtrace_' . $responseData['error']['fbtrace_id']
                        : 'error_' . time();

                    Message::create([
                        'wamid' => $wamid,
                        'contact_id' => $contactId,
                        'status' => 'failed',
                        'direction' => 1,
                        'type' => 'template',
                        'template_name' => $template->name,
                        'template_language' => $template->language,
                        'body' => $template->body,
                        'body_text' => $template->body,
                        'error_code' => $responseData['error']['code'] ?? 'API_ERROR',
                        'error_message' => $responseData['error']['message'] ?? 'WhatsApp API error'
                    ]);

                    Log::error('Failed to send template message: ', [
                        'to' => $contact->phone_no,
                        'template' => $template->name,
                        'error' => $responseData['error']
                    ]);
                } catch (\Exception $dbEx) {
                    Log::critical('Failed to save error template message: ' . $dbEx->getMessage());
                }
            }

            return $responseData;
        } catch (\Exception $e) {
            Log::error('Exception sending template message: ' . $e->getMessage());

            try {
                $contact = Contacts::find($contactId);
                $template = Template::find($templateId);

                $templateName = $template ? $template->name : 'unknown';
                $languageCode = $template ? $template->language : 'en';
                $body = $template ? $template->body : '';

                Message::create([
                    'wamid' => 'exception_' . time(),
                    'contact_id' => $contactId,
                    'status' => 'failed',
                    'direction' => 1,
                    'type' => 'template',
                    'template_name' => $templateName,
                    'template_language' => $languageCode,
                    'body' => $body,
                    'body_text' => $body,
                    'error_code' => 'EXCEPTION',
                    'error_message' => substr($e->getMessage(), 0, 500)
                ]);
            } catch (\Exception $dbEx) {
                Log::critical('Failed to save exception template message: ' . $dbEx->getMessage());
            }

            return $this->formatExceptionAsError($e, 'sendNewsLetterTemplate');
        }
    }

    public function getTemplates(
        ?string $search = null,
        ?string $status = null,
        ?string $type = null,
        ?string $language = null,
        int $perPage = 10,
        int $page = 1
    ) {
        $query = Template::select([
            'id',
            'fbid',
            'name',
            'language',
            'category',
            'status',
            'api_status',
            'parameter_format',
            'created_at',
            'updated_at'
        ])->orderBy('created_at', 'desc');

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($status && in_array($status, Template::STATUSES)) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('category', $type);
        }

        if ($language) {
            $query->where('language', $language);
        }

        $total = $query->count();
        $templates = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'status' => 'success',
            'data' => [
                'results' => $templates,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($total / $perPage),
                    'from' => ($page - 1) * $perPage + 1,
                    'to' => min($page * $perPage, $total),
                ]
            ]
        ];
    }

    public function createTemplate(array $data)
    {
        try {
            $dbTemplate = Template::create([
                'name' => $data['name'],
                'language' => $data['language'],
                'components' => $data['components'] ?? null,
                'category' => $data['category'],
                'status' => 'DRAFT',
                'api_status' => null,
                'parameter_format' => $data['parameter_format'] ?? null,
                'fbid' => null
            ]);

            Log::info('Template record created in database', [
                'template_id' => $dbTemplate->id,
                'name' => $dbTemplate->name,
                'status' => $dbTemplate->status
            ]);

            return [
                'id' => $dbTemplate->id,
                'name' => $dbTemplate->name,
                'language' => $dbTemplate->language,
                'category' => $dbTemplate->category,
                'status' => $dbTemplate->status,
                'parameter_format' => $dbTemplate->parameter_format,
                'components' => $dbTemplate->components,
                'created_at' => $dbTemplate->created_at,
                'updated_at' => $dbTemplate->updated_at
            ];
        } catch (\Exception $e) {
            Log::error('Error creating template: ' . $e->getMessage(), [
                'template_name' => $data['name'],
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'error' => [
                    'code' => 'DB_ERROR',
                    'message' => 'Failed to create template: ' . $e->getMessage()
                ]
            ];
        }
    }

    protected function extractTemplateText(array $components): string
    {
        $text = [];

        foreach ($components as $component) {
            if (isset($component['text'])) {
                $text[] = $component['text'];
            } elseif (isset($component['parameters'])) {
                foreach ($component['parameters'] as $param) {
                    if (isset($param['text'])) {
                        $text[] = $param['text'];
                    }
                }
            }
        }

        return implode("\n", $text);
    }

    public function deleteTemplate(string $templateName, string $templateId)
    {
        try {
            $endpoint = $this->baseUrl . $this->businessAccountId . '/message_templates';

            $params = [
                'name' => $templateName,
                'template_id' => $templateId
            ];

            $response = Http::timeout(30)
                ->withToken($this->accessToken)
                ->delete($endpoint . '?' . http_build_query($params));

            if ($response->failed()) {
                if ($response->status() === 404 || (isset($response->json()['error']['code']) && $response->json()['error']['code'] == 100)) {
                    try {
                        $deleted = Template::where('id', $templateId)->delete();
                        if ($deleted) {
                            Log::info('Template not found in WhatsApp API but deleted from database', [
                                'template_name' => $templateName,
                                'template_id' => $templateId
                            ]);
                            return [
                                'status' => 'success',
                                'message' => "Template {$templateName} not found in WhatsApp but removed from database"
                            ];
                        }
                    } catch (\Exception $dbEx) {
                        Log::error('Error deleting template from database: ' . $dbEx->getMessage());
                    }
                }
                return $this->formatHttpResponseError($response, 'deleteTemplate');
            }

            try {
                Template::where('fbid', $templateId)->delete();
            } catch (\Exception $dbEx) {
                Log::error('Error deleting template from database: ' . $dbEx->getMessage());
            }

            return [
                'status' => 'success',
                'message' => "Template {$templateName} deleted successfully"
            ];
        } catch (\Exception $e) {
            return $this->formatExceptionAsError($e, 'deleteTemplate');
        }
    }

    public function updateTemplate(string $templateId, array $data)
    {
        $endpoint = $this->baseUrl . $templateId;

        $templateData = [];
        if (isset($data['category'])) {
            $templateData['category'] = $data['category'];
        }
        if (isset($data['parameter_format'])) {
            $templateData['parameter_format'] = $data['parameter_format'];
        }
        if (isset($data['message_send_ttl_seconds'])) {
            $templateData['message_send_ttl_seconds'] = $data['message_send_ttl_seconds'];
        }
        if (isset($data['components'])) {
            $templateData['components'] = $data['components'];
        }

        try {
            $response = Http::timeout(30)
                ->withToken($this->accessToken)
                ->put($endpoint, $templateData);

            if ($response->failed()) {
                return $this->formatHttpResponseError($response, 'updateTemplate');
            }

            $responseData = $response->json();

            try {
                $template = Template::where('fbid', $templateId)->first();
                if ($template) {
                    $updateData = [];
                    if (isset($data['category'])) {
                        $updateData['category'] = $data['category'];
                    }
                    if (isset($data['components'])) {
                        $updateData['components'] = $data['components'];
                    }
                    if (isset($data['parameter_format'])) {
                        $updateData['parameter_format'] = $data['parameter_format'];
                    }
                    $updateData['status'] = 'IN REVIEW';
                    $template->update($updateData);
                }
            } catch (\Exception $e) {
                Log::error('Error updating template in database: ' . $e->getMessage(), [
                    'template_id' => $templateId
                ]);
            }

            return $responseData;
        } catch (\Exception $e) {
            return $this->formatExceptionAsError($e, 'updateTemplate');
        }
    }

    public function sendMediaMessage(string $to, string $mediaType, string $mediaUrl, ?string $caption = null)
    {
        $endpoint = $this->baseUrl . $this->phoneNumberId . '/messages';

        $mediaUrl = filter_var($mediaUrl, FILTER_VALIDATE_URL) ? $mediaUrl : env('APP_URL') . '/' . ltrim($mediaUrl, '/');

        $data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => $mediaType,
            $mediaType => [
                'link' => $mediaUrl,
            ]
        ];

        if ($caption && in_array($mediaType, ['image', 'video', 'document'])) {
            $data[$mediaType]['caption'] = $caption;
        }

        Log::info('Sending media message', [
            'to' => $to,
            'mediaType' => $mediaType,
            'mediaUrl' => $mediaUrl,
            'hasCaption' => !empty($caption)
        ]);
        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(30)
                ->post($endpoint, $data);

            $responseData = $response->json();

            if ($response->successful()) {
                Log::info('Media message sent successfully', [
                    'to' => $to,
                    'mediaType' => $mediaType,
                    'messageId' => $responseData['messages'][0]['id'] ?? 'unknown'
                ]);
            } else {
                Log::error('Error response from WhatsApp API when sending media', [
                    'status' => $response->status(),
                    'error' => $responseData['error'] ?? 'Unknown error',
                    'mediaType' => $mediaType,
                    'mediaUrl' => $mediaUrl
                ]);
            }

            return $responseData;
        } catch (\Exception $e) {
            Log::error('Exception sending media message: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'mediaType' => $mediaType,
                'mediaUrl' => $mediaUrl
            ]);

            return [
                'error' => [
                    'code' => 'MEDIA_EXCEPTION',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }
    public function submitTemplate(string $templateId, string $templateName)
    {
        try {
            $template = Template::where('fbid', $templateId)
                ->orWhere('name', $templateName)
                ->first();

            if (!$template) {
                return [
                    'error' => [
                        'code' => 'TEMPLATE_NOT_FOUND',
                        'message' => 'Template not found in database'
                    ]
                ];
            }

            if (empty($template->components)) {
                return [
                    'error' => [
                        'code' => 'INCOMPLETE_TEMPLATE',
                        'message' => 'Template must have components before submitting to WhatsApp'
                    ]
                ];
            }

            if (empty($template->fbid)) {
                $submissionData = [
                    'name' => $template->name,
                    'category' => $template->category,
                    'language' => $template->language,
                    'components' => $template->components,
                    'parameter_format' => $template->parameter_format
                ];

                $whatsappResult = $this->submitTemplateToWhatsApp($template->id, $submissionData);

                if (isset($whatsappResult['error'])) {
                    return $whatsappResult;
                }

                return [
                    'template_id' => $template->id,
                    'fbid' => $whatsappResult['id'] ?? null,
                    'name' => $template->name,
                    'status' => $template->fresh()->status,
                    'api_status' => $template->fresh()->api_status
                ];
            } else {
                $template->status = 'IN REVIEW';
                $template->api_status = 'PENDING';
                $template->save();

                Log::info('Template resubmitted for review', [
                    'template_id' => $template->id,
                    'template_name' => $templateName,
                    'fbid' => $template->fbid
                ]);

                return [
                    'template_id' => $template->id,
                    'fbid' => $template->fbid,
                    'name' => $template->name,
                    'status' => $template->status,
                    'api_status' => $template->api_status
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error submitting template: ' . $e->getMessage(), [
                'template_id' => $templateId,
                'template_name' => $templateName,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'error' => [
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    public function submitTemplateToWhatsApp(string $templateId, array $data)
    {
        $endpoint = $this->baseUrl . $this->businessAccountId . '/message_templates';

        $templateData = [
            'name' => $data['name'],
            'category' => $data['category'],
            'language' => $data['language'],
            'components' => $data['components']
        ];

        if (isset($data['parameter_format'])) {
            $templateData['parameter_format'] = $data['parameter_format'];
        }

        try {
            $response = Http::timeout(30)
                ->withToken($this->accessToken)
                ->post($endpoint, $templateData);

            if ($response->failed()) {
                return $this->formatHttpResponseError($response, 'submitTemplateToWhatsApp');
            }

            $responseData = $response->json();

            if (isset($responseData['id'])) {
                try {
                    $template = Template::find($templateId);
                    if ($template) {
                        $template->update([
                            'fbid' => $responseData['id'],
                            'status' => Template::API_TO_LOCAL_STATUS[$responseData['status']] ?? 'IN REVIEW',
                            'api_status' => $responseData['status']
                        ]);

                        Log::info('Template submitted to WhatsApp and updated in database', [
                            'template_id' => $template->id,
                            'fb_template_id' => $responseData['id'],
                            'status' => $template->status
                        ]);
                    }
                } catch (\Exception $dbEx) {
                    Log::error('Failed to update template record after WhatsApp submission: ' . $dbEx->getMessage());
                }
            }

            return $responseData;
        } catch (\Exception $e) {
            Log::error('Error submitting template to WhatsApp: ' . $e->getMessage(), [
                'template_id' => $templateId,
                'template_name' => $data['name'],
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'error' => [
                    'code' => 'WHATSAPP_API_ERROR',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }
}
