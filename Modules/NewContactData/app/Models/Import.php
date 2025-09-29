<?php

namespace Modules\NewContactData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\NewContactData\Database\Factories\ImportFactory;

class Import extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_b2b_shared';

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    // protected static function newFactory(): ImportFactory
    // {
    //     // return ImportFactory::new();
    // }
}
