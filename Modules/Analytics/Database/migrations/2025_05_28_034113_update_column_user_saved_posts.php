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
        Schema::table('user_saved_posts', function (Blueprint $table) {
            $table->char('user_id', 128)->nullable()->change();
            $table->boolean('is_user')->nullable();
            $table->boolean('is_views')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('', function (Blueprint $table) {
            
        });
    }
};
