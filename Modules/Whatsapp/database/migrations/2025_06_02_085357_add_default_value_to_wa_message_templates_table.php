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
        Schema::table('wa_message_templates', function (Blueprint $table) {
            $table->json('header_default_value')->nullable();
            $table->json('body_default_value')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_message_templates', function (Blueprint $table) {
            $table->dropColumn(['header_default_value', 'body_default_value']);
        });
    }
};
