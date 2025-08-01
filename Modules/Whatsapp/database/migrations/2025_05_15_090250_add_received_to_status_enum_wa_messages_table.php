<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql_b2b_shared';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE wa_messages DROP CONSTRAINT IF EXISTS wa_messages_status_check");
        DB::statement("ALTER TABLE wa_messages ADD CONSTRAINT wa_messages_status_check CHECK (status::text = ANY (ARRAY['accepted'::text, 'sent'::text, 'delivered'::text, 'read'::text, 'failed'::text, 'received'::text]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE wa_messages DROP CONSTRAINT IF EXISTS wa_messages_status_check");
        DB::statement("ALTER TABLE wa_messages ADD CONSTRAINT wa_messages_status_check CHECK (status::text = ANY (ARRAY['accepted'::text, 'sent'::text, 'delivered'::text, 'read'::text, 'failed'::text]))");
    }
};
