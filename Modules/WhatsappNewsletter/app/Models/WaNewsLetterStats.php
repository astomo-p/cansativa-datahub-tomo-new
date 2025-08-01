<?php

namespace Modules\WhatsappNewsletter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WaNewsLetterStats extends Model
{
    use HasFactory;

    protected $connection = 'pgsql_b2b_shared';
    protected $table = 'wa_campaign_newsletter_stats';

    protected $fillable = [
        'total_sent',
        'total_delivered',
        'total_opened',
        'total_clicks',
        'total_unsubscribed',
        'total_failed',
        'campaign_id',
        'campaign_status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'total_sent' => 'integer',
        'total_delivered' => 'integer',
        'total_opened' => 'integer',
        'total_clicks' => 'integer',
        'total_unsubscribed' => 'integer',
        'total_failed' => 'integer',
        'campaign_id' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'total_sent' => 0,
        'total_delivered' => 0,
        'total_opened' => 0,
        'total_clicks' => 0,
        'total_unsubscribed' => 0,
        'total_failed' => 0,
        'campaign_status' => 'pending',
    ];

    public function campaign()
    {
        return $this->belongsTo(WaNewsLetter::class, 'campaign_id');
    }

    public function getDeliveryRate(): float
    {
        return $this->total_sent > 0 ? round(($this->total_delivered / $this->total_sent) * 100, 2) : 0;
    }

    public function getOpenRate(): float
    {
        return $this->total_delivered > 0 ? round(($this->total_opened / $this->total_delivered) * 100, 2) : 0;
    }

    public function getClickRate(): float
    {
        return $this->total_delivered > 0 ? round(($this->total_clicks / $this->total_delivered) * 100, 2) : 0;
    }

    public function getUnsubscribeRate(): float
    {
        return $this->total_sent > 0 ? round(($this->total_unsubscribed / $this->total_sent) * 100, 2) : 0;
    }

    public function getCampaignStats(): array
    {
        return [
            'sent' => $this->total_sent,
            'delivered' => $this->total_delivered,
            'opened' => $this->total_opened,
            'clicks' => $this->total_clicks,
            'unsubscribed' => $this->total_unsubscribed,
            'failed' => $this->total_failed,
            'delivery_rate' => $this->getDeliveryRate(),
            'open_rate' => $this->getOpenRate(),
            'click_rate' => $this->getClickRate(),
            'unsubscribe_rate' => $this->getUnsubscribeRate(),
            'campaign_status' => $this->campaign_status,
            'contact_flag' => $this->campaign?->contact_flag,
        ];
    }
}