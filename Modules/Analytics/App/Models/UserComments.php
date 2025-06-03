<?php

namespace Modules\Analytics\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Analytics\Database\factories\UserCommentsFactory;

class UserComments extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];
    
    protected static function newFactory(): UserCommentsFactory
    {
        //return UserCommentsFactory::new();
    }
}
