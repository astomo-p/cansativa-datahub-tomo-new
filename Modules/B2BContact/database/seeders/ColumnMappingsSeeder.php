<?php

namespace Modules\B2BContact\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\B2BContact\Models\ColumnMappings;

class ColumnMappingsSeeder extends Seeder
{
    protected $connection = 'pgsql_b2b';
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Contact Type 1 / Pharmacy fields
        $fieldsType1 = [
            ['contact_type_id' => 1, 'field_name' => 'contact_name',     'display_name' => 'Pharmacy Name',     'field_type' => 'Text'],
            ['contact_type_id' => 1, 'field_name' => 'contact_no',       'display_name' => 'Pharmacy Number',   'field_type' => 'Text'],
            ['contact_type_id' => 1, 'field_name' => 'address',          'display_name' => 'Address',          'field_type' => 'Text'],
            ['contact_type_id' => 1, 'field_name' => 'post_code',        'display_name' => 'Post Code',        'field_type' => 'Text'],
            ['contact_type_id' => 1, 'field_name' => 'city',             'display_name' => 'City',             'field_type' => 'Text'],
            ['contact_type_id' => 1, 'field_name' => 'country',          'display_name' => 'Country',          'field_type' => 'Text'],
            ['contact_type_id' => 1, 'field_name' => 'contact_person',   'display_name' => 'Contact Person',   'field_type' => 'Text'],
            ['contact_type_id' => 1, 'field_name' => 'email',            'display_name' => 'Email',            'field_type' => 'Text'],
            ['contact_type_id' => 1, 'field_name' => 'phone_no',         'display_name' => 'Phone Number',     'field_type' => 'Text'],
            ['contact_type_id' => 1, 'field_name' => 'amount_purchase',  'display_name' => 'Amount Purchase',  'field_type' => 'Number'],
            ['contact_type_id' => 1, 'field_name' => 'total_purchase',   'display_name' => 'Total Purchase',   'field_type' => 'Number'],
            ['contact_type_id' => 1, 'field_name' => 'average_purchase', 'display_name' => 'Average Purchase', 'field_type' => 'Number'],
            ['contact_type_id' => 1, 'field_name' => 'last_purchase_date','display_name' => 'Last Purchase Date','field_type' => 'Date'],
            ['contact_type_id' => 1, 'field_name' => 'email_subscription',    'display_name' => 'Email Subscription',    'field_type' => 'Text'],
            ['contact_type_id' => 1, 'field_name' => 'whatsapp_subscription', 'display_name' => 'Whatsapp Subscription', 'field_type' => 'Text'],
            ['contact_type_id' => 1, 'field_name' => 'created_date',      'display_name' => 'Created Date',       'field_type' => 'Date'],
        ];

        // Contact Type 2 / Supplier fields
        $fieldsType2 = [
            ['contact_type_id' => 2, 'field_name' => 'contact_name',     'display_name' => 'Company Name',     'field_type' => 'Text'],
            ['contact_type_id' => 2, 'field_name' => 'vat_id',           'display_name' => 'Vat ID',           'field_type' => 'Text'],
            ['contact_type_id' => 2, 'field_name' => 'address',          'display_name' => 'Address',          'field_type' => 'Text'],
            ['contact_type_id' => 2, 'field_name' => 'post_code',        'display_name' => 'Post Code',        'field_type' => 'Text'],
            ['contact_type_id' => 2, 'field_name' => 'city',             'display_name' => 'City',             'field_type' => 'Text'],
            ['contact_type_id' => 2, 'field_name' => 'country',          'display_name' => 'Country',          'field_type' => 'Text'],
            ['contact_type_id' => 2, 'field_name' => 'contact_person',   'display_name' => 'Contact Person',   'field_type' => 'Text'],
            ['contact_type_id' => 2, 'field_name' => 'email',            'display_name' => 'Email',            'field_type' => 'Text'],
            ['contact_type_id' => 2, 'field_name' => 'phone_no',         'display_name' => 'Phone Number',     'field_type' => 'Text'],
            ['contact_type_id' => 2, 'field_name' => 'amount_purchase',  'display_name' => 'Amount Purchase',  'field_type' => 'Number'],
            ['contact_type_id' => 2, 'field_name' => 'total_purchase',   'display_name' => 'Total Purchase',   'field_type' => 'Number'],
            ['contact_type_id' => 2, 'field_name' => 'average_purchase', 'display_name' => 'Average Purchase', 'field_type' => 'Number'],
            ['contact_type_id' => 2, 'field_name' => 'last_purchase_date','display_name' => 'Last Purchase Date','field_type' => 'Date'],
            ['contact_type_id' => 2, 'field_name' => 'email_subscription',    'display_name' => 'Email Subscription',    'field_type' => 'Text'],
            ['contact_type_id' => 2, 'field_name' => 'whatsapp_subscription', 'display_name' => 'Whatsapp Subscription', 'field_type' => 'Text'],
            ['contact_type_id' => 2, 'field_name' => 'created_date',      'display_name' => 'Created Date',       'field_type' => 'Date'],
        ];

