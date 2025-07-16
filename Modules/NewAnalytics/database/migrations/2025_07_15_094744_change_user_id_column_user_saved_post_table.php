<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        /* Schema::table('user_saved_posts', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable()->change();
        }); */
        DB::statement('ALTER TABLE user_saved_posts ALTER COLUMN user_id  TYPE integer USING (user_id::integer)');
        DB::statement('ALTER TABLE user_saved_posts ALTER COLUMN user_id SET DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
