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
        Schema::connection('pgsql_b2b_shared')->create('wa_campaign_newsletter_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('newsletter_id');
            $table->integer('batch_number');
            $table->integer('total_batches');
            $table->json('contact_ids');
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending');
            $table->integer('processed_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->string('timezone')->nullable();
            $table->timestamps();

            $table->foreign('newsletter_id')->references('id')->on('wa_campaign_newsletters')->onDelete('cascade');
            $table->index(['newsletter_id', 'batch_number']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql_b2b_shared')->dropIfExists('wa_campaign_newsletter_batches');
    }
};
