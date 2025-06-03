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
        Schema::create('user_comments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('comment');
            $table->char('status',256);
            $table->bigInteger('commentable_id')->nullable();
            $table->char('commentable_type', 256)->nullable();
            $table->bigInteger('created_by');
            $table->bigInteger('updated_by');
            $table->datetime('created_date');
            $table->datetime('updated_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_comments');
    }
};
