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
        Schema::table('account_key_managers', function (Blueprint $table) {
            $table->renameColumn('wa_template_id', 'wa_campaign_id');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
