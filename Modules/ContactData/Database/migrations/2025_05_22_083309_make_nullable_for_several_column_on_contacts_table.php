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
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('contact_name')->nullable()->change();
            $table->string('contact_no')->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->string('post_code')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('country')->nullable()->change();
            $table->string('contact_person')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->decimal('amount_purchase', 10, 2)->nullable()->default(0)->change();
            $table->decimal('total_purchase', 10, 2)->nullable()->default(0)->change();
            $table->decimal('average_purchase', 10, 2)->nullable()->default(0)->change();
            $table->date('last_purchase_date')->nullable()->change();
            $table->boolean('cansativa_newsletter')->nullable()->default(0)->change();
            $table->boolean('community_user')->nullable()->default(0)->change();
            $table->boolean('whatsapp_subscription')->nullable()->default(0)->change();
            $table->bigInteger('contact_type_id')->nullable()->change();
            $table->bigInteger('contact_parent_id')->nullable()->default(null)->change();
            $table->bigInteger('created_by')->nullable()->change();
            $table->bigInteger('updated_by')->nullable()->change();
            $table->timestamp('updated_date')->nullable()->change();
            $table->timestamp('last_message_at')->nullable()->after('updated_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('contact_name')->nullable(false)->change();
            $table->string('contact_no')->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
            $table->string('post_code')->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
            $table->string('country')->nullable(false)->change();
            $table->string('contact_person')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->decimal('amount_purchase', 10, 2)->nullable(false)->default(0)->change();
            $table->decimal('total_purchase', 10, 2)->nullable(false)->default(0)->change();
            $table->decimal('average_purchase', 10, 2)->nullable(false)->default(0)->change();
            $table->date('last_purchase_date')->nullable(false)->change();
            $table->boolean('cansativa_newsletter')->nullable(false)->default(0)->change();
            $table->boolean('community_user')->nullable(false)->default(0)->change();
            $table->boolean('whatsapp_subscription')->nullable(false)->default(0)->change();
            $table->bigInteger('contact_type_id')->nullable(false)->change();
            $table->bigInteger('contact_parent_id')->nullable()->default(null)->change();
            $table->bigInteger('created_by')->nullable(false)->change();
            $table->bigInteger('updated_by')->nullable()->change();
            $table->timestamp('updated_date')->nullable()->change();
            $table->dropColumn('last_message_at');
        });
    }
};