<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        DB::connection('pgsql')
        ->table('saved_filters')
        ->whereNull('amount_of_contacts')
        ->update(['amount_of_contacts' => 0]);
        
        DB::connection('pgsql_b2b')
        ->table('saved_filters')
        ->whereNull('amount_of_contacts')
        ->update(['amount_of_contacts' => 0]);

        Schema::connection('pgsql')->table('saved_filters', function (Blueprint $table) {
            $table->bigInteger('amount_of_contacts')->default(0)->nullable(false)->change();
        });

        Schema::connection('pgsql_b2b')->table('saved_filters', function (Blueprint $table) {
            $table->bigInteger('amount_of_contacts')->default(0)->nullable(false)->change();
        });

        DB::connection('pgsql')
        ->table('history_exports')
        ->whereNull('amount_of_contacts')
        ->update(['amount_of_contacts' => 0]);

        DB::connection('pgsql')
        ->table('history_exports')
        ->whereNull('amount_contacts')
        ->update(['amount_contacts' => 0]);
        
        DB::connection('pgsql_b2b')
        ->table('history_exports')
        ->whereNull('amount_of_contacts')
        ->update(['amount_of_contacts' => 0]);

        Schema::connection('pgsql')->table('history_exports', function (Blueprint $table) {
            $table->bigInteger('amount_of_contacts')->default(0)->nullable(false)->change();
            $table->bigInteger('amount_contacts')->default(0)->nullable(false)->change();
        });

        Schema::connection('pgsql_b2b')->table('history_exports', function (Blueprint $table) {
            $table->bigInteger('amount_of_contacts')->default(0)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