        // Contact Type 3 / General Newsletter fields
        $fieldsType3 = [
            ['contact_type_id' => 3, 'field_name' => 'contact_name',          'display_name' => 'Full Name',          'field_type' => 'Text'],
            ['contact_type_id' => 3, 'field_name' => 'phone_no',              'display_name' => 'Phone Number',          'field_type' => 'Text'],
            ['contact_type_id' => 3, 'field_name' => 'email',                 'display_name' => 'Email',                 'field_type' => 'Text'],
            ['contact_type_id' => 3, 'field_name' => 'email_subscription',    'display_name' => 'Email Subscription',    'field_type' => 'Text'],
            ['contact_type_id' => 3, 'field_name' => 'whatsapp_subscription', 'display_name' => 'Whatsapp Subscription', 'field_type' => 'Text'],
            ['contact_type_id' => 3, 'field_name' => 'created_date',      'display_name' => 'Created Date',       'field_type' => 'Date'],
        ];

        // Contact Type 4 / Community fields
        $fieldsType4 = [
            ['contact_type_id' => 4, 'field_name' => 'contact_name',          'display_name' => 'Full Name',          'field_type' => 'Text'],
            ['contact_type_id' => 4, 'field_name' => 'phone_no',              'display_name' => 'Phone Number',          'field_type' => 'Text'],
            ['contact_type_id' => 4, 'field_name' => 'email',                 'display_name' => 'Email',                 'field_type' => 'Text'],
            ['contact_type_id' => 4, 'field_name' => 'cansativa_newsletter',  'display_name' => 'Cansativa Newsletter',  'field_type' => 'Text'],
            ['contact_type_id' => 4, 'field_name' => 'community_user',        'display_name' => 'Community User',        'field_type' => 'Text'],
            ['contact_type_id' => 4, 'field_name' => 'email_subscription',    'display_name' => 'Email Subscription',    'field_type' => 'Text'],
            ['contact_type_id' => 4, 'field_name' => 'whatsapp_subscription', 'display_name' => 'Whatsapp Subscription', 'field_type' => 'Text'],
            ['contact_type_id' => 4, 'field_name' => 'likes',                  'display_name' => 'Likes',                'field_type' => 'Number'],
            ['contact_type_id' => 4, 'field_name' => 'comments',               'display_name' => 'Comments',             'field_type' => 'Number'],
            ['contact_type_id' => 4, 'field_name' => 'submissions',            'display_name' => 'Submissions',          'field_type' => 'Number'],
            ['contact_type_id' => 4, 'field_name' => 'created_date',           'display_name' => 'Join Date',            'field_type' => 'Date'],
        ];

        // Contact Type 5 / Pharmacy Database fields
        $fieldsType5 = [
            ['contact_type_id' => 5, 'field_name' => 'associated_pharmacy',    'display_name' => 'Associated Pharmacy',     'field_type' => 'Text'],
            ['contact_type_id' => 5, 'field_name' => 'contact_name',           'display_name' => 'Full Name',               'field_type' => 'Text'],
            ['contact_type_id' => 5, 'field_name' => 'email',                  'display_name' => 'Email',                   'field_type' => 'Text'],
            ['contact_type_id' => 5, 'field_name' => 'phone_no',               'display_name' => 'Phone Number',            'field_type' => 'Text'],
            ['contact_type_id' => 5, 'field_name' => 'address',                'display_name' => 'Address',               'field_type' => 'Text'],
            ['contact_type_id' => 5, 'field_name' => 'post_code',              'display_name' => 'Post Code',               'field_type' => 'Text'],
            ['contact_type_id' => 5, 'field_name' => 'city',                   'display_name' => 'City',                    'field_type' => 'Text'],
            ['contact_type_id' => 5, 'field_name' => 'country',                'display_name' => 'Country',                 'field_type' => 'Text'],
            ['contact_type_id' => 5, 'field_name' => 'email_subscription',    'display_name' => 'Email Subscription',    'field_type' => 'Text'],
            ['contact_type_id' => 5, 'field_name' => 'whatsapp_subscription', 'display_name' => 'Whatsapp Subscription', 'field_type' => 'Text'],
            ['contact_type_id' => 5, 'field_name' => 'created_date',      'display_name' => 'Created Date',       'field_type' => 'Date'],
        ];

        // Insert/Update all fields
        foreach (array_merge($fieldsType1, $fieldsType2, $fieldsType3, $fieldsType4, $fieldsType5) as $field) {
            ColumnMappings::updateOrCreate(
                ['contact_type_id' => $field['contact_type_id'], 'field_name' => $field['field_name']],
                $field
            );
        }
    }
}
