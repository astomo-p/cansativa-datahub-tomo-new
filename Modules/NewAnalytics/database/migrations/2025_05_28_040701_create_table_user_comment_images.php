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
        Schema::create('user_comment_images', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_comment_id');
            $table->char('images_comment',256);
            $table->bigInteger('created_by');
            $table->dateTime('created_date')->useCurrent();
            $table->bigInteger('updated_by')->nullable();
            $table->dateTime('updated_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('');
    }
};
