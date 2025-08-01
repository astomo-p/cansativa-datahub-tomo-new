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
            $table->string('fbid', 100)->nullable()->change();
            $table->string('parameter_format', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_message_templates', function (Blueprint $table) {
            $table->string('fbid', 100)->nullable(false)->change();
            $table->string('parameter_format', 100)->nullable(false)->change();
        });
    }
};
