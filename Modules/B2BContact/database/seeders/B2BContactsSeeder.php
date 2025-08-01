<?php

namespace Modules\B2BContact\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\B2BContact\Models\B2BContacts;

class B2BContactsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        B2BContacts::factory()->count(20)->create();
    }
}
