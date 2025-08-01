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
        Schema::create('wa_campaign_newsletter_stats', function (Blueprint $table) {
            $table->id();
            $table->integer('total_sent')->default(0);
            $table->integer('total_delivered')->default(0);
            $table->integer('total_opened')->default(0);
            $table->integer('total_clicks')->default(0);
            $table->integer('total_unsubscribed')->default(0);
            $table->unsignedBigInteger('campaign_id');
            $table->timestamps();
            // $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_campaign_newsletter_stats');
    }
};
