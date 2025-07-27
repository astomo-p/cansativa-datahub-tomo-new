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
            $table->bigInteger('contact_type_id')->nullable();
            $table->bigInteger('amount_of_contacts')->nullable();
            $table->boolean('is_deleted')->default(false);
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
