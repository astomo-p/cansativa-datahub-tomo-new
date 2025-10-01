<?php

namespace Modules\B2BContact\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Campaign\Models\CampaignContact;

// use Modules\B2BContact\Database\Factories\ContactsFactory;

class Contacts extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    protected $connection = 'pgsql';
    protected $table = 'contacts';

    protected $guarded = ['files'];

    public function updateLastMessageAt($timestamp = null)
    {
        $this->last_message_at = $timestamp ?? now();

        return $this->save();
    }

    /** relation */
    public function pharmacyChilds()
    {
        return $this->hasMany(Contacts::class, 'contact_parent_id', 'id');
    }

    public function campaignContacts()
    {
        return $this->hasMany(CampaignContact::class, 'contact_id', 'id');
    }

    // protected static function newFactory(): ContactsFactory
    // {
    //     // return ContactsFactory::new();
    // }
}
