<?php

namespace App\Traits;
use Illuminate\Support\Facades\Request;

trait HasSignatureToken
{
    /** 
     * validate signature token
     */
    public function validateSignatureToken($apiToken,$signature,$payloads)
    {

        $expected = hash_hmac('sha256', json_encode($payloads, JSON_THROW_ON_ERROR), $apiToken);

        if ($expected !== $signature) {
            return [$signature, $payloads];
        } else {
            return null;
        }
    

    }
}