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
        Schema::create('history_exports', function (Blueprint $table) {
            $table->id();
            $table->string('contact_name')->nullable();
            $table->string('contact_type')->nullable();
            $table->string('applied_filters')->nullable();
            $table->bigInteger('amount_contacts')->nullable();
            $table->string('export_to')->nullable();
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
        Schema::dropIfExists('history_exports');
    }
};
