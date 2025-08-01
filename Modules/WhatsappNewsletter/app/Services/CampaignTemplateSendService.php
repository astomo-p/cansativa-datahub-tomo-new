<?php

namespace Modules\WhatsappNewsletter\Services;

use Illuminate\Support\Facades\Log;
use Modules\WhatsappNewsletter\Models\WaNewsLetter;
use Modules\Whatsapp\Models\Template;
use Modules\NewContactData\Models\Contacts;
use Modules\NewContactData\Models\B2BContacts;
use Modules\WhatsappNewsletter\Helpers\FilterQueryHelper;
use Modules\Whatsapp\Services\WhatsappAPIService;
use Modules\WhatsappNewsletter\Services\CampaignTrackingService;
use Illuminate\Support\Facades\Queue;
use Modules\AuditLog\Events\ContactLogged;

class CampaignTemplateSendService
{
    public function sendTemplateToFilteredContacts(int $newsletterId): array
    {
        $newsletter = WaNewsLetter::find($newsletterId);

        if (!$newsletter) {
            return ['status' => 'error', 'message' => 'Newsletter not found'];
        }

        $templateId = $newsletter->wa_template_id;
        if (!$templateId) {
            return ['status' => 'error', 'message' => 'Template ID not set in newsletter'];
        }

        $template = Template::find($templateId);
        if (!$template) {
            return ['status' => 'error', 'message' => 'Template not found'];
        }

        $filters = $newsletter->filters;
        $contactTypeId = $newsletter->contact_type_id;

        if (!$contactTypeId) {
            return ['status' => 'error', 'message' => 'Contact type not set in newsletter'];
        }

        if (is_string($filters)) {
            $filters = json_decode($filters, true);
        }

        if (!is_array($filters)) {
            $filters = [];
        }

        $query = Contacts::query();
        $query = FilterQueryHelper::applyFilters($query, $filters, $contactTypeId);
        $contacts = $query->whereNotNull('phone_no')->get();
        Log::info($contacts);

        if ($contacts->isEmpty()) {
            return ['status' => 'error', 'message' => 'No contacts found after filtering'];
        }

        $waApi = new WhatsappAPIService();
        $trackingService = new CampaignTrackingService();
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($contacts as $contact) {
            try {
                $result = $waApi->sendNewsLetterTemplate($templateId, $contact, $newsletter->contact_flag ?? null);

                if (isset($result['messages']) && !empty($result['messages'])) {
                    $messageId = $result['messages'][0]['id'] ?? null;

                    if ($messageId) {
                        $trackingService->trackMessageSent(
                            $newsletterId,
                            $contact->id,
                            $newsletter->contact_flag ?? 'b2c',
                            $messageId
                        );

                        $successCount++;
                        $results[$contact->id] = [
                            'status' => 'success',
                            'message_id' => $messageId,
                            'result' => $result
                        ];

                        Log::info("Message sent and tracked for campaign {$newsletterId}", [
                            'contact_id' => $contact->id,
                            'message_id' => $messageId,
                            'contact_flag' => $newsletter->contact_flag
                        ]);
                    } else {
                        $errorCount++;
                        $results[$contact->id] = [
                            'status' => 'error',
                            'message' => 'No message ID returned',
                            'result' => $result
                        ];
                    }
                } else {
                    $errorCount++;
                    $results[$contact->id] = [
                        'status' => 'error',
                        'message' => 'Failed to send message',
                        'result' => $result
                    ];
                }
            } catch (\Exception $e) {
                $errorCount++;
                $results[$contact->id] = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];

                Log::error("Error sending template to contact for campaign {$newsletterId}", [
                    'contact_id' => $contact->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'status' => 'success',
            'total_contacts' => count($contacts),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'sent_count' => $successCount,
            'results' => $results
        ];
    }

    public function processAllScheduledNewsletters($now = null): array
    {
        $results = [];
        $scheduled = WaNewsLetter::getScheduledForSending($now);
        foreach ($scheduled as $newsletter) {
            $results[$newsletter->id] = $this->sendTemplateToFilteredContacts($newsletter->id);
        }
        return $results;
    }

    public function sendTemplateToContactIds(int $newsletterId): array
    {
        $newsletter = WaNewsLetter::find($newsletterId);

        if (!$newsletter) {
            return ['status' => 'error', 'message' => 'Newsletter not found'];
        }

        $templateId = $newsletter->wa_template_id;
        if (!$templateId) {
            return ['status' => 'error', 'message' => 'Template ID not set in newsletter'];
        }

        $template = Template::find($templateId);
        if (!$template) {
            return ['status' => 'error', 'message' => 'Template not found'];
        }

        $contactIds = $newsletter->contact_ids;
        if (empty($contactIds)) {
            return ['status' => 'error', 'message' => 'No contact IDs provided'];
        }

        if (is_string($contactIds)) {
            $contactIds = json_decode($contactIds, true);
        }

        if (!is_array($contactIds)) {
            return ['status' => 'error', 'message' => 'Invalid contact IDs format'];
        }

        $contactFlag = $newsletter->contact_flag ?? null;

        if ($contactFlag === 'b2b') {
            $contacts = B2BContacts::on('pgsql_b2b')
                ->whereIn('id', $contactIds)
                ->whereNotNull('phone_no')
                ->get();
        } else {
            $contacts = Contacts::whereIn('id', $contactIds)
                ->whereNotNull('phone_no')
                ->get();
        }

        if ($contacts->isEmpty()) {
            return ['status' => 'error', 'message' => 'No contacts found with provided IDs'];
        }

        $waApi = new WhatsappAPIService();
        $trackingService = new CampaignTrackingService();
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($contacts as $contact) {
            try {
                $result = $waApi->sendNewsLetterTemplate($templateId, $contact, $newsletter->contact_flag ?? null);

                if (isset($result['messages']) && !empty($result['messages'])) {
                    $messageId = $result['messages'][0]['id'] ?? null;

                    if ($messageId) {
                        $description = [
                            'title' => "WhatsApp campaign sent",
                            'detail' => [
                                'title' => "(#{$newsletter->id}) {$newsletter->name}",
                                'image_url' => $template->template_image_url ?? '',
                                'view_report_url' => "",
                                'view_whatsapp_message' => "",
                                'template' => "Template used: {$template->name}",
                                'from' => ""
                            ]
                        ];

                        $createdBy = $newsletter->created_by ?? null;
                        $creatorUser = null;
                        $creatorName = 'System';
                        $creatorEmail = 'system@example.com';

                        if ($createdBy) {
                            $creatorUser = \Modules\User\Models\User::find($createdBy);
                            if ($creatorUser) {
                                $creatorName = $creatorUser->user_name ?? 'System';
                                $creatorEmail = $creatorUser->email ?? 'system@example.com';
                            }
                        }

                        event(new ContactLogged(
                            'wa_campaign',
                            $newsletter->contact_flag ?? 'b2b',
                            $contact->id,
                            $newsletterId,
                            $description,
                            $creatorName,
                            $creatorEmail
                        ));

                        $trackingService->trackMessageSent(
                            $newsletterId,
                            $contact->id,
                            $newsletter->contact_flag ?? 'b2c',
                            $messageId
                        );

                        $successCount++;
                        $results[$contact->id] = [
                            'status' => 'success',
                            'message_id' => $messageId,
                            'result' => $result
                        ];

                        Log::info("Message sent and tracked for campaign {$newsletterId}", [
                            'contact_id' => $contact->id,
                            'message_id' => $messageId,
                            'contact_flag' => $newsletter->contact_flag
                        ]);
                    } else {
                        $errorCount++;
                        $results[$contact->id] = [
                            'status' => 'error',
                            'message' => 'No message ID returned',
                            'result' => $result
                        ];
                    }
                } else {
                    $errorCount++;
                    $results[$contact->id] = [
                        'status' => 'error',
                        'message' => 'Failed to send message',
                        'result' => $result
                    ];
                }
            } catch (\Exception $e) {
                $errorCount++;
                $results[$contact->id] = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];

                Log::error("Error sending template to contact for campaign {$newsletterId}", [
                    'contact_id' => $contact->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'status' => 'success',
            'total_contacts' => count($contacts),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'sent_count' => $successCount,
            'results' => $results
        ];
    }

    public function sendTemplateToBatchContacts(int $newsletterId, array $contactIds): array
    {
        $newsletter = WaNewsLetter::find($newsletterId);

        if (!$newsletter) {
            return ['status' => 'error', 'message' => 'Newsletter not found'];
        }

        $templateId = $newsletter->wa_template_id;
        if (!$templateId) {
            return ['status' => 'error', 'message' => 'Template ID not set in newsletter'];
        }

        $template = Template::find($templateId);
        if (!$template) {
            return ['status' => 'error', 'message' => 'Template not found'];
        }

        if (empty($contactIds)) {
            return ['status' => 'error', 'message' => 'No contact IDs provided'];
        }

        $contactFlag = $newsletter->contact_flag ?? null;

        if ($contactFlag === 'b2b') {
            $contacts = B2BContacts::on('pgsql_b2b')
                ->whereIn('id', $contactIds)
                ->whereNotNull('phone_no')
                ->get();
        } else {
            $contacts = Contacts::whereIn('id', $contactIds)
                ->whereNotNull('phone_no')
                ->get();
        }

        if ($contacts->isEmpty()) {
            return ['status' => 'error', 'message' => 'No contacts found with provided IDs'];
        }

        $waApi = new WhatsappAPIService();
        $trackingService = new CampaignTrackingService();
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($contacts as $contact) {
            try {
                $result = $waApi->sendNewsLetterTemplate($templateId, $contact, $newsletter->contact_flag ?? null);

                if (isset($result['messages']) && !empty($result['messages'])) {
                    $messageId = $result['messages'][0]['id'] ?? null;

                    if ($messageId) {
                        $description = [
                            'title' => "WhatsApp campaign sent",
                            'detail' => [
                                'campaign_id' => $newsletterId,
                                'template_id' => $templateId,
                                'message_id' => $messageId
                            ]
                        ];

                        $createdBy = $newsletter->created_by ?? null;
                        $creatorUser = null;
                        $creatorName = 'System';
                        $creatorEmail = 'system@example.com';

                        if ($createdBy) {
                            $creatorUser = \Modules\User\Models\User::find($createdBy);
                            if ($creatorUser) {
                                $creatorName = $creatorUser->name;
                                $creatorEmail = $creatorUser->email;
                            }
                        }

                        event(new ContactLogged(
                            'wa_campaign',
                            $newsletter->contact_flag ?? 'b2b',
                            $contact->id,
                            null,
                            $description,
                            $creatorName,
                            $creatorEmail
                        ));

                        $trackingService->trackMessageSent(
                            $newsletterId,
                            $contact->id,
                            $newsletter->contact_flag ?? 'b2c',
                            $messageId
                        );

                        $successCount++;
                        $results[$contact->id] = [
                            'status' => 'success',
                            'message_id' => $messageId,
                            'result' => $result
                        ];

                        Log::info("Batch message sent and tracked for campaign {$newsletterId}", [
                            'contact_id' => $contact->id,
                            'message_id' => $messageId,
                            'contact_flag' => $newsletter->contact_flag
                        ]);
                    } else {
                        $errorCount++;
                        $results[$contact->id] = [
                            'status' => 'error',
                            'message' => 'No message ID returned',
                            'result' => $result
                        ];
                    }
                } else {
                    $errorCount++;
                    $results[$contact->id] = [
                        'status' => 'error',
                        'message' => 'Failed to send message',
                        'result' => $result
                    ];
                }
            } catch (\Exception $e) {
                $errorCount++;
                $results[$contact->id] = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];

                Log::error("Error sending batch template to contact for campaign {$newsletterId}", [
                    'contact_id' => $contact->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'status' => 'success',
            'total_contacts' => count($contacts),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'sent_count' => $successCount,
            'results' => $results
        ];
    }
}
