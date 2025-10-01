<?php

namespace Modules\B2BContact\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactField extends Model
{
    use HasFactory;

    protected $connection = 'pgsql_b2b';
    protected $fillable = ['field_name', 'field_type', 'description', 'contact_type_id'];

    public function fieldValues()
    {
        return $this->hasMany(ContactFieldValue::class);
    }
}