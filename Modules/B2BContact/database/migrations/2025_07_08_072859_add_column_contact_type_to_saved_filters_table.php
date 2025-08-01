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
        Schema::table('saved_filters', function (Blueprint $table) {
            $table->integer('contact_type_id')->after('filter_name')->nullable();
            $table->bigInteger('amount_of_contacts')->after('contact_type_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_filters', function (Blueprint $table) {
            $table->dropColumn('contact_type');
            $table->dropColumn('amount_of_contacts');
        });
    }
};
