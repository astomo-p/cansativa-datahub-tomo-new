<?php

namespace Modules\B2BContact\Models;

use Illuminate\Database\Eloquent\Model;

class ColumnMappings extends Model
{
    protected $connection = 'pgsql_b2b';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['field_name','display_name','contact_type_id','field_type'];

}
