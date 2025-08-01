<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_b2b';
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('contact_person')->change()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
