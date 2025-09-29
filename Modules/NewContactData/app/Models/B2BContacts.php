<?php

namespace Modules\NewContactData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\NewContactData\Database\Factories\B2BContactsFactory;

class B2BContacts extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'contact_name',
        'phone_no',
        'last_message_at',
        'contact_type_id',
        'created_date',
        'created_by',
        'updated_date',
        'updated_by',
        'last_export_at',
        'status'
    ];

    /**
     * The non default database connection for this model.
     */
    protected $connection = 'pgsql_b2b';

    /**
     * The table associated with the model.
     */
    protected $table = 'contacts';

    public $timestamps = true;

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    public function updateLastMessageAt($timestamp = null)
    {
        $this->last_message_at = $timestamp ?? now();
        return $this->save();
    }

    /** relation */

    public function contactType()
    {
        return $this->belongsTo(B2BContactTypes::class, 'contact_type_id', 'id');
    }

    public function pharmacyChilds()
    {
        return $this->hasMany(B2BContacts::class, 'contact_parent_id', 'id');
    }



    // protected static function newFactory(): B2BContactsFactory
    // {
    //     // return B2BContactsFactory::new();
    // }
}
