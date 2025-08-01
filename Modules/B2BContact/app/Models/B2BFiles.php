<?php

namespace Modules\B2BContact\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\B2BContact\Database\Factories\B2BFilesFactory;

class B2BFiles extends Model
{
    use HasFactory;

    /**
     * The non default database connection for this model.
     */
    protected $connection = 'pgsql_b2b';
    public $timestamps = false;

    /**
     * The table associated with the model.
     */
    protected $table = 'files';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['contact_id', 'file_name', 'file_path'];

    
}
