<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateSignature
{
    use \App\Traits\HasSignatureToken;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->hasHeader('Signature')) {
            throw new Exception('Missing signature header');
        }

        $apiToken = env('API_TOKEN');
        if(!$this->validateSignatureToken($apiToken)){
            throw new Exception('Invalid signature token');
        }
        return $next($request);
    }
}
