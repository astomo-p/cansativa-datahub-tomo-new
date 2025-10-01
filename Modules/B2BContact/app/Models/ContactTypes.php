<?php

namespace Modules\B2BContact\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\B2BContact\Models\Contacts;
use Modules\NewAnalytics\Models\UserSavedPosts;
use Modules\NewAnalytics\Models\VisitorLikes;
use Modules\NewAnalytics\Models\UserComments;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
// use Modules\B2BContact\Database\Factories\ContactTypesFactory;

class ContactTypes extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $connection = 'pgsql_b2b';
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

    public function amountPosts(): HasManyThrough
    {
        return $this->hasManyThrough(
            VisitorLikes::class,
            Contacts::class,
            'contact_type_id', // Foreign key on Contacts table
            'published_by', // Foreign key on VisitorLikes table
            'id', // Local key on ContactTypes table
            'user_id' // Local key on Contacts table
        );
    }

    public function amountComments(): HasManyThrough
    {
        return $this->hasManyThrough(
            UserComments::class,
            Contacts::class,
            'contact_type_id', // Foreign key on Contacts table
            'user_id', // Foreign key on UserComments table
            'id', // Local key on ContactTypes table
            'user_id' // Local key on Contacts table
        );
    }

    public function savedFilters()
    {
        return $this->hasMany(SavedFilter::class, 'contact_type_id');
    }
   

    // protected static function newFactory(): ContactTypesFactory
    // {
    //     // return ContactTypesFactory::new();
    // }
}
