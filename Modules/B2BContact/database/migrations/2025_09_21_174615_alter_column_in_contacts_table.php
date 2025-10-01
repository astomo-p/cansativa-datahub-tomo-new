<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix old null values
        DB::connection('pgsql')->table('contacts')
            ->whereNull('email_subscription')
            ->update(['email_subscription' => false]);

        DB::connection('pgsql_b2b')->table('contacts')
            ->whereNull('email_subscription')
            ->update(['email_subscription' => false]);

        Schema::connection('pgsql')->table('contacts', function (Blueprint $table) {
            $table->boolean('email_subscription')->default(false)->change();
        });

        Schema::connection('pgsql_b2b')->table('contacts', function (Blueprint $table) {
            $table->boolean('email_subscription')->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
