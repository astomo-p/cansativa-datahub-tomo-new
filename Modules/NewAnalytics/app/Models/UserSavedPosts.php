<?php

namespace Modules\NewAnalytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\NewAnalytics\Database\Factories\UserSavedPostsFactory;

class UserSavedPosts extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): UserSavedPostsFactory
    // {
    //     // return UserSavedPostsFactory::new();
    // }
}
