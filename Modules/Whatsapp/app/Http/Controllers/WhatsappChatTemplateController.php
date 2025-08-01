<?php

namespace Modules\Whatsapp\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Whatsapp\Models\WhatsappChatTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponder;
use Modules\Whatsapp\Services\WhatsappAPIService;

class WhatsappChatTemplateController extends Controller
{
    use ApiResponder;

    protected $whatsappService;

    public function __construct(WhatsappAPIService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $languageCode = $request->input('language_code');

        $query = WhatsappChatTemplate::query();

        if ($languageCode) {
            $query->where('language_code', $languageCode);
        }

        $total = $query->count();
        $templates = $query->orderBy('template_name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $this->successResponse([
            'results' => $templates,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('whatsapp::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template_name' => 'required|string|max:255|unique:wa_chat_templates,template_name',
            'language_code' => 'required|string|max:10',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        $apiData = [
            'name' => $request->template_name,
            'language' => $request->language_code,
            'category' => 'MARKETING',
            'components' => [
                [
                    'type' => 'BODY',
                    'text' => $request->message
                ]
            ]
        ];

        $apiResponse = $this->whatsappService->createTemplate($apiData);

        if (isset($apiResponse['error'])) {
            return $this->errorResponse($apiResponse['error']['message'] ?? 'Failed to create template on WhatsApp', 400);
        }

        $fbid = $apiResponse['id'] ?? null;

        $template = WhatsappChatTemplate::create([
            'template_name' => $request->template_name,
            'language_code' => $request->language_code,
            'message' => $request->message,
            'fbid' => $fbid
        ]);

        return $this->successResponse(
            array_merge($template->toArray(), [
                'api_response' => $apiResponse
            ]),
            'Template created successfully',
            201
        );
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $template = WhatsappChatTemplate::find($id);

        if (!$template) {
            return $this->errorResponse('Template not found', 404);
        }

        return $this->successResponse($template);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('whatsapp::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $template = WhatsappChatTemplate::find($id);

        if (!$template) {
            return $this->errorResponse('Template not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'template_name' => 'sometimes|required|string|max:255',
            'language_code' => 'sometimes|required|string|max:10',
            'message' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        $template->update($request->only([
            'template_name',
            'language_code',
            'message'
        ]));

        return $this->successResponse($template, 'Template updated successfully');
    }

    public function destroy($id)
    {
        $template = WhatsappChatTemplate::find($id);

        if (!$template) {
            return $this->errorResponse('Template not found', 404);
        }

        $apiResponse = $this->whatsappService->deleteTemplate(
            $template->template_name,
            $template->fbid
        );

        if (isset($apiResponse['error'])) {
            if (isset($apiResponse['error']['code']) && $apiResponse['error']['code'] == 100) {
                $template->delete();
                return $this->successResponse(null, 'Template deleted from database successfully (not found on WhatsApp)');
            }

            return $this->errorResponse($apiResponse['error']['message'] ?? 'Failed to delete template from WhatsApp', 400);
        }

        $template->delete();

        return $this->successResponse(
            ['api_response' => $apiResponse],
            'Template deleted successfully'
        );
    }
}
