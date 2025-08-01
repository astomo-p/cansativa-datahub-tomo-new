<?php

namespace Modules\Whatsapp\Traits;

trait WhatsappErrorHandler
{
    protected function handleWhatsappError($result, $defaultMessage = 'An error occurred', $defaultCode = 400)
    {
        if (isset($result['error'])) {
            $error = $result['error'];

            $errorCode = $defaultCode;
            if (isset($error['code']) && is_numeric($error['code'])) {
                $errorCode = (int)$error['code'];
            } elseif (isset($error['error_code']) && is_numeric($error['error_code'])) {
                $errorCode = (int)$error['error_code'];
            }

            $errorMessage = $defaultMessage;
            if (isset($error['message'])) {
                $errorMessage = $error['message'];
            } elseif (isset($error['error_message'])) {
                $errorMessage = $error['error_message'];
            }

            if (
                isset($error['code'], $error['error_subcode']) &&
                $error['code'] == 190 &&
                $error['error_subcode'] == 463
            ) {
                $errorMessage = 'Access token expired. Please re-authenticate.';
                $errorCode = 401;
            }

            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = $defaultCode;
            }

            $errorData = [
                'code' => $errorCode,
                'message' => $errorMessage,
                'original_error' => $error
            ];

            return [
                'response' => $this->errorResponse(
                    $errorMessage,
                    $errorCode,
                    $errorData
                ),
                'error' => $errorData
            ];
        }

        return null;
    }
}
