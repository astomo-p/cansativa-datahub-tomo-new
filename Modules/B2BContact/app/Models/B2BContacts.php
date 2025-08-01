<?php

namespace Modules\B2BContact\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class B2BContacts extends Model
{
    use HasFactory;

    protected $guarded = ['files'];

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    protected $connection = 'pgsql_b2b';
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

    public function customFieldValues()
    {
        return $this->hasMany(ContactFieldValue::class, 'contact_id', 'id');
    }

    public function getCustomFieldsWithValues()
    {
        return $this->customFieldValues()->with('contactField')->get()->mapWithKeys(function ($item) {
            return [$item->contactField->field_name => $item->value];
        });
    }

    protected static function newFactory()
    {
        return \Modules\B2BContact\Database\Factories\B2BContactsFactory::new();
    }
}
