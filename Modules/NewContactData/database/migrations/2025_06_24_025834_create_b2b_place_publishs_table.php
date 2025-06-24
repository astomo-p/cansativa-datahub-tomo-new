<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    /**
     * connection
     */
    protected $connection = 'pgsql_b2b';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('place_publishs', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->timestamp('updated_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b2b_place_publishs');
    }
};
