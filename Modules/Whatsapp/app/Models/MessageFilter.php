<?php

namespace Modules\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Whatsapp\Database\Factories\MessageFilterFactory;

class MessageFilter extends Model
{
    use HasFactory;

    protected $table = 'wa_message_filters';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['user_id', 'filter_query', 'name'];

    // protected static function newFactory(): MessageFilterFactory
    // {
    //     // return MessageFilterFactory::new();
    // }
}
