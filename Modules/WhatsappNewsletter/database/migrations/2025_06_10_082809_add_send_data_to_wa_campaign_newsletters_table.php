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
            $table->string('send_type')->nullable();
            $table->integer('batch_amount')->nullable();
            $table->integer('interval_days')->nullable();
            $table->integer('interval_hours')->nullable();
            $table->time('send_message_start_hours')->nullable();
            $table->time('send_message_end_hours')->nullable();
            $table->string('timezone')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_campaign_newsletters', function (Blueprint $table) {
            $table->dropColumn([
                'send_type',
                'batch_amount',
                'interval_days',
                'interval_hours',
                'send_message_start_hours',
                'send_message_end_hours',
                'timezone'
            ]);
        });
    }
};
