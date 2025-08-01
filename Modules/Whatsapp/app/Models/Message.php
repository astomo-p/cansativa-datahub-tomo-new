<?php

namespace Modules\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\NewContactData\Models\Contacts as ModelsContacts;

class Message extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shared.wa_messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wamid',
        'contact_id',
        'status',
        'error_code',
        'error_message',
        'direction',
        'type',
        'media_file',
        'header',
        'body',
        'body_text',
        'footer',
        'buttons',
        'scenario_session_id',
        'language',
        'template_name',
        'template_language',
        'is_read',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'direction' => 'integer',
        'buttons' => 'array',
        'scenario_session_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'accepted'
    ];

    /**
     * Message types available
     *
     * @var array
     */
    public const TYPES = [
        'text',
        'reply',
        'button',
        'image',
        'vcard',
        'audio',
        'video',
        'file',
        'template',
        'location',
        'custom',
        'unsupported'
    ];

    /**
     * Message statuses available
     *
     * @var array
     */
    public const STATUSES = [
        'accepted',
        'sent',
        'delivered',
        'read',
        'failed'
    ];

    /**
     * Get the contact that owns the message.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(ModelsContacts::class, 'contact_id');
    }
}
