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
        Schema::create('contacts', function (Blueprint $table) {
           
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

            // BIT columns for cansativa_newsletter, community_user, whatsapp_subscription
            // BIT NOT NULL is the default for addColumn without nullable()
            //$table->addColumn('bit', 'cansativa_newsletter', ['length' => 1]);
            //$table->addColumn('bit', 'community_user', ['length' => 1]);
           // $table->addColumn('bit', 'whatsapp_subscription', ['length' => 1]);

           /*  $table->enum('cansativa_newsletter', [0, 1])->nullable(); // Assuming ENUM type
            $table->enum('community_user', [0, 1])->nullable(); // Assuming ENUM type
            $table->enum('whatsapp_subscription', [0, 1])->nullable(); // Assuming ENUM type
             */

            $table->boolean('cansativa_newsletter')->default(0); // Assuming BIT(1) or similar
            $table->boolean('community_user')->default(0); // Assuming BIT(1) or similar
            $table->boolean('whatsapp_subscription')->default(0); // Assuming BIT(1) or similar

            $table->bigInteger('contact_type_id');
            $table->bigInteger('contact_parent_id')->nullable()->default(null);

            $table->bigInteger('created_by');
            $table->timestamp('created_date'); // Matches TIMESTAMP NOT NULL
            $table->bigInteger('updated_by')->nullable();
            $table->timestamp('updated_date')->nullable(); // Matches TIMESTAMP NOT NULL

             // CONSTRAINT "fk_contacts_contact_parent" FOREIGN KEY ("contact_parent_id") REFERENCES "contacts" ("id") ON UPDATE CASCADE ON DELETE CASCADE
        /*     $table->foreign('contact_parent_id', 'fk_contacts_contact_parent')
                  ->references('id')->on('contacts')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
        
            // CONSTRAINT "fk_contacts_contact_type" FOREIGN KEY ("contact_type_id") REFERENCES "contact_types" ("id") ON UPDATE RESTRICT ON DELETE RESTRICT
            $table->foreign('contact_type_id', 'fk_contacts_contact_type')
                  ->references('id')->on('contact_types')
                  ->onUpdate('restrict')
                  ->onDelete('restrict');
            */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
