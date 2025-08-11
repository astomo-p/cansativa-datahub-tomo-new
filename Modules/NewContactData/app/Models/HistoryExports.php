<?php

namespace Modules\NewContactData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\NewContactData\Models\SavedFilters;
// use Modules\NewContactData\Database\Factories\HistoryExportsFactory;

class HistoryExports extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    public function savedFilter()
    {
        return $this->hasOne(SavedFilters::class, 'id', 'saved_filter_id');
    }

    // protected static function newFactory(): HistoryExportsFactory
    // {
    //     // return HistoryExportsFactory::new();
    // }
}
