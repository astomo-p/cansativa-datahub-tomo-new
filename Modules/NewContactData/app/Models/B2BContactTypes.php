<?php

namespace Modules\NewContactData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\NewContactData\Models\B2BContacts;
// use Modules\NewContactData\Database\Factories\B2BContactTypesFactory;

class B2BContactTypes extends Model
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
    protected $table = 'b2b_contact_types';

     /** relation */

    public function contacts()
    {
        return $this->hasMany(B2BContacts::class, 'contact_type_id', 'id');
    }

    // protected static function newFactory(): B2BContactTypesFactory
    // {
    //     // return B2BContactTypesFactory::new();
    // }
}
