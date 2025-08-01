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
        Schema::connection('pgsql_b2b_shared')->table('wa_campaign_newsletter_stats', function (Blueprint $table) {
            $table->integer('total_failed')->default(0)->after('total_unsubscribed');
            $table->string('campaign_status', 20)->default('pending')->after('total_failed');
            $table->timestamp('started_at')->nullable()->after('campaign_status');
            $table->timestamp('completed_at')->nullable()->after('started_at');

            $table->index('campaign_status');
            $table->index('started_at');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql_b2b_shared')->table('wa_campaign_newsletter_stats', function (Blueprint $table) {
            $table->dropColumn(['total_failed', 'campaign_status', 'started_at', 'completed_at']);
        });
    }
};