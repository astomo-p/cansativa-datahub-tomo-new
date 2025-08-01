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
        Schema::create('campaign_contacts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('contact_id');
            $table->string('contact_type')->nullable(); // b2b, b2c, etc.
            $table->bigInteger('brevo_id');
            $table->bigInteger('campaign_id');
            $table->string('status')->nullable()->index();
            $table->boolean('status_message');
            $table->bigInteger('created_by');
            $table->timestamp('created_date');
            $table->bigInteger('updated_by')->nullable();
            $table->timestamp('updated_date')->nullable();

            // Foreign keys
            $table->foreign('campaign_id')->references('id')->on('campaigns')->restrictOnUpdate()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_contacts');
    }
};
