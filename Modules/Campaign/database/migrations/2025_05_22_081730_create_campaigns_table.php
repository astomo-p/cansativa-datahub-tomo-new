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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('contact_type_id');
            $table->string('recipient_type')->nullable(); // b2b, b2c, etc.
            $table->string('campaign_name');
            $table->bigInteger('brevo_campaign_id')->nullable();
            $table->string('filters')->nullable();
            $table->string('channel')->nullable();
            $table->bigInteger('created_by');
            $table->timestamp('created_date');
            $table->bigInteger('updated_by')->nullable();
            $table->timestamp('updated_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
