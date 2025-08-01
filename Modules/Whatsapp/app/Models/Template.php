<?php

namespace Modules\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\Rule;

class Template extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_message_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fbid',
        'name',
        'language',
        'components',
        'parameter_format',
        'status',
        'api_status',
        'category',
        'body',
        'header_type',
        'header_content',
        'header_default_value',
        'body_default_value',
        'button_type',
        'button_action',
        'button_text',
        'button_url',
        'button_phone_number',
        'button_footer_text'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'components' => 'array',
        'header_default_value' => 'array',
        'body_default_value' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Template categories available
     *
     * @var array
     */
    public const CATEGORIES = [
        'UTILITY',
        'MARKETING'
    ];

    /**
     * Template statuses available
     *
     * @var array
     */
    public const STATUSES = [
        'APPROVED',
        'IN REVIEW', // PENDING
        'DRAFT',
        'DISAPPROVED', // REJECTED
        'ARCHIVED',
    ];

    public const API_STATUSES = [
        'APPROVED',
        'IN_APPEAL',
        'PENDING',
        'REJECTED',
        'PENDING_DELETION',
        'DELETED',
        'DISABLED',
        'PAUSED',
        'LIMIT_EXCEEDED'
    ];

    public const API_TO_LOCAL_STATUS = [
        'APPROVED' => 'APPROVED',
        'IN_APPEAL' => 'IN REVIEW',
        'PENDING' => 'IN REVIEW',
        'REJECTED' => 'DISAPPROVED',
        'PENDING_DELETION' => 'ARCHIVED',
        'DELETED' => 'ARCHIVED',
        'DISABLED' => 'DISAPPROVED',
        'PAUSED' => 'IN REVIEW',
        'LIMIT_EXCEEDED' => 'DISAPPROVED',
    ];

    /**
     * Parameter formats available
     *
     * @var array
     */
    public const PARAMETER_FORMATS = [
        'POSITIONAL',
        'NAMED'
    ];

    /**
     * Scope a query to find template by name.
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    /**
     * Scope a query to find template by fbid.
     */
    public function scopeByFbid($query, string $fbid)
    {
        return $query->where('fbid', $fbid);
    }

    /**
     * Scope a query to find approved templates.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    /**
     * Check if template is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }

    /**
     * Update template status.
     */
    public function updateStatus(string $status, ?string $reason = null): bool
    {
        if (!in_array($status, self::STATUSES)) {
            return false;
        }

        return $this->update(['status' => $status]);
    }

    /**
     * Get the validation rules for the model.
     *
     * @param int|null $id
     * @return array
     */
    public static function validationRules($id = null): array
    {
        return [
            'fbid' => [
                'required',
                'string',
                Rule::unique('wa_message_templates', 'fbid')->ignore($id)
            ],
            'name' => [
                'required',
                'string',
                Rule::unique('wa_message_templates', 'name')->ignore($id)
            ],
            'language' => 'required|string',
            'components' => 'required|array',
            'parameter_format' => 'nullable|in:' . implode(',', self::PARAMETER_FORMATS),
            'status' => 'nullable|in:' . implode(',', self::STATUSES),
            'api_status' => 'nullable|in:' . implode(',', self::API_STATUSES),
            'category' => 'nullable|in:' . implode(',', self::CATEGORIES),
        ];
    }

    /**
     * Check if fbid already exists.
     */
    public static function fbidExists(string $fbid, ?int $excludeId = null): bool
    {
        $query = static::where('fbid', $fbid);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if name already exists.
     */
    public static function nameExists(string $name, ?int $excludeId = null): bool
    {
        $query = static::where('name', $name);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
