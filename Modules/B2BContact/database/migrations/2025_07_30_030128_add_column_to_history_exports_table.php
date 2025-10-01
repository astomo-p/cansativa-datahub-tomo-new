<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_b2b';
    
    public function up(): void
    {
        Schema::table('history_exports', function (Blueprint $table) {
            $table->boolean('is_frequence')->nullable();
            $table->boolean('is_apply_freq')->nullable();
            $table->json('frequency_cap')->nullable();
            $table->json('newsletter_channel')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('history_exports', function (Blueprint $table) {
            
        });
    }
};
