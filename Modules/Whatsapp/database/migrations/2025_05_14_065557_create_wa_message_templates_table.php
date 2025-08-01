<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_b2b_shared';

    public function up(): void
    {
        Schema::create('wa_message_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('fbid', 100);
            $table->string('name', 255);
            $table->string('language', 5)->nullable();
            $table->text('components')->nullable();
            $table->string('parameter_format', 100)->nullable();
            $table->string('status', 20)->nullable();
            $table->string('category', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_message_templates');
    }
};
