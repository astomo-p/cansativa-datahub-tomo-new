<?php

namespace Modules\Campaign\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Campaign\Models\Campaign; 

class CampaignDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $this->call([]);
        Campaign::factory()->count(50)->create();

        $this->command->info('Campaigns seeded!');
    }
}
