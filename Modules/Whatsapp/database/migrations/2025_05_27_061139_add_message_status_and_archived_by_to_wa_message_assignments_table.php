<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_b2b_shared';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wa_message_assignments', function (Blueprint $table) {
            $table->string('message_status')->default('IN PROGRESS');
            // $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_message_assignments', function (Blueprint $table) {
            $table->dropColumn('message_status');
            // $table->dropForeign(['archived_by']);
            $table->dropColumn('archived_by');
        });
    }
};
