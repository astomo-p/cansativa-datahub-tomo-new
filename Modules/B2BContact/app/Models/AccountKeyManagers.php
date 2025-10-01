<?php

namespace Modules\B2BContact\Models;

use Illuminate\Database\Eloquent\Model;

class AccountKeyManagers extends Model
{
    protected $connection = 'pgsql_b2b';
    protected $table = 'account_key_managers';
    
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';
    
    protected $fillable = ['manager_name', 'email', 'phone', 'auto_reply', 'message_template_name', 'wa_campaign_id'];

    public function contact()
    {
        return $this->hasOne(B2BContacts::class, 'id', 'account_key_manager_id');
    }
}
