<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_b2b_shared';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wa_message_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('assigned_to');
            $table->unsignedBigInteger('assigned_by');
            $table->dateTime('assigned_date');
            $table->timestamps();

            // $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            // $table->foreign('assigned_to')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('assigned_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_message_assignments');
    }
};
