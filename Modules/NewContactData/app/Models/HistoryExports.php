<?php

namespace Modules\NewContactData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\NewContactData\Database\Factories\HistoryExportsFactory;

class HistoryExports extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): HistoryExportsFactory
    // {
    //     // return HistoryExportsFactory::new();
    // }
}
