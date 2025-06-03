<?php

namespace Modules\ContactData\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeedPlacePublishSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       
        DB::table("place_publishs")->insert([
           "description" => "CANSATIVA Community",
                "created_by" => 1
        ]);
        DB::table("place_publishs")->insert([
           "description" => "CANSATIVA Website",
                "created_by" => 1
        ]);
       
    }
}
