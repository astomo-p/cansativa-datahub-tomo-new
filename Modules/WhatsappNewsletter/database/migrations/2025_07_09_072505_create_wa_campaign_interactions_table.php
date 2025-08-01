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
        Schema::connection('pgsql_b2b_shared')->create('wa_campaign_interactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('contact_id');
            $table->string('contact_flag', 10); // b2b or b2c
            $table->enum('interaction_type', ['click', 'unsubscribe', 'reply']);
            $table->string('button_text')->nullable();
            $table->text('clicked_url')->nullable();
            $table->json('interaction_data')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'interaction_type']);
            $table->index(['campaign_id', 'contact_flag']);
            $table->index(['contact_id', 'interaction_type']);
            $table->index('contact_flag');
            $table->index('interaction_type');

            $table->foreign('campaign_id')->references('id')->on('wa_campaign_newsletters')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql_b2b_shared')->dropIfExists('wa_campaign_interactions');
    }
};