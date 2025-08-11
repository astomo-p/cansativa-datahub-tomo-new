<?php

namespace Modules\NewContactData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\NewContactData\Models\ContactTypes;
use Modules\NewContactData\Models\HistoryExports;
// use Modules\NewContactData\Database\Factories\SavedFiltersFactory;

class SavedFilters extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';
    
    
    protected $fillable = ['filter_name', 'applied_filters', 'created_by', 'updated_by', 'contact_type_id', 'amount_of_contacts', 'is_frequence',
    'is_apply_freq', 'frequency_cap', 'newsletter_channel'];

    public function historyExport()
    {
        return $this->hasMany(HistoryExports::class, 'saved_filter_id', 'id');
    }

     public function contactType()
    {
        return $this->hasMany(ContactTypes::class, 'id', 'contact_type_id');
    }

    // protected static function newFactory(): SavedFiltersFactory
    // {
    //     // return SavedFiltersFactory::new();
    // }
}
