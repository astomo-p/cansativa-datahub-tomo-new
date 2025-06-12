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
        DB::table("contact_types")->insert([
            //"id" => 3,
            "contact_type_name" => "PHARMACY",
            "recipient_type" => "ipsum",
            "created_by" => 13,
            "created_date" => now(),
            "updated_by" => 13,
            "updated_date" => now(),
        ]);
         DB::table("contact_types")->insert([
            //"id" => 3,
            "contact_type_name" => "SUPPLIER",
            "recipient_type" => "ipsum",
            "created_by" => 13,
            "created_date" => now(),
            "updated_by" => 13,
            "updated_date" => now(),
        ]);
         DB::table("contact_types")->insert([
            //"id" => 3,
            "contact_type_name" => "GENERAL NEWSLETTER",
            "recipient_type" => "ipsum",
            "created_by" => 13,
            "created_date" => now(),
            "updated_by" => 13,
            "updated_date" => now(),
        ]);
         DB::table("contact_types")->insert([
            //"id" => 3,
            "contact_type_name" => "COMMUNITY",
            "recipient_type" => "ipsum",
            "created_by" => 13,
            "created_date" => now(),
            "updated_by" => 13,
            "updated_date" => now(),
        ]);
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
}
