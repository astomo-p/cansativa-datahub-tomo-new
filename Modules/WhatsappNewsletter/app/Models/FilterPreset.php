<?php

namespace Modules\WhatsappNewsletter\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\NewContactData\Models\ContactTypes;

class FilterPreset extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $connection = 'pgsql_b2b_shared';
    protected $table = 'wa_campaign_filter_presets';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'contact_type_id',
        'contact_flag',
        'filters'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'filters' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Get the contact type that owns the filter preset.
     */
    public function contactType()
    {
        return $this->belongsTo(
            ContactTypes::class,
            'contact_type_id',
            'id'
        )->on('pgsql');
    }

    /**
     * Get filter presets by contact type ID
     * 
     * @param int $contactTypeId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByContactType(int $contactTypeId)
    {
        return self::where('contact_type_id', $contactTypeId)->get();
    }

    /**
     * Get all filter presets
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllPresets()
    {
        return self::with('contactType')->get();
    }
}
