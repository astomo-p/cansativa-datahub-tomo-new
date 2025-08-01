<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_b2b';
    
    public function up(): void
    {
        Schema::table('history_exports', function (Blueprint $table) {
            $table->renameColumn('contact_name', 'name');
            $table->renameColumn('amount_contacts', 'amount_of_contacts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
