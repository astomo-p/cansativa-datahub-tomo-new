<?php

namespace Modules\AuditLog\Models;

use Illuminate\Database\Eloquent\Model;

class ContactLogs extends Model
{
    protected $connection = 'pgsql_b2b_shared';

    /**
     * The attributes that are mass assignable.
     */
    // protected $fillable = [];
    protected $guarded = [];

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

}
