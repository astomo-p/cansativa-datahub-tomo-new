<?php

namespace Modules\Whatsapp\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\NewContactData\Models\Contacts;
use Modules\Whatsapp\Models\Message;
use Modules\Whatsapp\Models\Template;
use Modules\Whatsapp\Traits\WhatsappServiceErrorHandler;

class WhatsappWebhookService
{
    use WhatsappServiceErrorHandler;
    public function verifyWebhook(string $mode, string $token, string $challenge)
    {
        $verifyToken = config('whatsapp.webhook_verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return $challenge;
        }

        throw new \Exception('Invalid webhook verification token');
    }

    public function handleWebhookEvent(array $payload)
    {
        try {
            $allResponses = [];

            if (isset($payload['entry'][0]['changes'][0])) {
                $change = $payload['entry'][0]['changes'][0];
                if ($change['field'] === 'message_template_status_update' && isset($change['value'])) {
                    return $this->processTemplateStatusUpdate($change['value']);
                }
            }

            $data = $payload['payload'] ?? $payload;

            if (isset($payload['field'])) {
                switch ($payload['field']) {
                    case 'messages':
                        if (isset($data['messages']) && !empty($data['messages'])) {
                            foreach ($data['messages'] as $message) {
                                $responses[] = $this->processIncomingMessage($message);
                            }
                        }
                        return !empty($responses) ? $responses : ['status' => 'success', 'message' => 'No messages to process'];
                }
            }

            if (isset($payload['entry'][0]['changes'][0]['value'])) {
                $data = $payload['entry'][0]['changes'][0]['value'];
                Log::info('Legacy webhook received:', ['payload' => $data]);

                $responses = ['messages' => [], 'statuses' => []];

                if (isset($data['messages'])) {
                    foreach ($data['messages'] as $message) {
                        $messageResponse = $this->processIncomingMessage($message);
                        if (isset($messageResponse)) {
                            $responses['messages'][] = $messageResponse;
                        }
                    }
                }

                if (isset($data['statuses'])) {
                    foreach ($data['statuses'] as $status) {
                        $statusResponse = $this->processStatusUpdate($status);
                        if (isset($statusResponse)) {
                            $responses['statuses'][] = $statusResponse;
                        }
                    }
                }

                return [
                    'status' => 'success',
                    'messages_processed' => count($responses['messages']),
                    'statuses_processed' => count($responses['statuses']),
                    'details' => $responses
                ];
            }

            return ['status' => 'success', 'message' => 'No event data to process'];
        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'payload' => $payload
            ]);

