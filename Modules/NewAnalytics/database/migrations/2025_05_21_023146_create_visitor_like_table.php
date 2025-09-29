<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->char('title',256);
            $table->char('status',256);
            $table->bigInteger('published_by');
            $table->date('publication_date')->nullable();
            $table->string('content')->nullable();
            //$table->addColumn('bit', 'comment_setting', ['length' => 1])->nullable()->default(DB::raw('NULL::"BIT"'));
            //$table->addColumn('bit', 'like_setting', ['length' => 1])->nullable()->default(DB::raw('NULL::"BIT"'));
            //$table->addColumn('bit', 'sponsored_post', ['length' => 1])->nullable()->default(DB::raw('NULL::"BIT"'));
            $table->boolean('comment_setting')->nullable();
            $table->boolean('like_setting')->nullable();
            $table->boolean('sponsored_post')->nullable();
            $table->char('tags',256)->nullable();
            $table->bigInteger('user_likes')->nullable();
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
        Schema::dropIfExists('visitor_like');
    }
};
