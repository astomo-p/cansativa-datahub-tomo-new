<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactTypes extends Model
{
     /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

     /** relation */

    public function contacts(): HasMany
    {
        return $this->hasMany(Contacts::class, 'contact_type_id', 'id');
    }
}
