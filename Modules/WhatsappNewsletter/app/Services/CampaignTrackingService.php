<?php

namespace Modules\WhatsappNewsletter\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\WhatsappNewsletter\Models\WaNewsLetter;
use Modules\WhatsappNewsletter\Models\WaNewsLetterStats;
use Modules\WhatsappNewsletter\Models\WaCampaignMessageTracking;
use Modules\WhatsappNewsletter\Models\WaCampaignInteractions;

class CampaignTrackingService
{
    public function initializeCampaignTracking(int $campaignId): WaNewsLetterStats
    {
        return WaNewsLetterStats::updateOrCreate(
            ['campaign_id' => $campaignId],
            [
                'campaign_status' => 'sending',
                'started_at' => now(),
            ]
        );
    }

    public function trackMessageSent(int $campaignId, int $contactId, string $contactFlag, string $messageId): void
    {
        try {
            DB::connection('pgsql_b2b_shared')->transaction(function () use ($campaignId, $contactId, $contactFlag, $messageId) {
                $existingTracking = WaCampaignMessageTracking::where('message_id', $messageId)->first();

                if ($existingTracking) {
                    Log::info('Message already tracked, skipping', [
                        'campaign_id' => $campaignId,
                        'contact_id' => $contactId,
                        'message_id' => $messageId
                    ]);
                    return;
                }

                WaCampaignMessageTracking::create([
                    'campaign_id' => $campaignId,
                    'contact_id' => $contactId,
                    'contact_flag' => $contactFlag,
                    'message_id' => $messageId,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                $stats = WaNewsLetterStats::where('campaign_id', $campaignId)->first();
                if ($stats) {
                    $stats->increment('total_sent');
                }
            });

            Log::info('Message sent tracked', [
                'campaign_id' => $campaignId,
                'contact_id' => $contactId,
                'contact_flag' => $contactFlag,
                'message_id' => $messageId
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track message sent: ' . $e->getMessage(), [
                'campaign_id' => $campaignId,
                'contact_id' => $contactId,
                'contact_flag' => $contactFlag,
                'message_id' => $messageId
            ]);
        }
    }

    public function trackMessageStatus(string $messageId, string $status): void
    {
        try {
            DB::connection('pgsql_b2b_shared')->transaction(function () use ($messageId, $status) {
                $tracking = WaCampaignMessageTracking::where('message_id', $messageId)->first();

                if (!$tracking) {
                    Log::warning('Message tracking not found for status update', [
                        'message_id' => $messageId,
                        'status' => $status
                    ]);
                    return;
                }

                $previousStatus = $tracking->status;

                if ($previousStatus === $status) {
                    Log::info('Status already set, skipping update', [
                        'message_id' => $messageId,
                        'status' => $status,
                        'previous_status' => $previousStatus
                    ]);
                    return;
                }

                if (!$this->isValidStatusTransition($previousStatus, $status)) {
                    Log::warning('Invalid status transition, skipping update', [
                        'message_id' => $messageId,
                        'previous_status' => $previousStatus,
                        'new_status' => $status
                    ]);
                    return;
                }

                $updateData = ['status' => $status];
                $statusField = $status . '_at';

                if (in_array($statusField, ['delivered_at', 'read_at', 'failed_at'])) {
                    $updateData[$statusField] = now();
                }

                $tracking->update($updateData);

                $this->updateCampaignStats($tracking->campaign_id, $status);
            });

            Log::info('Message status updated', [
                'message_id' => $messageId,
                'status' => $status
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track message status: ' . $e->getMessage(), [
                'message_id' => $messageId,
                'status' => $status
            ]);
        }
    }

    private function isValidStatusTransition(string $currentStatus, string $newStatus): bool
    {
        $statusHierarchy = [
            'sent' => 0,
            'delivered' => 1,
            'read' => 2,
            'failed' => 3
        ];

        $currentLevel = $statusHierarchy[$currentStatus] ?? -1;
        $newLevel = $statusHierarchy[$newStatus] ?? -1;

        if ($currentLevel === -1 || $newLevel === -1) {
            return false;
        }

        if ($currentStatus === 'failed' && $newStatus !== 'failed') {
            return false;
        }

        return $newLevel > $currentLevel || ($currentStatus === 'sent' && $newStatus === 'failed');
    }

    private function updateCampaignStats(int $campaignId, string $status): void
    {
        $stats = WaNewsLetterStats::where('campaign_id', $campaignId)->first();

        if (!$stats) {
            return;
        }

        switch ($status) {
            case 'delivered':
                $stats->increment('total_delivered');
                break;
            case 'read':
                $stats->increment('total_opened');
                break;
            case 'failed':
                $stats->increment('total_failed');
                break;
        }
    }

    public function trackInteraction(int $campaignId, int $contactId, string $contactFlag, string $interactionType, array $data = []): void
    {
        try {
            DB::connection('pgsql_b2b_shared')->transaction(function () use ($campaignId, $contactId, $contactFlag, $interactionType, $data) {
                $existingInteraction = WaCampaignInteractions::where([
                    'campaign_id' => $campaignId,
                    'contact_id' => $contactId,
                    'contact_flag' => $contactFlag,
                    'interaction_type' => $interactionType,
                    'button_text' => $data['button_text'] ?? null,
                ])->first();

                if ($existingInteraction) {
                    Log::info('Interaction already tracked, skipping', [
                        'campaign_id' => $campaignId,
                        'contact_id' => $contactId,
                        'interaction_type' => $interactionType,
                        'button_text' => $data['button_text'] ?? null
                    ]);
                    return;
                }

                WaCampaignInteractions::create([
                    'campaign_id' => $campaignId,
                    'contact_id' => $contactId,
                    'contact_flag' => $contactFlag,
                    'interaction_type' => $interactionType,
                    'button_text' => $data['button_text'] ?? null,
                    'clicked_url' => $data['clicked_url'] ?? null,
                    'interaction_data' => !empty($data) ? $data : null,
                ]);

                $stats = WaNewsLetterStats::where('campaign_id', $campaignId)->first();
                if ($stats) {
                    switch ($interactionType) {
                        case 'click':
                            $stats->increment('total_clicks');
                            break;
                        case 'unsubscribe':
                            $stats->increment('total_unsubscribed');
                            break;
                    }
                }
            });

            Log::info('Interaction tracked', [
                'campaign_id' => $campaignId,
                'contact_id' => $contactId,
                'contact_flag' => $contactFlag,
                'interaction_type' => $interactionType,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track interaction: ' . $e->getMessage(), [
                'campaign_id' => $campaignId,
                'contact_id' => $contactId,
                'contact_flag' => $contactFlag,
                'interaction_type' => $interactionType
            ]);
        }
    }

    public function completeCampaignTracking(int $campaignId): void
    {
        try {
            $stats = WaNewsLetterStats::where('campaign_id', $campaignId)->first();
            if ($stats) {
                $stats->update([
                    'campaign_status' => 'completed',
                    'completed_at' => now(),
                ]);
            }

            Log::info('Campaign tracking completed', ['campaign_id' => $campaignId]);
        } catch (\Exception $e) {
            Log::error('Failed to complete campaign tracking: ' . $e->getMessage(), [
                'campaign_id' => $campaignId
            ]);
        }
    }

    public function completeCampaign(int $campaignId): void
    {
        $this->completeCampaignTracking($campaignId);
    }

    public function getCampaignOverview(array $filters = []): array
    {
        $query = WaNewsLetterStats::query();

        if (!empty($filters['date_from'])) {
            $query->where('started_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('started_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['status'])) {
            $query->where('campaign_status', $filters['status']);
        }
        if (!empty($filters['contact_flag'])) {
            $query->whereHas('campaign', function ($q) use ($filters) {
                $q->where('contact_flag', $filters['contact_flag']);
            });
        }

        $stats = $query->select([
            DB::raw('COUNT(CASE WHEN campaign_status = "completed" THEN 1 END) as campaigns_sent'),
            DB::raw('SUM(total_sent) as messages_sent'),
            DB::raw('SUM(total_delivered) as messages_delivered'),
            DB::raw('SUM(total_opened) as messages_opened'),
            DB::raw('SUM(total_clicks) as link_clicks'),
            DB::raw('SUM(total_unsubscribed) as total_unsubscribes'),
            DB::raw('SUM(total_failed) as messages_failed'),
            DB::raw('CASE WHEN SUM(total_sent) > 0 THEN ROUND((SUM(total_delivered) / SUM(total_sent)) * 100, 2) ELSE 0 END as delivery_rate'),
            DB::raw('CASE WHEN SUM(total_delivered) > 0 THEN ROUND((SUM(total_opened) / SUM(total_delivered)) * 100, 2) ELSE 0 END as open_rate'),
            DB::raw('CASE WHEN SUM(total_delivered) > 0 THEN ROUND((SUM(total_clicks) / SUM(total_delivered)) * 100, 2) ELSE 0 END as click_rate'),
            DB::raw('CASE WHEN SUM(total_sent) > 0 THEN ROUND((SUM(total_unsubscribed) / SUM(total_sent)) * 100, 2) ELSE 0 END as unsubscribe_rate'),
        ])->first();

        return [
            'campaigns_sent' => $stats->campaigns_sent ?? 0,
            'messages_sent' => $stats->messages_sent ?? 0,
            'messages_delivered' => $stats->messages_delivered ?? 0,
            'messages_opened' => $stats->messages_opened ?? 0,
            'link_clicks' => $stats->link_clicks ?? 0,
            'total_unsubscribes' => $stats->total_unsubscribes ?? 0,
            'messages_failed' => $stats->messages_failed ?? 0,
            'delivery_rate' => $stats->delivery_rate ?? 0,
            'open_rate' => $stats->open_rate ?? 0,
            'click_rate' => $stats->click_rate ?? 0,
            'unsubscribe_rate' => $stats->unsubscribe_rate ?? 0,
        ];
    }

    public function getCampaignOverviewByContactFlag(array $filters = []): array
    {
        $b2bFilters = array_merge($filters, ['contact_flag' => 'b2b']);
        $b2cFilters = array_merge($filters, ['contact_flag' => 'b2c']);

        return [
            'b2b' => $this->getCampaignOverview($b2bFilters),
            'b2c' => $this->getCampaignOverview($b2cFilters),
        ];
    }

    public function getCampaignDetails(int $campaignId): array
    {
        $messageQuery = WaCampaignMessageTracking::where('campaign_id', $campaignId);
        $interactionQuery = WaCampaignInteractions::where('campaign_id', $campaignId);

        $messageStats = $messageQuery->selectRaw('
            COUNT(*) as total_sent,
            COUNT(CASE WHEN status = "delivered" THEN 1 END) as total_delivered,
            COUNT(CASE WHEN status = "read" THEN 1 END) as total_opened,
            COUNT(CASE WHEN status = "failed" THEN 1 END) as total_failed,
            COUNT(CASE WHEN contact_flag = "b2b" THEN 1 END) as sent_b2b,
            COUNT(CASE WHEN contact_flag = "b2c" THEN 1 END) as sent_b2c,
            COUNT(CASE WHEN status = "delivered" AND contact_flag = "b2b" THEN 1 END) as delivered_b2b,
            COUNT(CASE WHEN status = "delivered" AND contact_flag = "b2c" THEN 1 END) as delivered_b2c,
            COUNT(CASE WHEN status = "read" AND contact_flag = "b2b" THEN 1 END) as opened_b2b,
            COUNT(CASE WHEN status = "read" AND contact_flag = "b2c" THEN 1 END) as opened_b2c,
            COUNT(CASE WHEN status = "failed" AND contact_flag = "b2b" THEN 1 END) as failed_b2b,
            COUNT(CASE WHEN status = "failed" AND contact_flag = "b2c" THEN 1 END) as failed_b2c
        ')->first();

        $interactionStats = $interactionQuery->selectRaw('
            COUNT(CASE WHEN interaction_type = "click" THEN 1 END) as total_clicks,
            COUNT(CASE WHEN interaction_type = "unsubscribe" THEN 1 END) as total_unsubscribes,
            COUNT(CASE WHEN interaction_type = "click" AND contact_flag = "b2b" THEN 1 END) as clicks_b2b,
            COUNT(CASE WHEN interaction_type = "click" AND contact_flag = "b2c" THEN 1 END) as clicks_b2c,
            COUNT(CASE WHEN interaction_type = "unsubscribe" AND contact_flag = "b2b" THEN 1 END) as unsubscribes_b2b,
            COUNT(CASE WHEN interaction_type = "unsubscribe" AND contact_flag = "b2c" THEN 1 END) as unsubscribes_b2c
        ')->first();

        return [
            'message_stats' => [
                'total_sent' => $messageStats->total_sent ?? 0,
                'total_delivered' => $messageStats->total_delivered ?? 0,
                'total_opened' => $messageStats->total_opened ?? 0,
                'total_failed' => $messageStats->total_failed ?? 0,
                'sent_b2b' => $messageStats->sent_b2b ?? 0,
                'sent_b2c' => $messageStats->sent_b2c ?? 0,
                'delivered_b2b' => $messageStats->delivered_b2b ?? 0,
                'delivered_b2c' => $messageStats->delivered_b2c ?? 0,
                'opened_b2b' => $messageStats->opened_b2b ?? 0,
                'opened_b2c' => $messageStats->opened_b2c ?? 0,
                'failed_b2b' => $messageStats->failed_b2b ?? 0,
                'failed_b2c' => $messageStats->failed_b2c ?? 0,
            ],
            'interaction_stats' => [
                'total_clicks' => $interactionStats->total_clicks ?? 0,
                'total_unsubscribes' => $interactionStats->total_unsubscribes ?? 0,
                'clicks_b2b' => $interactionStats->clicks_b2b ?? 0,
                'clicks_b2c' => $interactionStats->clicks_b2c ?? 0,
                'unsubscribes_b2b' => $interactionStats->unsubscribes_b2b ?? 0,
                'unsubscribes_b2c' => $interactionStats->unsubscribes_b2c ?? 0,
            ],
        ];
    }

    public function getRecentInteractions(int $limit = 10): array
    {
        return WaCampaignInteractions::with(['campaign', 'contact'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($interaction) {
                return [
                    'id' => $interaction->id,
                    'campaign_id' => $interaction->campaign_id,
                    'campaign_name' => $interaction->campaign->name ?? 'Unknown',
                    'contact_id' => $interaction->contact_id,
                    'contact_flag' => $interaction->contact_flag,
                    'interaction_type' => $interaction->interaction_type,
                    'button_text' => $interaction->button_text,
                    'clicked_url' => $interaction->clicked_url,
                    'interaction_data' => $interaction->interaction_data,
                    'created_at' => $interaction->created_at,
                ];
            })
            ->toArray();
    }

    public function getMessageTrackingDetails(int $campaignId, int $limit = 50): array
    {
        return WaCampaignMessageTracking::with(['campaign', 'contact'])
            ->where('campaign_id', $campaignId)
            ->latest('sent_at')
            ->limit($limit)
            ->get()
            ->map(function ($tracking) {
                return [
                    'id' => $tracking->id,
                    'campaign_id' => $tracking->campaign_id,
                    'campaign_name' => $tracking->campaign->name ?? 'Unknown',
                    'contact_id' => $tracking->contact_id,
                    'contact_flag' => $tracking->contact_flag,
                    'message_id' => $tracking->message_id,
                    'status' => $tracking->status,
                    'sent_at' => $tracking->sent_at,
                    'delivered_at' => $tracking->delivered_at,
                    'read_at' => $tracking->read_at,
                    'failed_at' => $tracking->failed_at,
                    'error_code' => $tracking->error_code,
                    'error_message' => $tracking->error_message,
                ];
            })
            ->toArray();
    }

    public function getCampaignStats(int $campaignId): array
    {
        $stats = WaNewsLetterStats::where('campaign_id', $campaignId)->first();

        if (!$stats) {
            return [
                'campaign_id' => $campaignId,
                'total_sent' => 0,
                'total_delivered' => 0,
                'total_opened' => 0,
                'total_clicks' => 0,
                'total_unsubscribed' => 0,
                'total_failed' => 0,
                'delivery_rate' => 0,
                'open_rate' => 0,
                'click_rate' => 0,
                'unsubscribe_rate' => 0,
                'campaign_status' => 'not_found',
            ];
        }

        $messageStats = WaCampaignMessageTracking::getCampaignStats($campaignId);
        $interactionStats = WaCampaignInteractions::getCampaignInteractionStats($campaignId);

        return [
            'campaign_id' => $campaignId,
            'total_sent' => $stats->total_sent,
            'total_delivered' => $stats->total_delivered,
            'total_opened' => $stats->total_opened,
            'total_clicks' => $stats->total_clicks,
            'total_unsubscribed' => $stats->total_unsubscribed,
            'total_failed' => $stats->total_failed,
            'delivery_rate' => $stats->getDeliveryRate(),
            'open_rate' => $stats->getOpenRate(),
            'click_rate' => $stats->getClickRate(),
            'unsubscribe_rate' => $stats->getUnsubscribeRate(),
            'campaign_status' => $stats->campaign_status,
            'started_at' => $stats->started_at,
            'completed_at' => $stats->completed_at,
            'message_breakdown' => $messageStats,
            'interaction_breakdown' => $interactionStats,
        ];
    }

    public function getCampaignDetailedStats(int $campaignId): array
    {
        try {
            $stats = WaNewsLetterStats::where('campaign_id', $campaignId)->first();

            if (!$stats) {
                return [];
            }

            $messageStats = WaCampaignMessageTracking::getCampaignStats($campaignId);
            $interactionStats = WaCampaignInteractions::getCampaignInteractionStats($campaignId);

            $recentMessages = WaCampaignMessageTracking::where('campaign_id', $campaignId)
                ->with('contact')
                ->latest('sent_at')
                ->limit(20)
                ->get()
                ->map(function ($tracking) {
                    return [
                        'id' => $tracking->id,
                        'contact_id' => $tracking->contact_id,
                        'contact_flag' => $tracking->contact_flag,
                        'message_id' => $tracking->message_id,
                        'status' => $tracking->status,
                        'sent_at' => $tracking->sent_at,
                        'delivered_at' => $tracking->delivered_at,
                        'read_at' => $tracking->read_at,
                        'failed_at' => $tracking->failed_at,
                        'error_code' => $tracking->error_code,
                        'error_message' => $tracking->error_message,
                    ];
                });

            $recentInteractions = WaCampaignInteractions::where('campaign_id', $campaignId)
                ->with('contact')
                ->latest('created_at')
                ->limit(20)
                ->get()
                ->map(function ($interaction) {
                    return [
                        'id' => $interaction->id,
                        'contact_id' => $interaction->contact_id,
                        'contact_flag' => $interaction->contact_flag,
                        'interaction_type' => $interaction->interaction_type,
                        'button_text' => $interaction->button_text,
                        'clicked_url' => $interaction->clicked_url,
                        'interaction_data' => $interaction->interaction_data,
                        'created_at' => $interaction->created_at,
                    ];
                });

            return [
                'campaign_id' => $campaignId,
                'campaign_stats' => [
                    'total_sent' => $stats->total_sent,
                    'total_delivered' => $stats->total_delivered,
                    'total_opened' => $stats->total_opened,
                    'total_clicks' => $stats->total_clicks,
                    'total_unsubscribed' => $stats->total_unsubscribed,
                    'total_failed' => $stats->total_failed,
                    'delivery_rate' => $stats->getDeliveryRate(),
                    'open_rate' => $stats->getOpenRate(),
                    'click_rate' => $stats->getClickRate(),
                    'unsubscribe_rate' => $stats->getUnsubscribeRate(),
                    'campaign_status' => $stats->campaign_status,
                    'started_at' => $stats->started_at,
                    'completed_at' => $stats->completed_at,
                ],
                'message_breakdown' => $messageStats,
                'interaction_breakdown' => $interactionStats,
                'recent_messages' => $recentMessages,
                'recent_interactions' => $recentInteractions,
                'performance_metrics' => [
                    'success_rate' => $stats->total_sent > 0 ? round((($stats->total_delivered + $stats->total_opened) / $stats->total_sent) * 100, 2) : 0,
                    'engagement_rate' => $stats->total_delivered > 0 ? round((($stats->total_opened + $stats->total_clicks) / $stats->total_delivered) * 100, 2) : 0,
                    'failure_rate' => $stats->total_sent > 0 ? round(($stats->total_failed / $stats->total_sent) * 100, 2) : 0,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get campaign detailed stats: ' . $e->getMessage(), [
                'campaign_id' => $campaignId
            ]);
            return [];
        }
    }
}
