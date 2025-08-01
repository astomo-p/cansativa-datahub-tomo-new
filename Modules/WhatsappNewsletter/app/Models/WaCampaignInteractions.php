<?php

namespace Modules\WhatsappNewsletter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WaCampaignInteractions extends Model
{
    use HasFactory;

    protected $connection = 'pgsql_b2b_shared';
    protected $table = 'wa_campaign_interactions';

    protected $fillable = [
        'campaign_id',
        'contact_id',
        'contact_flag',
        'interaction_type',
        'button_text',
        'clicked_url',
        'interaction_data',
    ];

    protected $casts = [
        'interaction_data' => 'array',
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

    public function scopeByInteractionType($query, string $interactionType)
    {
        return $query->where('interaction_type', $interactionType);
    }

    public static function getCampaignInteractionStats(int $campaignId): array
    {
        $stats = static::where('campaign_id', $campaignId)
            ->selectRaw('
                COUNT(CASE WHEN interaction_type = "click" THEN 1 END) as total_clicks,
                COUNT(CASE WHEN interaction_type = "unsubscribe" THEN 1 END) as total_unsubscribes,
                COUNT(CASE WHEN interaction_type = "click" AND contact_flag = "b2b" THEN 1 END) as clicks_b2b,
                COUNT(CASE WHEN interaction_type = "click" AND contact_flag = "b2c" THEN 1 END) as clicks_b2c,
                COUNT(CASE WHEN interaction_type = "unsubscribe" AND contact_flag = "b2b" THEN 1 END) as unsubscribes_b2b,
                COUNT(CASE WHEN interaction_type = "unsubscribe" AND contact_flag = "b2c" THEN 1 END) as unsubscribes_b2c
            ')
            ->first();

        return [
            'total_clicks' => $stats->total_clicks ?? 0,
            'total_unsubscribes' => $stats->total_unsubscribes ?? 0,
            'clicks_b2b' => $stats->clicks_b2b ?? 0,
            'clicks_b2c' => $stats->clicks_b2c ?? 0,
            'unsubscribes_b2b' => $stats->unsubscribes_b2b ?? 0,
            'unsubscribes_b2c' => $stats->unsubscribes_b2c ?? 0,
        ];
    }
}