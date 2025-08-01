<?php

namespace Modules\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Whatsapp\Database\Factories\WhatsappChatTemplateFactory;

class WhatsappChatTemplate extends Model
{
    use HasFactory;

    protected $table = 'wa_chat_templates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['fbid', 'template_name', 'language_code', 'message'];

    // protected static function newFactory(): WhatsappChatTemplateFactory
    // {
    //     // return WhatsappChatTemplateFactory::new();
    // }
}
