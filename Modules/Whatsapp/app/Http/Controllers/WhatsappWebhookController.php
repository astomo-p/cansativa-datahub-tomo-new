<?php

namespace Modules\Whatsapp\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Whatsapp\Services\WhatsappWebhookService;
use Exception;
use App\Traits\ApiResponder;
use Illuminate\Support\Facades\Log;
use Modules\Whatsapp\Traits\WhatsappErrorHandler;

class WhatsappWebhookController extends Controller
{
    use ApiResponder, WhatsappErrorHandler;

    protected $webhookService;

    public function __construct(WhatsappWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    public function verify(Request $request)
    {
        try {
            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');

            $response = $this->webhookService->verifyWebhook($mode, $token, $challenge);

            return response($response, 200);
        } catch (Exception $e) {
            Log::error('Webhook verification failed: ' . $e->getMessage(), [
                'mode' => $request->query('hub_mode'),
                'token' => $request->query('hub_verify_token'),
                'exception' => get_class($e)
            ]);

            if ($request->query('hub_challenge')) {
                return response($request->query('hub_challenge'), 200);
            }

            return response('', 200);
        }
    }

    public function handle(Request $request): JsonResponse
    {
        try {
            $response = $this->webhookService->handleWebhookEvent($request->all());

            if (isset($response['error'])) {
                Log::error('Webhook handler error: ' . $response['error']['message'], [
                    'request' => $request->all(),
                    'error' => $response['error']
                ]);
            }

            return $this->successResponse(
                ['status' => 'received'],
                'Webhook received',
                200
            );
        } catch (Exception $e) {
            Log::error('Webhook handler uncaught exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->successResponse(
                ['status' => 'received'],
                'Webhook received',
                200
            );
        }
    }
}
