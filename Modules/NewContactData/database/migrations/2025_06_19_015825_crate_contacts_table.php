<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
         Schema::connection('pgsql_b2b')->create('contacts', function (Blueprint $table) {
           
            $table->id(); // Explicitly defining PK and type
            $table->string('contact_name');
            $table->string('contact_no');
            $table->text('address');
            $table->string('post_code');
            $table->string('city');
            $table->string('country');
            $table->string('contact_person');
            $table->string('email');
            $table->string('phone_no');
            $table->decimal('amount_purchase', 10, 2)->default(0); // Assuming NUMERIC(10,2) or similar
            $table->decimal('total_purchase', 10, 2)->default(0);
            $table->decimal('average_purchase', 10, 2)->default(0);
            $table->date('last_purchase_date');

            $table->boolean('cansativa_newsletter')->default(0); // Assuming BIT(1) or similar
            $table->boolean('community_user')->default(0); // Assuming BIT(1) or similar
            $table->boolean('whatsapp_subscription')->default(0); // Assuming BIT(1) or similar

            $table->bigInteger('contact_type_id');
            $table->bigInteger('contact_parent_id')->nullable()->default(null);

            $table->bigInteger('created_by');
            $table->timestamp('created_date'); // Matches TIMESTAMP NOT NULL
            $table->bigInteger('updated_by')->nullable();
            $table->timestamp('updated_date')->nullable(); // Matches TIMESTAMP NOT NULL

           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
