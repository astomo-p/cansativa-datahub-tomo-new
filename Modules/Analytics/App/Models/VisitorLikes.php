<?php

namespace Modules\Analytics\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VisitorLikes extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];
    //protected $table = "";
    
    protected static function newFactory()
    {
        //return VisitorLikesFactory::new();
    }
}
