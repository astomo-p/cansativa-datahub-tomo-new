<?php

namespace Modules\B2BContact\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactField extends Model
{
    use HasFactory;

    protected $fillable = ['field_name', 'field_type', 'description'];
    protected $connection = 'pgsql_b2b';

    public function fieldValues()
    {
        return $this->hasMany(ContactFieldValue::class);
    }
}