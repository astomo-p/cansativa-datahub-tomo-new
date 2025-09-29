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
        Schema::create('wa_template_attributes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('template_id')->nullable();
            $table->bigInteger('contact_type_id')->nullable();
            $table->char('vat_variable',255)->nullable();
            $table->char('vat_default',255)->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->bigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent(); // Matches TIMESTAMP NOT NULL
            $table->bigInteger('updated_by')->nullable();
            $table->timestamp('updated_date')->nullable(); // Matches TIMESTAMP NOT NULL
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_template_attributes');
    }
};
