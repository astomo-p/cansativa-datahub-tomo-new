<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_b2b';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('website')->nullable(); 
            $table->string('open_hours')->nullable(); 
            $table->string('price_list_new')->nullable(); 
            $table->string('region_name')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('website');
            $table->dropColumn('open_hours');
            $table->dropColumn('price_list_new');
            $table->dropColumn('region_name');
        });
    }
};
