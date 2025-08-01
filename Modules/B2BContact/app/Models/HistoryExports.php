<?php

namespace Modules\B2BContact\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\B2BContact\Database\Factories\HistoryExportsFactory;

class HistoryExports extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_b2b';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    public function savedFilter()
    {
        return $this->hasOne(SavedFilters::class, 'id', 'saved_filter_id');
    }
}
