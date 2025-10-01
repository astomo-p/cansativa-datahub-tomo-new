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
        ->table('contacts')
        ->whereNull('amount_purchase')
        ->update(['amount_purchase' => 0]);
        
        DB::connection('pgsql_b2b')
        ->table('contacts')
        ->whereNull('amount_purchase')
        ->update(['amount_purchase' => 0]);
        
        Schema::connection('pgsql')->table('contacts', function (Blueprint $table) {
            $table->integer('amount_purchase')->default(0)->nullable(false)->change();
        });

        Schema::connection('pgsql_b2b')->table('contacts', function (Blueprint $table) {
            $table->integer('amount_purchase')->default(0)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
