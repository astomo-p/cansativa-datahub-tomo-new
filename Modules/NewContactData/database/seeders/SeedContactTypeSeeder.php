<?php

namespace Modules\NewContactData\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedContactTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if(!DB::table('contact_types')->where('contact_type_name', 'PHARMACY')->exists()) {
            DB::table("contact_types")->insert([
            "contact_type_name" => "PHARMACY",
            "recipient_type" => "ipsum",
            "created_by" => 13,
            "created_date" => now(),
            "updated_by" => 13,
            "updated_date" => now(),
        ]);
        }
        
         if(!DB::table('contact_types')->where('contact_type_name', 'SUPPLIER')->exists()) {
         DB::table("contact_types")->insert([
            //"id" => 3,
            "contact_type_name" => "SUPPLIER",
            "recipient_type" => "ipsum",
            "created_by" => 13,
            "created_date" => now(),
            "updated_by" => 13,
            "updated_date" => now(),
        ]);
        }

         if(!DB::table('contact_types')->where('contact_type_name', 'GENERAL NEWSLETTER')->exists()) {
         DB::table("contact_types")->insert([
            //"id" => 3,
            "contact_type_name" => "GENERAL NEWSLETTER",
            "recipient_type" => "ipsum",
            "created_by" => 13,
            "created_date" => now(),
            "updated_by" => 13,
            "updated_date" => now(),
        ]);
        }

            if(!DB::table('contact_types')->where('contact_type_name', 'COMMUNITY')->exists()) {    
         DB::table("contact_types")->insert([
            //"id" => 3,
            "contact_type_name" => "COMMUNITY",
            "recipient_type" => "ipsum",
            "created_by" => 13,
            "created_date" => now(),
            "updated_by" => 13,
            "updated_date" => now(),
        ]);
        }

        if(!DB::table('contact_types')->where('contact_type_name', 'PHARMACY DATABASE')->exists()) {
         DB::table("contact_types")->insert([
            //"id" => 3,
            "contact_type_name" => "PHARMACY DATABASE",
            "recipient_type" => "ipsum",
            "created_by" => 13,
            "created_date" => now(),
            "updated_by" => 13,
            "updated_date" => now(),
        ]);
        }

         if(!DB::table('contact_types')->where('contact_type_name', 'SUBSCRIBER')->exists()) {
         DB::table("contact_types")->insert([
            //"id" => 3,
            "contact_type_name" => "SUBSCRIBER",
            "recipient_type" => "ipsum",
            "created_by" => 13,
            "created_date" => now(),
            "updated_by" => 13,
            "updated_date" => now(),
        ]);
        }
    }
}
