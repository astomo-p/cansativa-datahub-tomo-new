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
            $table->text('body')->nullable();
            $table->string('header_type')->nullable();
            $table->text('header_content')->nullable();
            $table->string('button_type')->nullable();
            $table->string('button_action')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->string('button_phone_number')->nullable();
            $table->text('button_footer_text')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_message_templates', function (Blueprint $table) {
            $table->dropColumn([
                'body',
                'header_type',
                'header_content',
                'button_type',
                'button_action',
                'button_text',
                'button_url',
                'button_phone_number',
                'button_footer_text'
            ]);
        });
    }
};
