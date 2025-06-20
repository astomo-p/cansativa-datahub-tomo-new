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

        $expire = strtotime(date("Y-m-d H:i:s"));
        $token = explode('.',$signature);
        $sign = $token[1];
        $expired = strtotime(base64_decode($token[0]));
        $expected = hash_hmac('sha256', json_encode($payloads, JSON_THROW_ON_ERROR), $apiToken);

        if($expired > $expire){
             return null;
        }
        if ($expected !== $sign) {
            return [$expire,$expired,$signature, $payloads];
        } else {
            return [$expire,$expired,$signature, $payloads];
        }
    

    }
}