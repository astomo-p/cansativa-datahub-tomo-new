<?php

namespace Modules\B2BContact\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\B2BContact\Database\Factories\SavedFiltersFactory;

class SavedFilters extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    protected $connection = 'pgsql_b2b';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['filter_name', 'applied_filters', 'created_by', 'updated_by', 'contact_type_id', 'amount_of_contacts'];

    public function historyExport()
    {
        return $this->hasMany(HistoryExports::class, 'saved_filter_id', 'id');
    }
}
