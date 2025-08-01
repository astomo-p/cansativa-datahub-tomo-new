<?php

namespace Modules\WhatsappNewsletter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\WhatsappNewsletter\Models\WaNewsLetter;
use Modules\WhatsappNewsletter\Services\CampaignTemplateSendService;
use Modules\WhatsappNewsletter\Services\CampaignTrackingService;

class SendWaNewsLetterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $newsletterId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $newsletterId)
    {
        $this->newsletterId = $newsletterId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info("[SendWaNewsLetterJob] Processing newsletter ID: {$this->newsletterId}");

        $trackingService = new CampaignTrackingService();
        $trackingService->initializeCampaignTracking($this->newsletterId);
        $newsletter = WaNewsLetter::find($this->newsletterId);

        if ($newsletter) {
            $newsletter->status = WaNewsLetter::STATUS_SENDING_IN_PROGRESS;
            $newsletter->sending_status = WaNewsLetter::SENDING_STATUS_PROCESSING;
            $newsletter->save();
        }

        $service = new CampaignTemplateSendService();
        $result = $service->sendTemplateToContactIds($this->newsletterId);

        Log::info("[SendWaNewsLetterJob] Result for newsletter ID {$this->newsletterId}: " . json_encode($result));

        if ($result['status'] === 'success') {
            $trackingService->completeCampaign($this->newsletterId);

            if ($newsletter) {
                $newsletter->status = WaNewsLetter::STATUS_SENDING_COMPLETE;
                $newsletter->sending_status = WaNewsLetter::SENDING_STATUS_SUCCESS;
                $newsletter->sent_at = now();
                $newsletter->save();
            }
        } else {
            if ($newsletter) {
                $newsletter->sending_status = WaNewsLetter::SENDING_STATUS_FAILED;
                $newsletter->save();
            }
        }
    }
}
