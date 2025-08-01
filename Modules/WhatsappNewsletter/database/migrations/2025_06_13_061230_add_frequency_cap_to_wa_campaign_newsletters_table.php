<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_b2b_shared';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wa_campaign_newsletters', function (Blueprint $table) {
            $table->boolean('frequency_cap_enabled')->default(false);
            $table->integer('frequency_cap_limit')->nullable();
            $table->integer('frequency_cap_period')->nullable();
            $table->string('frequency_cap_unit', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_campaign_newsletters', function (Blueprint $table) {
            $table->dropColumn(['frequency_cap_enabled', 'frequency_cap_limit', 'frequency_cap_period', 'frequency_cap_unit']);
        });
    }
};
