<?php

namespace Modules\ContactData\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ContactData\Database\Seeders\SeedPlacePublishSeeder;
use Modules\ContactData\Database\Seeders\SeedContactSeeder;
use Modules\ContactData\Database\Seeders\SeedContactTypeSeeder;

class ContactDataDatabaseSeeder extends Seeder
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
