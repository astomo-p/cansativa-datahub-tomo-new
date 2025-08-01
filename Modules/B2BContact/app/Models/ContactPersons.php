<?php

namespace Modules\B2BContact\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\B2BContact\Database\Factories\ContactPersonsFactory;

class ContactPersons extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    /**
     * The non default database connection for this model.
     */
    protected $connection = 'pgsql_b2b';

    /**
     * The table associated with the model.
     */
    protected $table = 'contacts';

    protected $fillable = ['contact_name', 'phone_no', 'email', 'contact_person'];
}
