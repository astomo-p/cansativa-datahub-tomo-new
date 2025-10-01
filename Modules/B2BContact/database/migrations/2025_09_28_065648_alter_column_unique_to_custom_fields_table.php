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
        Schema::connection('pgsql')->table('contact_fields', function (Blueprint $table) {
            $table->dropUnique('contact_fields_field_name_unique');
            $table->string('field_name')->change(); // keeps data intact
            $table->unique(['field_name', 'contact_type_id']);
        });

        Schema::connection('pgsql_b2b')->table('contact_fields', function (Blueprint $table) {
            $table->dropUnique('contact_fields_field_name_unique');
            $table->string('field_name')->change(); // keeps data intact
            $table->unique(['field_name', 'contact_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
