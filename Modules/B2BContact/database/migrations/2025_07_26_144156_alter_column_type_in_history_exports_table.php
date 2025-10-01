<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    protected $connection = 'pgsql_b2b';
    
    public function up(): void
    {
        Schema::table('history_exports', function (Blueprint $table) {
            $table->renameColumn('contact_type_id', 'contact_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
