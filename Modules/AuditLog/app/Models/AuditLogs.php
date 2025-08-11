<?php

namespace Modules\AuditLog\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLogs extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    protected $fillable = ['full_name', 'email', 'module', 'activity'];

    /**
     * The non default database connection for this model.
     */
    protected $connection = 'pgsql_b2b_shared';

    const MODULE_PHARMACY = 'Pharmacies';
    const MODULE_SUPPLIER = 'Supplier';
    const MODULE_GENERAL_NEWSLETTER = 'General Newsletter';
    const MODULE_COMMUNITY = 'High Passion Community';
    const MODULE_PHARMACY_DB = 'Pharmacy Databases';
    const MODULE_WA_NEWSLETTER = 'WhatsApp Newsletter';
    const MODULE_EMAIL_NEWSLETTER = 'Email Newsletter';
    const MODULE_WA_CENTER = 'WhatsApp Center';
    const MODULE_SETTING = 'Setting';

    const MODULES = [
        self::MODULE_PHARMACY,
        self::MODULE_SUPPLIER,
        self::MODULE_GENERAL_NEWSLETTER,
        self::MODULE_COMMUNITY,
        self::MODULE_PHARMACY_DB,
        self::MODULE_WA_NEWSLETTER,
        self::MODULE_EMAIL_NEWSLETTER,
        self::MODULE_WA_CENTER,
        self::MODULE_SETTING,
    ];
    
}
