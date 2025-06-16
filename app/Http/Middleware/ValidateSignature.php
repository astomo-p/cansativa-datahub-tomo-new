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
        if (!$request->hasHeader('Authorization')) {
            return response()->json([
                'message' => 'Missing Authorization or Signature header'
            ], 400);

        }

        $apiToken = env('API_TOKEN');
        $validated = $this->validateSignatureToken($apiToken, $request->bearerToken(), $request->all());
        if(!is_null($validated)) {
            return response()->json([
                'message' => 'Invalid signature token',
                'data' => $validated
            ], 401);
        }
        return $next($request);
    }
}
