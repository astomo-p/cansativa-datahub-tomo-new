<?php

namespace Modules\B2BContact\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class B2BContacts extends Model
{
    use HasFactory;

    protected $guarded = ['files'];

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    protected $connection = 'pgsql_b2b';
    protected $table = 'contacts';

    protected $casts = [
        'amount_purchase' => 'decimal:2',
        'average_purchase' => 'decimal:2',
        'total_purchase' => 'decimal:2',
        'whatsapp_subscription' => 'boolean',
        'email_subscription' => 'boolean',
    ];

    protected $appends = ['amount_contacts', 'custom_fields'];
    protected $hidden = ['custom_field_values', 'customFieldValues'];

    /** relation */

    public function pharmacyDatabase()
    {
        return $this->hasMany(Contacts::class, 'contact_parent_id', 'id');
    }

    public function getAmountContactsAttribute()
    {
        return $this->pharmacyDatabase()->count();
    }

    public function contactPersons()
    {
        return $this->hasMany(B2BContacts::class, 'contact_person', 'id');
    }

    public function documents()
    {
        return $this->hasMany(B2BFiles::class, 'contact_id', 'id');
    }

    public function customFieldValues()
    {
        return $this->hasMany(ContactFieldValue::class, 'contact_id', 'id');
    }

    public function getCustomFieldsAttribute()
    {
        return $this->customFieldValues()->with('contactField')->get()->mapWithKeys(function ($item) {
            return [$item->contactField->field_name => $item->value];
        });
    }

    protected static function newFactory()
    {
        return \Modules\B2BContact\Database\Factories\B2BContactsFactory::new();
    }

    public function accountManager()
    {
        return $this->belongsTo(AccountKeyManagers::class, 'account_key_manager_id', 'id');
    }

    protected function amountPurchase(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value === null ? 0 : $value,
        );
    }

    protected function averagePurchase(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value === null ? 0 : $value,
        );
    }

    protected function totalPurchase(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value === null ? 0 : $value,
        );
    }
}
