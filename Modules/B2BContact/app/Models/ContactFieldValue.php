<?php

namespace Modules\B2BContact\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactFieldValue extends Model
{
    use HasFactory;

    protected $fillable = ['contact_id', 'contact_field_id', 'value'];
    protected $connection = 'pgsql_b2b';

    // Relasi ke kontak pemilik nilai
    public function contact()
    {
        return $this->belongsTo(B2BContacts::class);
    }

    // Relasi ke definisi field kustom
    public function contactField()
    {
        return $this->belongsTo(ContactField::class);
    }
}