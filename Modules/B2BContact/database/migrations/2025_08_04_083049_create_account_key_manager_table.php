<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_b2b';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_key_managers', function (Blueprint $table) {
            $table->id();
            $table->string('manager_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('auto_reply')->nullable();
            $table->bigInteger('wa_template_id')->nullable();
            $table->string('message_template_name')->nullable();
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
        Schema::dropIfExists('account_key_managers');
    }
};
