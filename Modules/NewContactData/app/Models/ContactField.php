<?php

namespace Modules\NewContactData\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactField extends Model
{
    use HasFactory;

    protected $fillable = ['field_name', 'field_type', 'description'];
    protected $connection = 'pgsql';
    protected $table = 'contact_fields';

    public function fieldValues()
    {
        return $this->hasMany(ContactFieldValue::class);
    }
}