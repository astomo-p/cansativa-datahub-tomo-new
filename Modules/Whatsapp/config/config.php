<?php

return [
    'name' => 'Whatsapp',
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'api_token' => env('WHATSAPP_API_TOKEN'),
    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'your-verify-token'),
    'google.cloud.credentials' => env('GOOGLE_TRANSLATE_API_KEY', null),
];
