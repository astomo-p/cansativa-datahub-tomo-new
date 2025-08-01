<?php

namespace Modules\Whatsapp\Traits;

trait WhatsappServiceErrorHandler
{
    protected function formatError($message, $code = 'WHATSAPP_ERROR', $details = []): array
    {
        return [
            'error' => array_merge([
                'code' => $code,
                'message' => $message,
            ], $details)
        ];
    }

    protected function formatExceptionAsError(\Exception $exception, string $context = ''): array
    {
        \Illuminate\Support\Facades\Log::error(
            ($context ? "[$context] " : '') . 'Exception: ' . $exception->getMessage(),
            [
                'exception_class' => get_class($exception),
                'trace' => $exception->getTraceAsString(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]
        );

        return $this->formatError(
            $exception->getMessage(),
            'EXCEPTION',
            [
                'error_type' => get_class($exception),
                'context' => $context
            ]
        );
    }

    protected function formatHttpResponseError($response, string $context = ''): array
    {
        if (!is_array($response)) {
            $response = $response->json();
        }

        if (isset($response['error'])) {
            $error = $response['error'];

            \Illuminate\Support\Facades\Log::error(
                ($context ? "[$context] " : '') . 'API Error: ' . ($error['message'] ?? 'Unknown error'),
                [
                    'error' => $error,
                    'context' => $context
                ]
            );

            return [
                'error' => $error
            ];
        }

        return $this->formatError('Unknown API error', 'API_ERROR', ['context' => $context]);
    }
}
