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

    public function toArray()
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name ?? $this->contact_name,
            'export_name'        => $this->name ?? $this->contact_name,
            'contact_type'       => $this->contact_type,
            'applied_filters'     => $this->applied_filters,
            'amount_of_contacts' => $this->amount_contacts,
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

    // protected static function newFactory(): HistoryExportsFactory
    // {
    //     // return HistoryExportsFactory::new();
    // }
}
