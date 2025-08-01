<?php

namespace Modules\B2BContact\Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class B2bContactTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! DB::connection('pgsql_b2b')->table('contact_types')->where('contact_type_name', 'PHARMACY')->exists()) {
            DB::connection('pgsql_b2b')->table('contact_types')->insert([
                'contact_type_name' => 'PHARMACY',
                'recipient_type' => 'ipsum',
                'created_by' => 13,
                'created_date' => now(),
                'updated_by' => 13,
                'updated_date' => now(),
            ]);
        }

        if (! DB::connection('pgsql_b2b')->table('contact_types')->where('contact_type_name', 'SUPPLIER')->exists()) {
            DB::connection('pgsql_b2b')->table('contact_types')->insert([
                // "id" => 3,
                'contact_type_name' => 'SUPPLIER',
                'recipient_type' => 'ipsum',
                'created_by' => 13,
                'created_date' => now(),
                'updated_by' => 13,
                'updated_date' => now(),
            ]);
        }

        if (! DB::connection('pgsql_b2b')->table('contact_types')->where('contact_type_name', 'GENERAL NEWSLETTER')->exists()) {
            DB::connection('pgsql_b2b')->table('contact_types')->insert([
                // "id" => 3,
                'contact_type_name' => 'GENERAL NEWSLETTER',
                'recipient_type' => 'ipsum',
                'created_by' => 13,
                'created_date' => now(),
                'updated_by' => 13,
                'updated_date' => now(),
            ]);
        }

        if (! DB::connection('pgsql_b2b')->table('contact_types')->where('contact_type_name', 'COMMUNITY')->exists()) {
            DB::connection('pgsql_b2b')->table('contact_types')->insert([
                // "id" => 3,
                'contact_type_name' => 'COMMUNITY',
                'recipient_type' => 'ipsum',
                'created_by' => 13,
                'created_date' => now(),
                'updated_by' => 13,
                'updated_date' => now(),
            ]);
        }

        if (! DB::connection('pgsql_b2b')->table('contact_types')->where('contact_type_name', 'PHARMACY DATABASE')->exists()) {
            DB::connection('pgsql_b2b')->table('contact_types')->insert([
                // "id" => 3,
                'contact_type_name' => 'PHARMACY DATABASE',
                'recipient_type' => 'ipsum',
                'created_by' => 13,
                'created_date' => now(),
                'updated_by' => 13,
                'updated_date' => now(),
            ]);
        }

        if (! DB::connection('pgsql_b2b')->table('contact_types')->where('contact_type_name', 'SUBSCRIBER')->exists()) {
            DB::connection('pgsql_b2b')->table('contact_types')->insert([
                // "id" => 3,
                'contact_type_name' => 'SUBSCRIBER',
                'recipient_type' => 'ipsum',
                'created_by' => 13,
                'created_date' => now(),
                'updated_by' => 13,
                'updated_date' => now(),
            ]);
        }
    }
}
