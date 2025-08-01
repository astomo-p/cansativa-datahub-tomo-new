<?php

namespace Modules\Whatsapp\Services;

class GoogleTranslateService
{
    public function handle() {}

    public function detectLanguage($text)
    {
        $apiKey = env('GOOGLE_TRANSLATE_API_KEY', 'your-api-key');
        $url = "https://translation.googleapis.com/language/translate/v2/detect?key={$apiKey}";

        $data = [
            'q' => $text
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $result = json_decode($response, true);

        if (isset($result['data']['detections'][0][0]['language'])) {
            return $result['data']['detections'][0][0]['language'];
        }

        return null;
    }
}
