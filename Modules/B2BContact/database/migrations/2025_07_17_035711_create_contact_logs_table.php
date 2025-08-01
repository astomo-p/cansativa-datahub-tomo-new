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
        Schema::create('contact_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('contact_flag');
            $table->integer('contact_id');
            $table->integer('campaign_id')->nullable();
            $table->json('description');
            $table->string('creator_name');
            $table->string('creator_email');
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('updated_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_logs');
    }
};
