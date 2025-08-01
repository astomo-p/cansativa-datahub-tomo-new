<?php

namespace Modules\WhatsappNewsletter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\WhatsappNewsletter\Models\WaNewsLetter;
use Modules\WhatsappNewsletter\Models\WaNewsLetterBatch;
use Modules\WhatsappNewsletter\Services\CampaignTemplateSendService;
use Modules\WhatsappNewsletter\Services\CampaignTrackingService;

class SendWaNewsLetterBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $batchId)
    {
        $this->batchId = $batchId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info("[SendWaNewsLetterBatchJob] Processing batch ID: {$this->batchId}");

        $batch = WaNewsLetterBatch::find($this->batchId);

        if (!$batch) {
            Log::error("[SendWaNewsLetterBatchJob] Batch not found: {$this->batchId}");
            return;
        }

        $newsletter = $batch->newsletter;

        if (!$newsletter) {
            Log::error("[SendWaNewsLetterBatchJob] Newsletter not found for batch: {$this->batchId}");
            $batch->markAsFailed();
            return;
        }

        $batch->markAsProcessing();

        if ($batch->batch_number === 1) {
            $newsletter->status = WaNewsLetter::STATUS_SENDING_IN_PROGRESS;
            $newsletter->sending_status = WaNewsLetter::SENDING_STATUS_PROCESSING;
            $newsletter->save();

            $trackingService = new CampaignTrackingService();
            $trackingService->initializeCampaignTracking($newsletter->id);
        }

        try {
            $service = new CampaignTemplateSendService();
            $result = $service->sendTemplateToBatchContacts($newsletter->id, $batch->contact_ids);

            Log::info("[SendWaNewsLetterBatchJob] Batch {$this->batchId} result: " . json_encode($result));

            if ($result['status'] === 'success') {
                $batch->markAsCompleted($result['success_count'], $result['error_count']);

                Log::info("[SendWaNewsLetterBatchJob] Batch {$this->batchId} completed successfully. Success: {$result['success_count']}, Errors: {$result['error_count']}");

                if ($this->isLastBatch($batch)) {
                    $this->completeCampaign($newsletter);
                }
            } else {
                $batch->markAsFailed();
                Log::error("[SendWaNewsLetterBatchJob] Batch {$this->batchId} failed: " . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $batch->markAsFailed();
            Log::error("[SendWaNewsLetterBatchJob] Exception processing batch {$this->batchId}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function isLastBatch(WaNewsLetterBatch $batch): bool
    {
        $remainingBatches = WaNewsLetterBatch::where('newsletter_id', $batch->newsletter_id)
            ->where('status', WaNewsLetterBatch::STATUS_PENDING)
            ->count();

        return $remainingBatches === 0;
    }

    private function completeCampaign(WaNewsLetter $newsletter)
    {
        Log::info("[SendWaNewsLetterBatchJob] Completing campaign for newsletter {$newsletter->id}");

        $trackingService = new CampaignTrackingService();
        $trackingService->completeCampaign($newsletter->id);

        $newsletter->status = WaNewsLetter::STATUS_SENDING_COMPLETE;
        $newsletter->sending_status = WaNewsLetter::SENDING_STATUS_SUCCESS;
        $newsletter->sent_at = now();
        $newsletter->save();

        Log::info("[SendWaNewsLetterBatchJob] Campaign completed for newsletter {$newsletter->id}");
    }
}
