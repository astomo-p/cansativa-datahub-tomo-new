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
            $table->unsignedBigInteger('assigned_to')->nullable()->change();
            $table->unsignedBigInteger('assigned_by')->nullable()->change();
            $table->dateTime('assigned_date')->nullable()->change();
            $table->string('status')->nullable()->after('assigned_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_message_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_to')->nullable(false)->change();
            $table->unsignedBigInteger('assigned_by')->nullable(false)->change();
            $table->dateTime('assigned_date')->nullable(false)->change();
            $table->dropColumn('status');
        });
    }
};
