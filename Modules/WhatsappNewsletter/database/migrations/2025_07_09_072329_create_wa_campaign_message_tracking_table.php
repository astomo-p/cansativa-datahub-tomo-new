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
        Schema::connection('pgsql_b2b_shared')->create('wa_campaign_message_tracking', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('contact_id');
            $table->string('contact_flag', 10); // b2b or b2c
            $table->string('message_id')->nullable(); // WhatsApp message ID
            $table->enum('status', ['sent', 'delivered', 'read', 'failed'])->default('sent');
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'contact_id']);
            $table->index(['campaign_id', 'contact_flag']);
            $table->index(['campaign_id', 'status']);
            $table->index('message_id');
            $table->index('contact_flag');
            $table->index('status');

            $table->foreign('campaign_id')->references('id')->on('wa_campaign_newsletters')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql_b2b_shared')->dropIfExists('wa_campaign_message_tracking');
    }
};