            return [
                'error' => [
                    'code' => 'WEBHOOK_ERROR',
                    'message' => $e->getMessage()
                ],
                'status' => 'processed_with_errors',
                'payload_received' => true
            ];
        }
    }

    private function processTemplateStatusUpdate(array $value): array
    {
        $event = $value['event'] ?? null;
        $templateId = $value['message_template_id'] ?? null;
        $templateName = $value['message_template_name'] ?? null;
        $language = $value['message_template_language'] ?? null;
        $reason = $value['reason'] ?? 'NONE';

        if (!$event || !$templateId || !$templateName) {
            Log::error('Invalid template status update payload', ['value' => $value]);
            return [
                'status' => 'error',
                'message' => 'Invalid template status update payload'
            ];
        }

        try {
            $template = Template::where('fbid', $templateId)->first();

            if (!$template) {
                Log::warning('Template not found in database', [
                    'fbid' => $templateId,
                    'name' => $templateName
                ]);

                return [
                    'status' => 'error',
                    'message' => 'Template not found in database',
                    'data' => [
                        'template_id' => $templateId,
                        'template_name' => $templateName
                    ]
                ];
            }

            $apiToLocalStatus = Template::API_TO_LOCAL_STATUS;
            $localStatus = $apiToLocalStatus[$event] ?? $event;

            $template->updateStatus($localStatus, $reason);
            $template->api_status = $event;
            $template->save();

            Log::info('Template status updated in database', [
                'name' => $templateName,
                'id' => $templateId,
                'language' => $language,
                'reason' => $reason,
                'api_status' => $event,
                'status' => $localStatus
            ]);

            if ($event === 'PENDING_DELETION') {
                $template->delete();
                Log::info('Template deleted from database due to PENDING_DELETION status', [
                    'name' => $templateName,
                    'id' => $templateId,
                    'language' => $language
                ]);
            }

            return [
                'status' => 'success',
                'message' => 'Template status updated successfully',
                'data' => [
                    'event' => $event,
                    'template_id' => $templateId,
                    'template_name' => $templateName,
                    'language' => $language,
                    'reason' => $reason,
                    'new_status' => $template->status
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update template status', [
                'error' => $e->getMessage(),
                'template_id' => $templateId,
                'event' => $event
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to update template status: ' . $e->getMessage(),
                'data' => [
                    'template_id' => $templateId,
                    'template_name' => $templateName,
                    'event' => $event
                ]
            ];
        }
    }

    protected function processIncomingMessage(array $message, ?array $metadata = null)
    {
        try {
            $to = $message['from'];

            $contact = Contacts::firstOrCreate(
                ['phone_no' => $to]
            );

            if ($contact->wasRecentlyCreated) {
                $contact->status = 'hidden';
                $contact->created_date = now();
                $contact->created_by = Auth::id();
                $contact->save();
            }

            $messageData = [
                'wamid' => $message['id'],
                'contact_id' => $contact->id,
                'status' => 'accepted',
                'direction' => 0,
                'type' => $message['type'],
                'timestamp' => date('Y-m-d H:i:s', $message['timestamp'])
            ];

            switch ($message['type']) {
                case 'text':
                    $messageData['body'] = $message['text']['body'];
                    $messageData['body_text'] = $message['text']['body'];
                    break;

                case 'image':
                    $messageData['media_file'] = $message['image']['id'] ?? null;
                    $messageData['body'] = json_encode($message['image']);
                    break;

                case 'video':
                    $messageData['media_file'] = $message['video']['id'] ?? null;
                    $messageData['body'] = json_encode($message['video']);
                    break;

                case 'audio':
                    $messageData['media_file'] = $message['audio']['id'] ?? null;
                    $messageData['body'] = json_encode($message['audio']);
                    break;

                case 'document':
                    $messageData['media_file'] = $message['document']['id'] ?? null;
                    $messageData['body'] = json_encode($message['document']);
                    break;

                default:
                    $messageData['body'] = json_encode($message);
                    $messageData['body_text'] = 'Unsupported message type: ' . $message['type'];
            }

            try {
                if ($message['type'] === 'text' && !empty($message['text']['body'])) {
                    try {
                        $assignmentService = app(AssignmentService::class);
                        $assignment = $assignmentService->assignmentChecker($contact->id, $message['text']['body']);
                        $messageData['assignment_id'] = $assignment->id;
                        $messageData['language'] = $contact->message_language;
                    } catch (\Exception $assignmentException) {
                        Log::warning('Assignment process failed: ' . $assignmentException->getMessage(), [
                            'contact_id' => $contact->id,
                            'text' => $message['text']['body']
                        ]);
                    }
                }

                $newMessage = Message::create($messageData);
                $contact->updateLastMessageAt();
                Log::info('Incoming message saved to database:', $messageData);

                return [
                    'status' => 'success',
                    'message' => 'Message processed successfully',
                    'wamid' => $message['id'],
                    'message_id' => $newMessage->id
                ];
            } catch (\Exception $dbException) {
                $messageData['status'] = 'failed';
                $messageData['error_code'] = 'DB_ERROR';
                $messageData['error_message'] = substr($dbException->getMessage(), 0, 500);

                $newMessage = Message::create($messageData);
                Log::error('Error saving message, retried with error details: ' . $dbException->getMessage());

                return [
                    'error' => [
                        'code' => 'DB_ERROR',
                        'message' => $dbException->getMessage()
                    ],
                    'status' => 'failed',
                    'wamid' => $message['id'],
                    'message_id' => $newMessage->id
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error processing incoming message: ' . $e->getMessage(), [
                'message_data' => $message
            ]);

            try {
                $errorMessageData = [
                    'wamid' => $message['id'] ?? 'unknown_' . time(),
                    'contact_id' => isset($contact) ? $contact->id : null,
                    'status' => 'failed',
                    'direction' => 0,
                    'type' => $message['type'] ?? 'unknown',
                    'error_code' => 'PROCESSING_ERROR',
                    'error_message' => substr($e->getMessage(), 0, 500),
                    'body' => json_encode($message),
                    'body_text' => 'Failed to process message: ' . $e->getMessage()
                ];

                $errorMessage = Message::create($errorMessageData);
                Log::info('Failed message saved with error details', $errorMessageData);

                return [
                    'error' => [
                        'code' => 'PROCESSING_ERROR',
                        'message' => $e->getMessage()
                    ],
                    'status' => 'failed',
                    'wamid' => $message['id'] ?? 'unknown_' . time(),
                    'message_id' => $errorMessage->id
                ];
            } catch (\Exception $saveException) {
                Log::critical('Could not save failed message: ' . $saveException->getMessage(), [
                    'original_error' => $e->getMessage(),
                    'message_data' => $message
                ]);

                return [
                    'error' => [
                        'code' => 'CRITICAL_ERROR',
                        'message' => 'Failed to process and save message: ' . $e->getMessage() .
                            ' Additionally failed to save error: ' . $saveException->getMessage()
                    ],
                    'status' => 'failed',
                    'wamid' => $message['id'] ?? 'unknown_' . time()
                ];
            }
        }
    }

    protected function processContact(array $contact)
    {
        try {
            $contactData = [
                'wa_id' => $contact['wa_id'],
                'name' => $contact['profile']['name'] ?? null
            ];

            Log::info('Processing contact:', $contactData);

            try {
                $dbContact = Contacts::firstOrCreate(
                    ['phone_no' => $contact['wa_id']]
                );

                if ($dbContact->wasRecentlyCreated) {
                    $dbContact->status = 'hidden';
                    $dbContact->created_at = now();
                    $dbContact->created_by = 1;
                    $dbContact->save();
                }

                return [
                    'status' => 'success',
                    'message' => 'Contact processed successfully',
                    'contact_id' => $dbContact->id
                ];
            } catch (\Exception $dbEx) {
                Log::error('Error saving contact to database: ' . $dbEx->getMessage(), $contactData);

                return [
                    'error' => [
                        'code' => 'DB_ERROR',
                        'message' => $dbEx->getMessage()
                    ],
                    'status' => 'failed',
                    'wa_id' => $contact['wa_id']
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error processing contact: ' . $e->getMessage(), [
                'contact_data' => $contact
            ]);

            return [
                'error' => [
                    'code' => 'PROCESSING_ERROR',
                    'message' => $e->getMessage()
                ],
                'status' => 'failed',
                'wa_id' => $contact['wa_id'] ?? 'unknown'
            ];
        }
    }

    protected function processStatusUpdate(array $status)
    {
        try {
            $message = Message::where('wamid', $status['id'])->first();

            if ($message) {
                $updateData = ['status' => strtolower($status['status'])];

                if (isset($status['errors']) && !empty($status['errors'])) {
                    $error = $status['errors'][0];
                    $updateData['error_code'] = $error['code'];
                    $updateData['error_message'] = $error['title'];

                    $errorResponse = [
                        'error' => [
                            'code' => $error['code'],
                            'message' => $error['title']
                        ],
                        'status' => 'failed',
                        'wamid' => $status['id']
                    ];
                }

                $message->update($updateData);

                Log::info('Message status updated:', [
                    'wamid' => $status['id'],
                    'new_status' => $status['status']
                ]);

                return isset($errorResponse) ? $errorResponse : ['status' => 'success', 'wamid' => $status['id']];
            } else {
                Log::warning('Message not found for status update:', [
                    'wamid' => $status['id'],
                    'status' => $status['status']
                ]);

                try {
                    Message::create([
                        'wamid' => $status['id'],
                        'contact_id' => 1,
                        'status' => strtolower($status['status']),
                        'direction' => 1,
                        'type' => 'unknown',
                        'body' => json_encode($status),
                        'body_text' => 'Status update for unknown message'
                    ]);

                    return $errorResponse ?? ['status' => 'success', 'wamid' => $status['id'], 'message' => 'Status update processed for unknown message'];
                } catch (\Exception $createEx) {
                    Log::error('Failed to create status update message: ' . $createEx->getMessage());
                    return [
                        'error' => [
                            'code' => 'DB_ERROR',
                            'message' => $createEx->getMessage()
                        ],
                        'status' => 'failed',
                        'wamid' => $status['id']
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing status update: ' . $e->getMessage(), [
                'status_data' => $status
            ]);

            return [
                'error' => [
                    'code' => 'STATUS_UPDATE_ERROR',
                    'message' => $e->getMessage()
                ],
                'status' => 'failed',
                'wamid' => $status['id'] ?? 'unknown'
            ];
        }
    }
}
