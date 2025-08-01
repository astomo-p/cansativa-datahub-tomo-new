<?php

namespace Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// use Modules\Campaign\Database\Factories\CampaignContactFactory;

class CampaignContact extends Model
{
    use HasFactory;

    protected $connection = 'pgsql_b2b_shared';

    /**
     * The attributes that are mass assignable.
     */
    const CREATED_AT = 'created_date';

    const UPDATED_AT = 'updated_date';

    protected $fillable = ['campaign_id', 'contact_type', 'brevo_id', 'contact_id', 'status_message', 'status', 'created_date', 'updated_date'];

    // protected static function newFactory(): CampaignContactFactory
    // {
    //     // return CampaignContactFactory::new();
    // }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }
}
