<?php

namespace Modules\WhatsappNewsletter\Database\Seeders;

use Illuminate\Database\Seeder;

class WhatsappNewsletterDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $this->call([CleanupSeeder::class]);

        $this->call([
            WaNewsLetterSeeder::class,
            MessageTrackingSeeder::class,
        ]);
    }
}
