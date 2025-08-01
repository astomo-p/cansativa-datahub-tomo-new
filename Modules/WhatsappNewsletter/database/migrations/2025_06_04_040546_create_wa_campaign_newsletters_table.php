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
        Schema::create('wa_campaign_newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sending_status')->default('scheduled');
            $table->string('campaign_status')->default('draft');
            $table->datetime('scheduled_at')->nullable();
            $table->datetime('sent_at')->nullable();
            $table->unsignedBigInteger('contact_type_id');
            $table->unsignedBigInteger('wa_template_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            // $table->foreignId('created_by')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_campaign_newsletters');
    }
};
