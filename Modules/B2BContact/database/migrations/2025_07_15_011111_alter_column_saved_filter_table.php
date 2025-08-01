<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_b2b';
    
    public function up(): void
    {
        Schema::table('saved_filters', function (Blueprint $table) {
            if (!Schema::connection($this->connection)->hasColumn('saved_filters', 'contact_type_id')) {
                $table->integer('contact_type_id')->nullable(); 
            }

        });
    }

};
