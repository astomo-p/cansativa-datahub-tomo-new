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
        Schema::create('wa_campaign_filter_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('contact_type_id');
            $table->json('filters');
            $table->timestamps();

            // Foreign key constraint
            // $table->foreign('contact_type_id')->references('id')->on('contact_types')->onDelete('cascade');

            $table->index('contact_type_id');
            $table->index(['contact_type_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_campaign_filter_presets');
    }
};
