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
    protected $connection = 'pgsql_b2b';
    protected $connection_b2c = 'pgsql_b2c';

    public function up(): void
    {

        DB::connection($this->connection_b2c)->statement('ALTER TABLE user_saved_posts ALTER COLUMN user_id  TYPE integer USING (user_id::integer)');
        DB::connection($this->connection_b2c)->statement('ALTER TABLE user_saved_posts ALTER COLUMN user_id SET DEFAULT NULL');

        $columnType = DB::connection($this->connection)
            ->selectOne("
                SELECT data_type
                FROM information_schema.columns
                WHERE table_name = 'user_saved_posts'
                  AND column_name = 'user_id'
            ");

        if ($columnType && in_array(strtolower($columnType->data_type), ['character varying', 'char', 'text'])) {
            DB::connection($this->connection)->statement(
                "ALTER TABLE user_saved_posts ALTER COLUMN user_id TYPE BIGINT USING user_id::BIGINT"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};