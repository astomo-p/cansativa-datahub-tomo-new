<?php

namespace Modules\WhatsappNewsletter\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\WhatsappNewsletter\Models\WaNewsLetter;
use Carbon\Carbon;

class WaNewsLetterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campaigns = [
            [
                'name' => 'Summer Promotion Campaign',
                'status' => WaNewsLetter::STATUS_SENDING_COMPLETE,
                'contact_flag' => 'both',
                'contact_type_id' => 1,
                'wa_template_id' => 1,
                'created_by' => 1,
                'send_type' => WaNewsLetter::SEND_TYPE_NOW,
                'sending_status' => WaNewsLetter::SENDING_STATUS_SUCCESS,
                'sent_at' => Carbon::parse('2025-07-29 09:00:00'),
                'batch_amount' => 100,
                'interval_hours' => 1,
                'send_message_start_hours' => '09:00:00',
                'send_message_end_hours' => '18:00:00',
                'timezone' => 'Asia/Jakarta',
                'frequency_cap_enabled' => true,
                'frequency_cap_limit' => 3,
                'frequency_cap_period' => 24,
                'frequency_cap_unit' => 'hours',
                'filters' => json_encode([
                    'age_min' => 18,
                    'age_max' => 65,
                    'location' => 'Jakarta'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Product Launch Newsletter',
                'status' => WaNewsLetter::STATUS_SENDING_IN_PROGRESS,
                'contact_flag' => 'b2c',
                'contact_type_id' => 2,
                'wa_template_id' => 2,
                'created_by' => 1,
                'send_type' => WaNewsLetter::SEND_TYPE_NOW,
                'sending_status' => WaNewsLetter::SENDING_STATUS_PROCESSING,
                'sent_at' => Carbon::parse('2025-07-29 10:00:00'),
                'batch_amount' => 50,
                'interval_hours' => 2,
                'send_message_start_hours' => '08:00:00',
                'send_message_end_hours' => '20:00:00',
                'timezone' => 'Asia/Jakarta',
                'frequency_cap_enabled' => false,
                'filters' => json_encode([
                    'interest' => 'health',
                    'segment' => 'premium'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Weekly Health Tips',
                'status' => WaNewsLetter::STATUS_SCHEDULED,
                'contact_flag' => 'b2b',
                'contact_type_id' => 3,
                'wa_template_id' => 3,
                'created_by' => 2,
                'send_type' => WaNewsLetter::SEND_TYPE_SCHEDULED,
                'sending_status' => WaNewsLetter::SENDING_STATUS_READY,
                'scheduled_at' => Carbon::parse('2025-07-30 10:00:00'),
                'schedule_timezone' => 'Asia/Jakarta',
                'batch_amount' => 25,
                'interval_days' => 1,
                'send_message_start_hours' => '10:00:00',
                'send_message_end_hours' => '16:00:00',
                'timezone' => 'Asia/Jakarta',
                'frequency_cap_enabled' => true,
                'frequency_cap_limit' => 1,
                'frequency_cap_period' => 7,
                'frequency_cap_unit' => 'days',
                'contact_ids' => json_encode([2001, 2002, 2003, 2004, 2005]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Flash Sale Alert',
                'status' => WaNewsLetter::STATUS_DRAFT_APPROVED,
                'contact_flag' => 'both',
                'contact_type_id' => 1,
                'wa_template_id' => 4,
                'created_by' => 1,
                'send_type' => WaNewsLetter::SEND_TYPE_NOW,
                'sending_status' => WaNewsLetter::SENDING_STATUS_READY,
                'batch_amount' => 200,
                'interval_hours' => 1,
                'send_message_start_hours' => '09:00:00',
                'send_message_end_hours' => '21:00:00',
                'timezone' => 'Asia/Jakarta',
                'frequency_cap_enabled' => true,
                'frequency_cap_limit' => 2,
                'frequency_cap_period' => 12,
                'frequency_cap_unit' => 'hours',
                'filters' => json_encode([
                    'purchase_history' => true,
                    'last_purchase_days' => 30
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Customer Feedback Survey',
                'status' => WaNewsLetter::STATUS_DRAFT_IN_REVIEW,
                'contact_flag' => 'b2c',
                'contact_type_id' => 2,
                'wa_template_id' => 5,
                'created_by' => 3,
                'send_type' => WaNewsLetter::SEND_TYPE_SCHEDULED,
                'sending_status' => WaNewsLetter::SENDING_STATUS_WAITING,
                'scheduled_at' => Carbon::parse('2025-08-01 14:00:00'),
                'schedule_timezone' => 'Asia/Jakarta',
                'batch_amount' => 75,
                'interval_hours' => 3,
                'send_message_start_hours' => '14:00:00',
                'send_message_end_hours' => '17:00:00',
                'timezone' => 'Asia/Jakarta',
                'frequency_cap_enabled' => false,
                'saved_filter_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Monthly Newsletter Archive',
                'status' => WaNewsLetter::STATUS_ARCHIVE,
                'contact_flag' => 'both',
                'contact_type_id' => 1,
                'wa_template_id' => 6,
                'created_by' => 2,
                'send_type' => WaNewsLetter::SEND_TYPE_NOW,
                'sending_status' => WaNewsLetter::SENDING_STATUS_SUCCESS,
                'sent_at' => Carbon::parse('2025-06-29 10:00:00'),
                'batch_amount' => 150,
                'interval_hours' => 2,
                'send_message_start_hours' => '10:00:00',
                'send_message_end_hours' => '18:00:00',
                'timezone' => 'Asia/Jakarta',
                'frequency_cap_enabled' => true,
                'frequency_cap_limit' => 1,
                'frequency_cap_period' => 30,
                'frequency_cap_unit' => 'days',
                'filters' => json_encode([
                    'subscription_status' => 'active',
                    'engagement_score' => 'high'
                ]),
                'created_at' => Carbon::parse('2025-06-25 09:00:00'),
                'updated_at' => Carbon::parse('2025-06-29 15:00:00'),
            ],
        ];

        foreach ($campaigns as $campaign) {
            WaNewsLetter::create($campaign);
        }

        $this->command->info('WA Newsletter campaigns seeded successfully!');
    }
}
