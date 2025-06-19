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
        Schema::connection('pgsql_b2b')->create('contact_types', function (Blueprint $table) {
           $table->id(); // Explicitly defining BIGINT and primary key
            $table->string('contact_type_name'); // VARCHAR NOT NULL
            $table->string('recipient_type');    // VARCHAR NOT NULL

            $table->bigInteger('created_by');    // BIGINT NOT NULL
            $table->timestamp('created_date');   // TIMESTAMP NOT NULL
            $table->bigInteger('updated_by')->nullable();    // BIGINT NOT NULL
            $table->timestamp('updated_date')->nullable();   // TIMESTAMP NOT NULL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
