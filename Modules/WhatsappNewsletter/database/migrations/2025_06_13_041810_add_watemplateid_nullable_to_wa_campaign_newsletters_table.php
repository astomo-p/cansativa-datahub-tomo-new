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
        Schema::table('wa_campaign_newsletters', function (Blueprint $table) {
            $table->unsignedBigInteger('wa_template_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_campaign_newsletters', function (Blueprint $table) {
            $table->unsignedBigInteger('wa_template_id')->nullable(false)->change();
        });
    }
};
