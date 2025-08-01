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
       /*  Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('contact_id')->nullable();
            $table->string('file_name')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent(); // Matches TIMESTAMP NOT NULL
            $table->bigInteger('updated_by')->nullable();
            $table->timestamp('updated_date')->nullable(); // Matches TIMESTAMP NOT NULL
        }); */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //Schema::dropIfExists('files');
    }
};
