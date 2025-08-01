<?php

namespace Modules\WhatsappNewsletter\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\Log;

class WaNewsLetterScheduler extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'wa-newsletter:scheduler';

    /**
     * The console command description.
     */
    protected $description = 'Send scheduled WhatsApp newsletters.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->handleRegularScheduledNewsletters();
        $this->handleTrickleBatches();
    }

    private function handleRegularScheduledNewsletters()
    {
        $now = now();
        $scheduled = \Modules\WhatsappNewsletter\Models\WaNewsLetter::getScheduledForSending($now);
        Log::info("[WaNewsLetterScheduler] Found {$scheduled->count()} scheduled WhatsApp newsletters to send.");

        if ($scheduled->isEmpty()) {
            Log::info('[WaNewsLetterScheduler] No scheduled WhatsApp newsletters found.');
            return;
        }

        $batchProcessingService = new \Modules\WhatsappNewsletter\Services\BatchProcessingService();
        $dispatched = 0;
        $batchesCreated = 0;

        foreach ($scheduled as $newsletter) {
            if ($batchProcessingService->shouldUseTrickle($newsletter)) {
                $timezone = $newsletter->timezone ?: config('app.timezone');
                Log::info("[WaNewsLetterScheduler] Creating batches for newsletter {$newsletter->id} (trickle enabled, timezone: {$timezone})");

                $result = $batchProcessingService->createBatchesForNewsletter($newsletter);

                if ($result['status'] === 'success') {
                    $batchesCreated += $result['batches_created'];
                    Log::info("[WaNewsLetterScheduler] Created {$result['batches_created']} batches for newsletter {$newsletter->id}");
                } else {
                    Log::error("[WaNewsLetterScheduler] Failed to create batches for newsletter {$newsletter->id}: " . $result['message']);
                }
            } else {
                $scheduleTimezone = $newsletter->schedule_timezone ?: $newsletter->timezone ?: config('app.timezone');
                Log::info("[WaNewsLetterScheduler] Dispatching regular newsletter {$newsletter->id} (no trickle, timezone: {$scheduleTimezone})");
                \Modules\WhatsappNewsletter\Jobs\SendWaNewsLetterJob::dispatch($newsletter->id);
                $dispatched++;
            }
        }

        if ($dispatched > 0) {
            Log::info("[WaNewsLetterScheduler] Regular newsletters dispatched to queue. Total: {$dispatched}");
        }

        if ($batchesCreated > 0) {
            Log::info("[WaNewsLetterScheduler] Trickle batches created. Total: {$batchesCreated}");
        }
    }

    private function handleTrickleBatches()
    {
        $now = now();
        Log::info("[WaNewsLetterScheduler] Current time for trickle batch processing: {$now->format('Y-m-d H:i:s')} UTC");

        $batchProcessingService = new \Modules\WhatsappNewsletter\Services\BatchProcessingService();
        $pendingBatches = $batchProcessingService->getPendingBatches($now);

        Log::info("[WaNewsLetterScheduler] Found {$pendingBatches->count()} pending trickle batches to process.");

        if ($pendingBatches->isEmpty()) {
            Log::info('[WaNewsLetterScheduler] No pending trickle batches found.');
            return;
        }

        $dispatched = 0;
        foreach ($pendingBatches as $batch) {
            $batchTimezone = $batch->timezone ?: config('app.timezone');
            $scheduledTimeInTimezone = \Carbon\Carbon::parse($batch->scheduled_at)
                ->utc()
                ->setTimezone($batchTimezone);

            Log::info("[WaNewsLetterScheduler] Dispatching batch {$batch->id} (Newsletter: {$batch->newsletter_id}, Batch: {$batch->batch_number}/{$batch->total_batches}, Scheduled: {$scheduledTimeInTimezone->format('Y-m-d H:i:s')} {$batchTimezone})");
            \Modules\WhatsappNewsletter\Jobs\SendWaNewsLetterBatchJob::dispatch($batch->id);
            $dispatched++;
        }

        Log::info("[WaNewsLetterScheduler] Trickle batches dispatched to queue. Total: {$dispatched}");
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['example', InputArgument::REQUIRED, 'An example argument.'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }
}
