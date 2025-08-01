<?php

namespace Modules\WhatsappNewsletter\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\WhatsappNewsletter\Models\WaCampaignMessageTracking;
use Modules\WhatsappNewsletter\Models\WaNewsLetter;

class CleanupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Cleaning up existing data...');

        WaCampaignMessageTracking::truncate();
        $this->command->info('Message tracking data cleaned.');

        WaNewsLetter::truncate();
        $this->command->info('Newsletter campaigns cleaned.');

        $this->command->info('Cleanup completed successfully!');
    }
}
