<?php

namespace Modules\NewContactData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\NewContactData\Database\Factories\ContactTypesFactory;

class ContactTypes extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

     /** relation */

    public function contacts(): HasMany
    {
        return $this->hasMany(Contacts::class, 'contact_type_id', 'id');
    }

    // protected static function newFactory(): ContactTypesFactory
    // {
    //     // return ContactTypesFactory::new();
    // }
}
