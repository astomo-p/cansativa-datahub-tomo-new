<?php

namespace Modules\NewContactData\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// use Modules\NewContactData\Database\Factories\ContactsFactory;

class Contacts extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['files'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    const CREATED_AT = 'created_date';

    const UPDATED_AT = 'updated_date';

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'whatsapp_subscription' => 'boolean',
        'temporary_whatsapp_subscription' => 'boolean',
    ];

    public function updateLastMessageAt($timestamp = null)
    {
        $this->last_message_at = $timestamp ?? now();

        return $this->save();
    }

    /** relation */
    public function contactType()
    {
        return $this->belongsTo(ContactTypes::class, 'contact_type_id', 'id');
    }

    public function pharmacyChilds()
    {
        return $this->hasMany(Contacts::class, 'contact_parent_id', 'id');
    }

    // protected static function newFactory(): ContactsFactory
    // {
    //     // return ContactsFactory::new();
    // }
}
