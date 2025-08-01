<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\NewContactData\Models\ContactTypes;
use Modules\Campaign\Models\CampaignFactory;

// use Modules\Campaign\Database\Factories\CampaignFactory;

class Campaign extends Model
{
    use HasFactory;

    protected $connection = 'pgsql_b2b_shared';

    /**
     * The attributes that are mass assignable.
     */
    public $timestamps = true;

    const CREATED_AT = 'created_date';

    const UPDATED_AT = 'updated_date';

    protected $fillable = [
        'contact_type_id',
        'recipient_type',
        'campaign_name',
        'brevo_campaign_id',
        'filters',
        'channel',
        'created_by',
        'updated_by',
    ];

    public function contactType()
    {
        return $this->belongsTo(ContactTypes::class, 'contact_type_id');
    }

    public function campaignContacts()
    {
        return $this->hasMany(CampaignContact::class, 'campaign_id');
    }

    // protected static function newFactory(): CampaignFactory
    // {
    //     // return CampaignFactory::new();
    // }
    protected static function newFactory()
    {
        return CampaignFactory::new(); 
    }

}
