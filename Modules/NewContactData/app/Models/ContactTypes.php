<?php

namespace Modules\NewContactData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\NewContactData\Models\Contacts;
use Modules\NewAnalytics\Models\UserSavedPosts;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
// use Modules\NewContactData\Database\Factories\ContactTypesFactory;

class ContactTypes extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

     /** relation */

    public function contacts()
    {
        return $this->hasMany(Contacts::class, 'contact_type_id', 'id');
    }

    
     public function savedPosts(): HasManyThrough
    {
        return $this->hasManyThrough(
            UserSavedPosts::class,
            Contacts::class,
            'contact_type_id', // Foreign key on Contacts table
            'user_id', // Foreign key on UserSavedPosts table
            'id', // Local key on ContactTypes table
            'user_id' // Local key on Contacts table
        );
    }
   

    // protected static function newFactory(): ContactTypesFactory
    // {
    //     // return ContactTypesFactory::new();
    // }
}
