<?php

namespace Modules\B2BContactAdjustment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\B2BContact\Database\Factories\B2BContactFactory;

class B2BContacts extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['files'];

    public $timestamps = false;

    /**
     * The non default database connection for this model.
     */
    protected $connection = 'pgsql_b2b';

    /**
     * The table associated with the model.
     */
    protected $table = 'contacts';

     /** relation */

    public function pharmacyChilds()
    {
        return $this->hasMany(B2BContacts::class, 'contact_parent_id', 'id');
    }

    public function contactPersons()
    {
        return $this->hasMany(B2BContacts::class, 'contact_person', 'id');
    }

    public function documents()
    {
        return $this->hasMany(B2BFiles::class, 'contact_id', 'id');
    }

    protected static function newFactory()
    {
        return \Modules\B2BContact\Database\Factories\B2BContactsFactory::new();
    }
}
