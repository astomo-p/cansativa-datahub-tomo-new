<?php

namespace Modules\NewContactData\Models;

use App\Helpers\TranslationHelper;
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

    protected $casts = [
        'applied_filters' => 'array',
        'created_date'    => 'datetime',
    ];

    protected $appends = ['row_no', 'saved_filter_name'];

    public function toArray()
    {
        return [
            'row_no'             => $this->row_no,
            'id'                 => $this->id,
            'name'               => $this->name,
            'export_name'        => $this->name,
            'contact_type'       => $this->contact_type,
            'saved_filter_name'    => $this->saved_filter_name,
            'applied_filters'    => $this->applied_filters,
            'amount_of_contacts' => $this->amount_of_contacts,
            'export_to'          => $this->formatExportTo(),
            'created_date'       => $this->created_date,
        ];
    }

    public function formatExportTo()
    {
        $map = [
            '.xlsx'    => '.xlsx',
            'xlsx'     => '.xlsx',
            'whatsapp' => 'WhatsApp',
            'email'    => 'Email',
        ];

        return $map[strtolower($this->export_to)] ?? ucfirst($this->export_to);
    }

    public function translateContactType()
    {
        $contactType = TranslationHelper::getContactTypeKey($this->contact_type);
        return TranslationHelper::translate($contactType);
    }

    public function savedFilter()
    {
        return $this->hasOne(SavedFilters::class, 'id', 'saved_filter_id');
    }

    public function getRowNoAttribute($value)
    {
        return $this->attributes['row_no'] ?? null;
    }

    public function getSavedFilterNameAttribute()
    {
        return $this->savedFilter->filter_name ?? null;
    }
}
