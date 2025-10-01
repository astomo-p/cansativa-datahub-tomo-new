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
        Schema::table('saved_filters', function (Blueprint $table) {
            $table->renameColumn('frequence_cap', 'frequency_cap');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
