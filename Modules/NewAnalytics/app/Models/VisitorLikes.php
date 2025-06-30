<?php

namespace Modules\NewAnalytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\NewAnalytics\Database\Factories\VisitorLikesFactory;

class VisitorLikes extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    protected $table = 'posts';

    // protected static function newFactory(): VisitorLikesFactory
    // {
    //     // return VisitorLikesFactory::new();
    // }
}
