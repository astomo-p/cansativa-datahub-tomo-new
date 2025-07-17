<?php

namespace Modules\NewContactData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\NewContactData\Database\Factories\SharedContactLogFactory;

class SharedContactLogs extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    /**
     * The non default database connection for this model.
     */
    protected $connection = 'pgsql_b2b_shared';

    /**
     * The table associated with the model.
     */
    protected $table = 'contact_logs';

    // protected static function newFactory(): SharedContactLogFactory
    // {
    //     // return SharedContactLogFactory::new();
    // }
}
