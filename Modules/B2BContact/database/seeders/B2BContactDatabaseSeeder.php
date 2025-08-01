<?php

namespace Modules\B2BContact\Database\Seeders;

use Illuminate\Database\Seeder;

class B2BContactDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            B2bContactTypesSeeder::class,
            B2BContactsSeeder::class,
        ]);
    }
}
