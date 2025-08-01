<?php

namespace Modules\WhatsappNewsletter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WaCampaignMessageTracking extends Model
{
    use HasFactory;

    protected $connection = 'pgsql_b2b_shared';
    protected $table = 'wa_campaign_message_tracking';

    protected $fillable = [
        'campaign_id',
        'contact_id',
        'contact_flag',
        'message_id',
        'status',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'error_code',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(WaNewsLetter::class, 'campaign_id');
    }

    public function contact()
    {
        if ($this->contact_flag === 'b2b') {
            return $this->belongsTo(\Modules\NewContactData\Models\B2BContacts::class, 'contact_id');
        } else {
            return $this->belongsTo(\Modules\NewContactData\Models\Contacts::class, 'contact_id');
        }
    }

    public function scopeByCampaign($query, int $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeByContactFlag($query, string $contactFlag)
    {
        return $query->where('contact_flag', $contactFlag);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public static function getCampaignStats(int $campaignId): array
    {
        $stats = static::where('campaign_id', $campaignId)
            ->selectRaw('
                COUNT(*) as total_sent,
                COUNT(CASE WHEN status = "delivered" THEN 1 END) as total_delivered,
                COUNT(CASE WHEN status = "read" THEN 1 END) as total_read,
                COUNT(CASE WHEN status = "failed" THEN 1 END) as total_failed,
                COUNT(CASE WHEN contact_flag = "b2b" THEN 1 END) as total_b2b,
                COUNT(CASE WHEN contact_flag = "b2c" THEN 1 END) as total_b2c
            ')
            ->first();

        return [
            'total_sent' => $stats->total_sent ?? 0,
            'total_delivered' => $stats->total_delivered ?? 0,
            'total_read' => $stats->total_read ?? 0,
            'total_failed' => $stats->total_failed ?? 0,
            'total_b2b' => $stats->total_b2b ?? 0,
            'total_b2c' => $stats->total_b2c ?? 0,
        ];
    }
}