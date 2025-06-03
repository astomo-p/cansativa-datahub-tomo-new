<?php

namespace Modules\ContactData\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contacts extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['contact_name', 'phone_no', 'last_message_at', 'contact_type_id', 'created_date', 'created_by', 'updated_date', 'updated_by'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    public function updateLastMessageAt($timestamp = null)
    {
        $this->last_message_at = $timestamp ?? now();
        return $this->save();
    }

     /** relation */

    public function pharmacyChilds()
    {
        return $this->hasMany(Contacts::class, 'contact_parent_id', 'id');
    }

    protected static function newFactory()
    {
        //return ContactsFactory::new();
    }
}
