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
            if (Schema::hasColumn('saved_filters', 'contact_type')) {
                $table->renameColumn('contact_type', 'contact_type_id');
            }
            $table->boolean('is_deleted')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {

    }
};
