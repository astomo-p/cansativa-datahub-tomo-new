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
        Schema::connection('pgsql_b2b_shared')->table('wa_campaign_newsletters', function (Blueprint $table) {
            $table->string('contact_flag')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql_b2b_shared')->table('wa_campaign_newsletters', function (Blueprint $table) {
            $table->dropColumn('contact_flag');
        });
    }
};
