<?php

namespace Modules\WhatsappNewsletter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class WaNewsLetter extends Model
{
    use HasFactory;

    const STATUS_SENDING_COMPLETE = 'Sending complete';
    const STATUS_SENDING_IN_PROGRESS = 'Sending in progress';
    const STATUS_SCHEDULED = 'Scheduled';
    const STATUS_DRAFT_APPROVED = 'Draft - Approved';
    const STATUS_DRAFT_IN_REVIEW = 'Draft - In review';
    const STATUS_DRAFT_NOT_SUBMITTED = 'Draft - Not submitted for review';
    const STATUS_DRAFT_DISAPPROVED = 'Draft - Disapproved by Meta';
    const STATUS_ARCHIVE = 'Archive';

    const SEND_TYPE_NOW = 'SEND NOW';
    const SEND_TYPE_SCHEDULED = 'SCHEDULED';

    const SENDING_STATUS_WAITING = 'waiting';
    const SENDING_STATUS_READY = 'ready';
    const SENDING_STATUS_PROCESSING = 'processing';
    const SENDING_STATUS_SUCCESS = 'success';
    const SENDING_STATUS_FAILED = 'failed';

    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_SENDING_COMPLETE,
            self::STATUS_SENDING_IN_PROGRESS,
            self::STATUS_SCHEDULED,
            self::STATUS_DRAFT_APPROVED,
            self::STATUS_DRAFT_IN_REVIEW,
            self::STATUS_DRAFT_NOT_SUBMITTED,
            self::STATUS_DRAFT_DISAPPROVED,
            self::STATUS_ARCHIVE,
        ];
    }

    public static function getValidSendTypes(): array
    {
        return [
            self::SEND_TYPE_NOW,
            self::SEND_TYPE_SCHEDULED,
        ];
    }

    public static function getValidSendingStatuses(): array
    {
        return [
            self::SENDING_STATUS_WAITING,
            self::SENDING_STATUS_READY,
            self::SENDING_STATUS_PROCESSING,
            self::SENDING_STATUS_SUCCESS,
            self::SENDING_STATUS_FAILED,
        ];
    }

    protected $connection = 'pgsql_b2b_shared';
    protected $table = 'wa_campaign_newsletters';

    protected $fillable = [
        'name',
        'scheduled_at',
        'schedule_timezone',
        'sent_at',
        'contact_type_id',
        'contact_flag',
        'wa_template_id',
        'created_by',
        'status',
        'filters',
        'send_type',
        'batch_amount',
        'interval_days',
        'interval_hours',
        'send_message_start_hours',
        'send_message_end_hours',
        'timezone',
        'sending_status',
        'frequency_cap_enabled',
        'frequency_cap_limit',
        'frequency_cap_period',
        'frequency_cap_unit',
        'contact_ids',
        'saved_filter_id'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'frequency_cap_enabled' => 'boolean',
    ];

    protected $attributes = [
        'status' => 'Draft - In review',
        'frequency_cap_enabled' => false,
        'sending_status' => self::SENDING_STATUS_READY,
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (!in_array($model->status, self::getValidStatuses())) {
                throw new \InvalidArgumentException("Invalid status value: {$model->status}");
            }

            if (!is_null($model->send_type) && !in_array($model->send_type, self::getValidSendTypes())) {
                throw new \InvalidArgumentException("Invalid send_type value: {$model->send_type}");
            }

            if (!is_null($model->sending_status) && !in_array($model->sending_status, self::getValidSendingStatuses())) {
                throw new \InvalidArgumentException("Invalid sending_status value: {$model->sending_status}");
            }
        });
    }

    public function setStatusAttribute($value)
    {
        if (!in_array($value, self::getValidStatuses())) {
            throw new \InvalidArgumentException("Invalid status value: {$value}");
        }

        $this->attributes['status'] = $value;
    }

    public function setSendTypeAttribute($value)
    {
        if (!is_null($value) && !in_array($value, self::getValidSendTypes())) {
            throw new \InvalidArgumentException("Invalid send_type value: {$value}");
        }

        $this->attributes['send_type'] = $value;
    }

    public function setSendingStatusAttribute($value)
    {
        if (!in_array($value, self::getValidSendingStatuses())) {
            throw new \InvalidArgumentException("Invalid sending_status value: {$value}");
        }
        $this->attributes['sending_status'] = $value;
    }

    public function stats()
    {
        return $this->hasOne(WaNewsLetterStats::class, 'campaign_id');
    }

    public function batches()
    {
        return $this->hasMany(WaNewsLetterBatch::class, 'newsletter_id');
    }

    public static function processScheduledNewsletters(callable $callback)
    {
        $scheduled = self::where('status', self::STATUS_SCHEDULED)->get();
        foreach ($scheduled as $newsletter) {
            $callback($newsletter);
        }
    }

    public static function getScheduledForSending($now = null)
    {
        $now = $now ?: now();

        return self::where('status', self::STATUS_DRAFT_APPROVED)
            ->where('sending_status', self::SENDING_STATUS_READY)
            ->whereNotNull('wa_template_id')
            ->where(function ($query) {
                $query->whereNotNull('filters')
                    ->orWhereNotNull('contact_ids');
            })
            ->get()
            ->filter(function ($newsletter) use ($now) {
                if (!$newsletter->scheduled_at) {
                    return true;
                }

                $scheduleTimezone = $newsletter->schedule_timezone ?: $newsletter->timezone ?: config('app.timezone');
                $currentTimeInScheduleTimezone = $now->copy()->setTimezone($scheduleTimezone);
                $scheduledTimeInTimezone = \Carbon\Carbon::parse($newsletter->scheduled_at)
                    ->setTimezone($scheduleTimezone);

                return $scheduledTimeInTimezone->lte($currentTimeInScheduleTimezone);
            });
    }

    public function shouldUseTrickle(): bool
    {
        return !is_null($this->batch_amount) &&
            ($this->batch_amount > 0) &&
            (!is_null($this->interval_days) || !is_null($this->interval_hours));
    }
}
