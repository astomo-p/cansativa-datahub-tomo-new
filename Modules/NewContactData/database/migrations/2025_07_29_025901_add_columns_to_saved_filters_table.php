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
        Schema::table('saved_filters', function (Blueprint $table) {
            $table->boolean('is_frequence')->nullable();
            $table->boolean('is_apply_freq')->nullable();
            $table->string('frequency_cap')->nullable();
            $table->string('newsletter_channel')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_filters', function (Blueprint $table) {
            
        });
    }
};
