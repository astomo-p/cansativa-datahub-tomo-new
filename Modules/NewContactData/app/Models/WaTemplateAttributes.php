<?php

namespace Modules\NewContactData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\NewContactData\Database\Factories\WaTemplateAttributesFactory;

class WaTemplateAttributes extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    //protected $fillable = [];

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';


    // protected static function newFactory(): WaTemplateAttributesFactory
    // {
    //     // return WaTemplateAttributesFactory::new();
    // }
}
