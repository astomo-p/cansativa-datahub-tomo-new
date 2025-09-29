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
        Schema::create('user_saved_posts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('post_id')->nullable();
            //$table->addColumn('bit', 'is_like', ['length' => 1])->nullable()->default(DB::raw('NULL::"BIT"'));
            //$table->addColumn('bit', 'is_saved', ['length' => 1])->nullable()->default(DB::raw('NULL::"BIT"'));
            $table->boolean('is_like')->nullable();
            $table->boolean('is_saved')->nullable();
            $table->bigInteger('rank')->nullable();
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
        Schema::dropIfExists('user_saved_posts');
    }
};
