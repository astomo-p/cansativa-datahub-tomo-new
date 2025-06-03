<?php

namespace Modules\ContactData\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\ContactData\Database\factories\PlacePublishsFactory;

class PlacePublishs extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];
    
    protected static function newFactory(): PlacePublishsFactory
    {
        //return PlacePublishsFactory::new();
    }
}
