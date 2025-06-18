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
    protected $fillable = [];

    /**
     * The non default database connection for this model.
     */
    protected $connection = 'pgsql_b2b';

    /**
     * The table associated with the model.
     */
    protected $table = 'b2b_contacts';

     /** relation */

    public function pharmacyChilds()
    {
        return $this->hasMany(B2BContacts::class, 'contact_parent_id', 'id');
    }
    

    // protected static function newFactory(): B2BContactsFactory
    // {
    //     // return B2BContactsFactory::new();
    // }
}
