<?php

namespace Modules\NewContactData\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Insert ke contact_types
        $contactTypeId = DB::table('contact_types')->insertGetId([
            // "id" => 3,
            'contact_type_name' => 'PHARMACY',
            'recipient_type' => 'ipsum',
            'created_by' => 13,
            'created_date' => now(),
            'updated_by' => 13,
            'updated_date' => now(),
        ]);

        // Insert ke contacts
        DB::table('contacts')->insert([
            'contact_name' => 'Tanaka Kujira',
            'contact_no' => 'CT-001',
            'address' => '123 Sakura Street',
            'post_code' => '10001',
            'city' => 'Tokyo',
            'country' => 'Japan',
            'contact_person' => 'Tanaka Kujira',
            'email' => 'tanakakujira491@gmail.com',
            'phone_no' => '+81-90-1234-5678',
            'amount_purchase' => 1000.00,
            'total_purchase' => 10000.00,
            'average_purchase' => 500.00,
            'last_purchase_date' => $now->toDateString(),
            'cansativa_newsletter' => true,
            'community_user' => false,
            'whatsapp_subscription' => true,
            'contact_type_id' => $contactTypeId,
            'contact_parent_id' => null,
            'created_by' => 0,
            'created_date' => $now,
            'updated_by' => null,
            'updated_date' => null,
        ]);
    }
}
