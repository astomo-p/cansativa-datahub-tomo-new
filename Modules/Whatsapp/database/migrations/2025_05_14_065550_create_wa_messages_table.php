<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_b2b_shared';

    public function up(): void
    {
        Schema::create('wa_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('wamid', 100);
            $table->unsignedBigInteger('contact_id');
            $table->enum('status', ['accepted', 'sent', 'delivered', 'read', 'failed']);
            $table->string('error_code', 10)->nullable();
            $table->text('error_message')->nullable();
            $table->tinyInteger('direction');
            $table->enum('type', [
                'text',
                'reply',
                'button',
                'image',
                'vcard',
                'audio',
                'video',
                'file',
                'template',
                'location',
                'custom',
                'unsupported'
            ]);
            $table->string('media_file', 255)->nullable();
            $table->text('header')->nullable();
            $table->text('body')->nullable();
            $table->text('body_text')->nullable();
            $table->text('footer')->nullable();
            $table->text('buttons')->nullable();
            $table->unsignedBigInteger('scenario_session_id')->nullable();
            $table->string('language', 5)->nullable();
            $table->string('template_name', 50)->nullable();
            $table->string('template_language', 5)->nullable();
            $table->timestamps();

            // $table->foreign('contact_id')
            //     ->references('id')
            //     ->on('contacts')
            //     ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_messages');
    }
};
