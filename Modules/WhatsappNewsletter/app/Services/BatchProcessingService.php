<?php

namespace Modules\WhatsappNewsletter\Services;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Modules\WhatsappNewsletter\Models\WaNewsLetter;
use Modules\WhatsappNewsletter\Models\WaNewsLetterBatch;
use Modules\NewContactData\Models\Contacts;
use Modules\NewContactData\Models\B2BContacts;
use Modules\WhatsappNewsletter\Helpers\FilterQueryHelper;

class BatchProcessingService
{
    public function createBatchesForNewsletter(WaNewsLetter $newsletter): array
    {
        Log::info("[BatchProcessingService] Creating batches for newsletter ID: {$newsletter->id}");
        $contacts = $this->getContactsForNewsletter($newsletter);

        if ($contacts->isEmpty()) {
            Log::warning("[BatchProcessingService] No contacts found for newsletter {$newsletter->id}");
            return ['status' => 'error', 'message' => 'No contacts found'];
        }

        $totalContacts = $contacts->count();
        $batchSize = $newsletter->batch_amount ?: $totalContacts;
        $totalBatches = ceil($totalContacts / $batchSize);

        Log::info("[BatchProcessingService] Newsletter {$newsletter->id}: {$totalContacts} contacts, batch size: {$batchSize}, total batches: {$totalBatches}");

        WaNewsLetterBatch::where('newsletter_id', $newsletter->id)->delete();

        $contactChunks = $contacts->pluck('id')->chunk($batchSize);
        $batches = [];
        $batchNumber = 1;

        foreach ($contactChunks as $contactIds) {
            $scheduledAt = $this->calculateBatchScheduleTime($newsletter, $batchNumber);
            $batchTimezone = $newsletter->timezone ?: config('app.timezone');

            $batch = WaNewsLetterBatch::create([
                'newsletter_id' => $newsletter->id,
                'batch_number' => $batchNumber,
                'total_batches' => $totalBatches,
                'contact_ids' => $contactIds->toArray(),
                'scheduled_at' => $scheduledAt->utc(),
                'timezone' => $batchTimezone,
            ]);

            $batches[] = $batch;

            Log::info("[BatchProcessingService] Created batch {$batchNumber}/{$totalBatches} for newsletter {$newsletter->id}, scheduled at: {$scheduledAt->format('Y-m-d H:i:s')} {$batchTimezone} (UTC: {$scheduledAt->utc()->format('Y-m-d H:i:s')})");

            $batchNumber++;
        }

        $newsletter->status = WaNewsLetter::STATUS_SCHEDULED;
        $newsletter->sending_status = WaNewsLetter::SENDING_STATUS_READY;
        $newsletter->save();

        return [
            'status' => 'success',
            'total_contacts' => $totalContacts,
            'total_batches' => $totalBatches,
            'batches_created' => count($batches)
        ];
    }

    private function getContactsForNewsletter(WaNewsLetter $newsletter)
    {
        if (!empty($newsletter->contact_ids)) {
            $contactIds = is_string($newsletter->contact_ids)
                ? json_decode($newsletter->contact_ids, true)
                : $newsletter->contact_ids;

            if ($newsletter->contact_flag === 'b2b') {
                return B2BContacts::on('pgsql_b2b')
                    ->whereIn('id', $contactIds)
                    ->whereNotNull('phone_no')
                    ->get();
            } else {
                return Contacts::whereIn('id', $contactIds)
                    ->whereNotNull('phone_no')
                    ->get();
            }
        }

        $filters = $newsletter->filters;
        if (is_string($filters)) {
            $filters = json_decode($filters, true);
        }

        if (!is_array($filters)) {
            $filters = [];
        }

        $query = Contacts::query();
        $query = FilterQueryHelper::applyFilters($query, $filters, $newsletter->contact_type_id);

        return $query->whereNotNull('phone_no')->get();
    }

    private function calculateBatchScheduleTime(WaNewsLetter $newsletter, int $batchNumber): Carbon
    {
        $timezone = $newsletter->timezone ?: config('app.timezone');

        if ($batchNumber === 1) {
            if ($newsletter->send_type === WaNewsLetter::SEND_TYPE_SCHEDULED) {
                $baseTime = Carbon::parse($newsletter->scheduled_at)
                    ->setTimezone($newsletter->schedule_timezone ?: $timezone);
            } else {
                $baseTime = now()->setTimezone($timezone);
            }
        } else {
            $intervalDays = $newsletter->interval_days ?: 0;
            $intervalHours = $newsletter->interval_hours ?: 0;

            if ($newsletter->send_type === WaNewsLetter::SEND_TYPE_SCHEDULED) {
                $baseTime = Carbon::parse($newsletter->scheduled_at)
                    ->setTimezone($newsletter->schedule_timezone ?: $timezone);
            } else {
                $baseTime = now()->setTimezone($timezone);
            }

            $totalIntervalDays = ($batchNumber - 1) * $intervalDays;
            $totalIntervalHours = ($batchNumber - 1) * $intervalHours;

            $baseTime->addDays($totalIntervalDays)->addHours($totalIntervalHours);
        }

        if ($newsletter->send_message_start_hours && $newsletter->send_message_end_hours) {
            $startHour = (int) explode(':', $newsletter->send_message_start_hours)[0];
            $endHour = (int) explode(':', $newsletter->send_message_end_hours)[0];

            if ($baseTime->hour < $startHour) {
                $baseTime->setHour($startHour)->setMinute(0)->setSecond(0);
            } elseif ($baseTime->hour >= $endHour) {
                $baseTime->addDay()->setHour($startHour)->setMinute(0)->setSecond(0);
            }
        }

        return $baseTime;
    }

    public function shouldUseTrickle(WaNewsLetter $newsletter): bool
    {
        return !is_null($newsletter->batch_amount) &&
            ($newsletter->batch_amount > 0) &&
            (!is_null($newsletter->interval_days) || !is_null($newsletter->interval_hours));
    }

    public function getPendingBatches($now = null): \Illuminate\Support\Collection
    {
        return WaNewsLetterBatch::getPendingBatches($now);
    }
}
