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

        // Drop the table if it already exists
       // Schema::connection('pgsql_b2b')->dropIfExists('contacts');

       if (!Schema::connection('pgsql_b2b')->hasTable('contacts')) {

        // Create the contacts table with the specified columns and types
          Schema::connection('pgsql_b2b')->create('contacts', function (Blueprint $table) {
           
            $table->id(); // Explicitly defining PK and type
            $table->string('contact_name')->nullable();
            $table->string('contact_no')->nullable();
            $table->text('address')->nullable();
            $table->string('post_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_no')->nullable();
            $table->decimal('amount_purchase', 10, 2)->default(0); // Assuming NUMERIC(10,2) or similar
            $table->decimal('total_purchase', 10, 2)->default(0);
            $table->decimal('average_purchase', 10, 2)->default(0);
            $table->date('last_purchase_date')->nullable();

            $table->boolean('cansativa_newsletter')->default(0); // Assuming BIT(1) or similar
            $table->boolean('community_user')->default(0); // Assuming BIT(1) or similar
            $table->boolean('whatsapp_subscription')->default(0); // Assuming BIT(1) or similar

            $table->bigInteger('contact_type_id')->nullable();
            $table->bigInteger('contact_parent_id')->nullable()->default(null);

            $table->string('status')->nullable();
            $table->boolean('is_deleted')->default(false);
             $table->string('message_language', 4)->nullable();
             $table->string('state')->nullable();
             $table->bigInteger('user_id')->unsigned()->nullable();

            $table->bigInteger('created_by')->nullable();
            $table->timestamp('created_date')->useCurrent(); // Matches TIMESTAMP NOT NULL
            $table->bigInteger('updated_by')->nullable();
            $table->timestamp('updated_date')->nullable(); // Matches TIMESTAMP NOT NULL

           
        });

       } 

       /*  if (!Schema::connection('pgsql_b2b')->hasColumn('contacts', 'status')) {
            Schema::connection('pgsql_b2b')->table('contacts', function (Blueprint $table) {
                $table->string('status')->nullable();
            });
        }

        if (!Schema::connection('pgsql_b2b')->hasColumn('contacts', 'is_deleted')) {
            Schema::connection('pgsql_b2b')->table('contacts', function (Blueprint $table) {
               $table->boolean('is_deleted')->default(false);
            });
        }

        if (!Schema::connection('pgsql_b2b')->hasColumn('contacts', 'message_language')) {
            Schema::connection('pgsql_b2b')->table('contacts', function (Blueprint $table) {
                $table->string('message_language', 4)->nullable();
            });
        }

        if (!Schema::connection('pgsql_b2b')->hasColumn('contacts', 'state')) {
            Schema::connection('pgsql_b2b')->table('contacts', function (Blueprint $table) {
                 $table->string('state')->nullable();
            });
        }

        if (!Schema::connection('pgsql_b2b')->hasColumn('contacts', 'user_id')) {
            Schema::connection('pgsql_b2b')->table('contacts', function (Blueprint $table) {
                 $table->bigInteger('user_id')->unsigned()->nullable();
            });
        } */

    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
