<?php

namespace Modules\WhatsappNewsletter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class WaNewsLetterBatch extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    protected $connection = 'pgsql_b2b_shared';
    protected $table = 'wa_campaign_newsletter_batches';

    protected $fillable = [
        'newsletter_id',
        'batch_number',
        'total_batches',
        'contact_ids',
        'scheduled_at',
        'sent_at',
        'status',
        'processed_count',
        'success_count',
        'error_count',
        'timezone'
    ];

    protected $casts = [
        'contact_ids' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'processed_count' => 0,
        'success_count' => 0,
        'error_count' => 0,
    ];

    public function newsletter()
    {
        return $this->belongsTo(WaNewsLetter::class, 'newsletter_id');
    }

    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
        ];
    }

    public function setStatusAttribute($value)
    {
        if (!in_array($value, self::getValidStatuses())) {
            throw new \InvalidArgumentException("Invalid status value: {$value}");
        }

        $this->attributes['status'] = $value;
    }

    public static function getPendingBatches($now = null)
    {
        $now = $now ?: now();

        return self::where('status', self::STATUS_PENDING)
            ->get()
            ->filter(function ($batch) use ($now) {
                $batchTimezone = $batch->timezone ?: config('app.timezone');
                $currentTimeInBatchTimezone = $now->copy()->setTimezone($batchTimezone);

                $batchScheduledTime = Carbon::parse($batch->scheduled_at)
                    ->utc()
                    ->setTimezone($batchTimezone);

                return $batchScheduledTime->lte($currentTimeInBatchTimezone);
            })
            ->sortBy('scheduled_at')
            ->sortBy('batch_number')
            ->values();
    }

    public function markAsProcessing()
    {
        $this->status = self::STATUS_PROCESSING;
        $this->save();
    }

    public function markAsCompleted(int $successCount, int $errorCount)
    {
        $this->status = self::STATUS_COMPLETED;
        $this->sent_at = now();
        $this->processed_count = count($this->contact_ids);
        $this->success_count = $successCount;
        $this->error_count = $errorCount;
        $this->save();
    }

    public function markAsFailed()
    {
        $this->status = self::STATUS_FAILED;
        $this->save();
    }
}
