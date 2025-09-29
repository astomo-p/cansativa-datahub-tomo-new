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
         Schema::table('contacts', function (Blueprint $table) {
            // Change amount_of_purchase to integer and make it nullable
            $table->integer('amount_purchase')->nullable()->change();

            // Make last_purchase_date nullable
            $table->date('last_purchase_date')->nullable()->change();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
