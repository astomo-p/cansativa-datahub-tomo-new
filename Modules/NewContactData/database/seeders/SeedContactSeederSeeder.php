<?php

namespace Modules\NewContactData\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedContactSeederSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('contacts')->insert([
            // "id" => 5,
            'contact_name' => Str::random(10),
            'contact_no' => rand(111111111, 9999999999),
            'address' => Str::random(20),
            'post_code' => rand(11111, 99999),
            'city' => Str::random(10),
            'country' => Str::random(10),
            'contact_person' => Str::random(10),
            'email' => Str::random(10).'@cansativa.ge',
            'phone_no' => rand(111111111, 9999999999),
            'amount_purchase' => 0,
            'total_purchase' => 0,
            'average_purchase' => 0,
            'last_purchase_date' => now(),
            'cansativa_newsletter' => 0,
            'community_user' => 0,
            'whatsapp_subscription' => 0,
            'contact_type_id' => 1,
            'contact_parent_id' => null,
            'created_by' => 1,
            'created_date' => now(),
            'updated_by' => 1,
            'updated_date' => now(),
        ]);
        DB::table('contacts')->insert([
            // "id" => 6,
            'contact_name' => Str::random(10),
            'contact_no' => rand(111111111, 9999999999),
            // "vat_id" => rand(11111111, 999999999),
            'address' => Str::random(20),
            'post_code' => rand(11111, 99999),
            'city' => Str::random(10),
            'country' => Str::random(10),
            'contact_person' => Str::random(10),
            'email' => Str::random(10).'@cansativa.ge',
            'phone_no' => rand(111111111, 9999999999),
            'amount_purchase' => 0,
            'total_purchase' => 0,
            'average_purchase' => 0,
            'last_purchase_date' => now(),
            'cansativa_newsletter' => 0,
            'community_user' => 0,
            'whatsapp_subscription' => 0,
            'contact_type_id' => 2,
            'contact_parent_id' => null,
            'created_by' => 1,
            'created_date' => now(),
            'updated_by' => 1,
            'updated_date' => now(),
        ]);
        DB::table('contacts')->insert([
            // "id" => 6,
            'contact_name' => Str::random(10),
            'contact_no' => rand(111111111, 9999999999),
            'address' => Str::random(20),
            'post_code' => rand(11111, 99999),
            'city' => Str::random(10),
            'country' => Str::random(10),
            'contact_person' => Str::random(10),
            'email' => Str::random(10).'@cansativa.ge',
            'phone_no' => rand(111111111, 9999999999),
            'amount_purchase' => 0,
            'total_purchase' => 0,
            'average_purchase' => 0,
            'last_purchase_date' => now(),
            'cansativa_newsletter' => 0,
            'community_user' => 0,
            'whatsapp_subscription' => 0,
            'contact_type_id' => 3,
            'contact_parent_id' => null,
            'created_by' => 1,
            'created_date' => now(),
            'updated_by' => 1,
            'updated_date' => now(),
        ]);
        DB::table('contacts')->insert([
            // "id" => 6,
            'contact_name' => Str::random(10),
            'contact_no' => rand(111111111, 9999999999),
            'address' => Str::random(20),
            'post_code' => rand(11111, 99999),
            'city' => Str::random(10),
            'country' => Str::random(10),
            'contact_person' => Str::random(10),
            'email' => Str::random(10).'@cansativa.ge',
            'phone_no' => rand(111111111, 9999999999),
            'amount_purchase' => 0,
            'total_purchase' => 0,
            'average_purchase' => 0,
            'last_purchase_date' => now(),
            'cansativa_newsletter' => 0,
            'community_user' => 0,
            'whatsapp_subscription' => 0,
            'contact_type_id' => 4,
            'contact_parent_id' => null,
            'created_by' => 1,
            'created_date' => now(),
            'updated_by' => 1,
            'updated_date' => now(),
        ]);
    }
}
