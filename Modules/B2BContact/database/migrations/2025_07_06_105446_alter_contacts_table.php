<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_b2b';
    /**
     * Run the migrations.
     */
    public function up(): void {
        DB::statement('ALTER TABLE contacts ALTER COLUMN contact_person TYPE integer USING (contact_person)::integer');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
    }
};
