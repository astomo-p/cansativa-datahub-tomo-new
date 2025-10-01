<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('pgsql')->table('saved_filters', function () {
            DB::statement('ALTER TABLE saved_filters ALTER COLUMN applied_filters DROP NOT NULL');
        });
        Schema::connection('pgsql_b2b')->table('saved_filters', function () {
            DB::statement('ALTER TABLE saved_filters ALTER COLUMN applied_filters DROP NOT NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
