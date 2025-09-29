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
        Schema::connection('pgsql')->table('contacts', function (Blueprint $table) {
            $table->timestamp('last_export_at')->nullable();
        });

        Schema::connection('pgsql_b2b')->table('contacts', function (Blueprint $table) {
            $table->timestamp('last_export_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql')->table('contacts', function (Blueprint $table) {
            $table->dropColumn('last_export_at');
        });

        Schema::connection('pgsql_b2b')->table('contacts', function (Blueprint $table) {
            $table->dropColumn('last_export_at');
        });
    }
};
