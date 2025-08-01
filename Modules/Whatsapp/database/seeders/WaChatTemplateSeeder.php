<?php

namespace Modules\Whatsapp\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WaChatTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $templates = [
            [
                "template_name" => "de_chat_template",
                "language_code" => "de",
                "message" => "Hey, bist du noch da?",
                "created_at" => $now,
                "updated_at" => $now
            ],
            [
                "template_name" => "en_chat_template",
                "language_code" => "en",
                "message" => "Hey, are you still there?",
                "created_at" => $now,
                "updated_at" => $now
            ],
            [
                "template_name" => "fr_chat_template",
                "language_code" => "fr",
                "message" => "Bonjour, es-tu toujours là ?",
                "created_at" => $now,
                "updated_at" => $now
            ]
        ];

        DB::table('wa_chat_templates')->insert($templates);
    }
}
