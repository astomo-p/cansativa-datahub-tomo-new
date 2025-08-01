<?php

namespace Modules\WhatsappNewsletter\Services;

use Illuminate\Support\Facades\Log;
use Modules\WhatsappNewsletter\Models\WaNewsLetter;
use Modules\WhatsappNewsletter\Jobs\SendWaNewsLetterJob;

class TrickleCampaignService
{
    protected $batchProcessingService;

    public function __construct()
    {
        $this->batchProcessingService = new BatchProcessingService();
    }

    public function triggerTrickleCampaign(int $newsletterId): array
    {
        $newsletter = WaNewsLetter::find($newsletterId);

        if (!$newsletter) {
            return ['status' => 'error', 'message' => 'Newsletter not found'];
        }

        if ($newsletter->status !== WaNewsLetter::STATUS_DRAFT_APPROVED) {
            return ['status' => 'error', 'message' => 'Newsletter must be in Draft - Approved status'];
        }

        Log::info("[TrickleCampaignService] Triggering campaign for newsletter {$newsletterId}");

        try {
            if ($this->batchProcessingService->shouldUseTrickle($newsletter)) {
                Log::info("[TrickleCampaignService] Using trickle processing for newsletter {$newsletterId}");

                $result = $this->batchProcessingService->createBatchesForNewsletter($newsletter);

                if ($result['status'] === 'success') {
                    Log::info("[TrickleCampaignService] Successfully created {$result['batches_created']} batches for newsletter {$newsletterId}");

                    return [
                        'status' => 'success',
                        'message' => 'Trickle campaign initiated successfully',
                        'type' => 'trickle',
                        'data' => $result
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to create batches: ' . $result['message']
                    ];
                }
            } else {
                Log::info("[TrickleCampaignService] Using immediate processing for newsletter {$newsletterId}");

                $newsletter->sending_status = WaNewsLetter::SENDING_STATUS_READY;

                if ($newsletter->send_type === WaNewsLetter::SEND_TYPE_NOW) {
                    $newsletter->scheduled_at = now();
                    $newsletter->status = WaNewsLetter::STATUS_DRAFT_APPROVED;
                } else {
                    $newsletter->status = WaNewsLetter::STATUS_SCHEDULED;
                }

                $newsletter->save();
                SendWaNewsLetterJob::dispatch($newsletter->id);

                return [
                    'status' => 'success',
                    'message' => 'Campaign queued for immediate processing',
                    'type' => 'immediate',
                    'data' => [
                        'newsletter_id' => $newsletter->id,
                        'send_type' => $newsletter->send_type
                    ]
                ];
            }
        } catch (\Exception $e) {
            Log::error("[TrickleCampaignService] Error triggering campaign for newsletter {$newsletterId}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to trigger campaign: ' . $e->getMessage()
            ];
        }
    }

    public function getCampaignStatus(int $newsletterId): array
    {
        $newsletter = WaNewsLetter::with(['batches', 'stats'])->find($newsletterId);

        if (!$newsletter) {
            return ['status' => 'error', 'message' => 'Newsletter not found'];
        }

        $data = [
            'newsletter_id' => $newsletter->id,
            'status' => $newsletter->status,
            'sending_status' => $newsletter->sending_status,
            'send_type' => $newsletter->send_type,
            'has_trickle' => $this->batchProcessingService->shouldUseTrickle($newsletter),
            'scheduled_at' => $newsletter->scheduled_at,
            'sent_at' => $newsletter->sent_at,
        ];

        if ($newsletter->batches->isNotEmpty()) {
            $data['batches'] = [
                'total_batches' => $newsletter->batches->first()->total_batches ?? 0,
                'completed_batches' => $newsletter->batches->where('status', 'completed')->count(),
                'pending_batches' => $newsletter->batches->where('status', 'pending')->count(),
                'processing_batches' => $newsletter->batches->where('status', 'processing')->count(),
                'failed_batches' => $newsletter->batches->where('status', 'failed')->count(),
                'batches_detail' => $newsletter->batches->map(function ($batch) {
                    return [
                        'batch_number' => $batch->batch_number,
                        'status' => $batch->status,
                        'scheduled_at' => $batch->scheduled_at,
                        'sent_at' => $batch->sent_at,
                        'processed_count' => $batch->processed_count,
                        'success_count' => $batch->success_count,
                        'error_count' => $batch->error_count,
                    ];
                })
            ];
        }

        if ($newsletter->stats) {
            $data['stats'] = [
                'total_sent' => $newsletter->stats->total_sent,
                'total_delivered' => $newsletter->stats->total_delivered,
                'total_opened' => $newsletter->stats->total_opened,
                'total_clicked' => $newsletter->stats->total_clicked,
                'total_unsubscribed' => $newsletter->stats->total_unsubscribed,
            ];
        }

        return [
            'status' => 'success',
            'data' => $data
        ];
    }

    public function cancelTrickleCampaign(int $newsletterId): array
    {
        $newsletter = WaNewsLetter::find($newsletterId);

        if (!$newsletter) {
            return ['status' => 'error', 'message' => 'Newsletter not found'];
        }

        if (!in_array($newsletter->status, [
            WaNewsLetter::STATUS_SCHEDULED,
            WaNewsLetter::STATUS_DRAFT_APPROVED
        ])) {
            return ['status' => 'error', 'message' => 'Campaign cannot be cancelled in its current status'];
        }

        try {
            $pendingBatches = $newsletter->batches()->where('status', 'pending')->count();
            $newsletter->batches()->where('status', 'pending')->delete();

            $newsletter->status = WaNewsLetter::STATUS_ARCHIVE;
            $newsletter->sending_status = WaNewsLetter::SENDING_STATUS_FAILED;
            $newsletter->save();

            Log::info("[TrickleCampaignService] Cancelled trickle campaign for newsletter {$newsletterId}, deleted {$pendingBatches} pending batches");

            return [
                'status' => 'success',
                'message' => 'Trickle campaign cancelled successfully',
                'deleted_batches' => $pendingBatches
            ];
        } catch (\Exception $e) {
            Log::error("[TrickleCampaignService] Error cancelling campaign for newsletter {$newsletterId}: " . $e->getMessage());

            return [
                'status' => 'error',
                'message' => 'Failed to cancel campaign: ' . $e->getMessage()
            ];
        }
    }
}
