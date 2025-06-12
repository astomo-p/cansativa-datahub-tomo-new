<?php

namespace Modules\NewContactData\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\NewContactData\Database\Seeders\SeedPlacePublishSeeder;
use Modules\NewContactData\Database\Seeders\SeedContactSeederSeeder;
use Modules\NewContactData\Database\Seeders\SeedContactTypeSeeder;

class NewContactDataDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $this->call([
            SeedPlacePublishSeeder::class,
            SeedContactTypeSeeder::class,
            SeedContactSeeder::class,
        ]);
    }
}
