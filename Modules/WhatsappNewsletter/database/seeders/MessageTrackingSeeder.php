<?php

namespace Modules\WhatsappNewsletter\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\WhatsappNewsletter\Models\WaCampaignMessageTracking;
use Modules\WhatsappNewsletter\Models\WaNewsLetter;
use Carbon\Carbon;

class MessageTrackingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createCampaigns();
        $this->createMessageTracking();
    }

    private function createCampaigns()
    {
        $campaigns = [
            [
                'id' => 1,
                'name' => 'Summer Promotion Campaign',
                'status' => WaNewsLetter::STATUS_SENDING_COMPLETE,
                'contact_flag' => 'both',
                'contact_type_id' => 1,
                'wa_template_id' => 1,
                'created_by' => 1,
                'send_type' => WaNewsLetter::SEND_TYPE_NOW,
                'sending_status' => WaNewsLetter::SENDING_STATUS_SUCCESS,
                'sent_at' => Carbon::parse('2025-07-29 09:00:00'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Product Launch Newsletter',
                'status' => WaNewsLetter::STATUS_SENDING_IN_PROGRESS,
                'contact_flag' => 'b2c',
                'contact_type_id' => 2,
                'wa_template_id' => 2,
                'created_by' => 1,
                'send_type' => WaNewsLetter::SEND_TYPE_NOW,
                'sending_status' => WaNewsLetter::SENDING_STATUS_PROCESSING,
                'sent_at' => Carbon::parse('2025-07-29 10:00:00'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($campaigns as $campaign) {
            WaNewsLetter::updateOrCreate(
                ['id' => $campaign['id']],
                $campaign
            );
        }
    }

    private function createMessageTracking()
    {
        $trackingData = [
            [
                'campaign_id' => 1,
                'contact_id' => 1003,
                'contact_flag' => 'b2c',
                'message_id' => 'wamid.003',
                'status' => 'sent',
                'sent_at' => Carbon::parse('2025-07-29 10:00:00'),
                'delivered_at' => null,
                'read_at' => null,
                'failed_at' => null,
                'error_code' => null,
                'error_message' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'campaign_id' => 1,
                'contact_id' => 1004,
                'contact_flag' => 'b2b',
                'message_id' => 'wamid.004',
                'status' => 'sent',
                'sent_at' => Carbon::parse('2025-07-29 10:05:00'),
                'delivered_at' => null,
                'read_at' => null,
                'failed_at' => null,
                'error_code' => null,
                'error_message' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'campaign_id' => 2,
                'contact_id' => 1005,
                'contact_flag' => 'b2c',
                'message_id' => 'wamid.005',
                'status' => 'sent',
                'sent_at' => Carbon::parse('2025-07-29 10:10:00'),
                'delivered_at' => null,
                'read_at' => null,
                'failed_at' => null,
                'error_code' => null,
                'error_message' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'campaign_id' => 1,
                'contact_id' => 1006,
                'contact_flag' => 'b2c',
                'message_id' => 'wamid.006',
                'status' => 'delivered',
                'sent_at' => Carbon::parse('2025-07-29 10:15:00'),
                'delivered_at' => Carbon::parse('2025-07-29 10:16:00'),
                'read_at' => null,
                'failed_at' => null,
                'error_code' => null,
                'error_message' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'campaign_id' => 1,
                'contact_id' => 1007,
                'contact_flag' => 'b2b',
                'message_id' => 'wamid.007',
                'status' => 'delivered',
                'sent_at' => Carbon::parse('2025-07-29 10:20:00'),
                'delivered_at' => Carbon::parse('2025-07-29 10:21:00'),
                'read_at' => null,
                'failed_at' => null,
                'error_code' => null,
                'error_message' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'campaign_id' => 2,
                'contact_id' => 1008,
                'contact_flag' => 'b2c',
                'message_id' => 'wamid.008',
                'status' => 'delivered',
                'sent_at' => Carbon::parse('2025-07-29 10:25:00'),
                'delivered_at' => Carbon::parse('2025-07-29 10:26:00'),
                'read_at' => null,
                'failed_at' => null,
                'error_code' => null,
                'error_message' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'campaign_id' => 1,
                'contact_id' => 1009,
                'contact_flag' => 'b2c',
                'message_id' => 'wamid.009',
                'status' => 'read',
                'sent_at' => Carbon::parse('2025-07-29 10:30:00'),
                'delivered_at' => Carbon::parse('2025-07-29 10:31:00'),
                'read_at' => Carbon::parse('2025-07-29 10:45:00'),
                'failed_at' => null,
                'error_code' => null,
                'error_message' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'campaign_id' => 1,
                'contact_id' => 1010,
                'contact_flag' => 'b2b',
                'message_id' => 'wamid.010',
                'status' => 'read',
                'sent_at' => Carbon::parse('2025-07-29 10:35:00'),
                'delivered_at' => Carbon::parse('2025-07-29 10:36:00'),
                'read_at' => Carbon::parse('2025-07-29 10:50:00'),
                'failed_at' => null,
                'error_code' => null,
                'error_message' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'campaign_id' => 2,
                'contact_id' => 1011,
                'contact_flag' => 'b2c',
                'message_id' => 'wamid.011',
                'status' => 'read',
                'sent_at' => Carbon::parse('2025-07-29 10:40:00'),
                'delivered_at' => Carbon::parse('2025-07-29 10:41:00'),
                'read_at' => Carbon::parse('2025-07-29 10:55:00'),
                'failed_at' => null,
                'error_code' => null,
                'error_message' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'campaign_id' => 1,
                'contact_id' => 1012,
                'contact_flag' => 'b2c',
                'message_id' => 'wamid.012',
                'status' => 'failed',
                'sent_at' => Carbon::parse('2025-07-29 11:00:00'),
                'delivered_at' => null,
                'read_at' => null,
                'failed_at' => Carbon::parse('2025-07-29 11:01:00'),
                'error_code' => '131051',
                'error_message' => 'User number is part of an experiment',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'campaign_id' => 1,
                'contact_id' => 1013,
                'contact_flag' => 'b2b',
                'message_id' => 'wamid.013',
                'status' => 'failed',
                'sent_at' => Carbon::parse('2025-07-29 11:05:00'),
                'delivered_at' => null,
                'read_at' => null,
                'failed_at' => Carbon::parse('2025-07-29 11:06:00'),
                'error_code' => '131026',
                'error_message' => 'Message undeliverable',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'campaign_id' => 2,
                'contact_id' => 1014,
                'contact_flag' => 'b2c',
                'message_id' => 'wamid.014',
                'status' => 'failed',
                'sent_at' => Carbon::parse('2025-07-29 11:10:00'),
                'delivered_at' => null,
                'read_at' => null,
                'failed_at' => Carbon::parse('2025-07-29 11:11:00'),
                'error_code' => '131047',
                'error_message' => 'Re-engagement message',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($trackingData as $data) {
            WaCampaignMessageTracking::create($data);
        }

        $this->command->info('Message tracking data seeded successfully!');
    }
}
