<?php

namespace Modules\Whatsapp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Whatsapp\Services\WhatsappAPIService;
use App\Traits\ApiResponder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Whatsapp\Traits\WhatsappErrorHandler;
use \Symfony\Component\HttpFoundation\Response;
use Modules\NewContactData\Models\ContactTypes;

class WhatsappTemplateController extends Controller
{
    use ApiResponder, WhatsappErrorHandler;

    protected $whatsappService;

    public function __construct(WhatsappAPIService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function getTemplates(Request $request)
    {
        try {
            $search = $request->input('search');
            $status = $request->input('status');
            $type = $request->input('type');
            $language = $request->input('language');
            $perPage = (int) $request->input('per_page', 10);
            $page = (int) $request->input('page', 1);

            $result = $this->whatsappService->getTemplates(
                $search,
                $status,
                $type,
                $language,
                $perPage,
                $page
            );

            $errorResponse = $this->handleWhatsappError($result, 'Failed to retrieve templates');
            if ($errorResponse) {
                Log::error('Error in getTemplates: ' . $errorResponse['error']['message'], [
                    'error' => $errorResponse['error']
                ]);

                return $this->errorResponse(
                    'Failed to retrieve templates: ' . $errorResponse['error']['message'],
                    Response::HTTP_BAD_REQUEST,
                    $errorResponse['error']['original_error']
                );
            }

            return $this->successResponse($result['data']);
        } catch (\Exception $e) {
            Log::error('Error in getTemplates: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to retrieve templates: ' . $e->getMessage(),
                500,
                [
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage(),
                    'exception' => get_class($e)
                ]
            );
        }
    }

    public function createTemplate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|unique:wa_message_templates,name|regex:/^[a-z0-9_]+$/',
                'category' => 'required|string|in:' . implode(',', \Modules\Whatsapp\Models\Template::CATEGORIES),
                'language' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $result = $this->whatsappService->createTemplate($request->all());
            $errorResponse = $this->handleWhatsappError($result, 'Failed to create template');

            if ($errorResponse) {
                Log::error('Error in createTemplate: ' . $errorResponse['error']['message'], [
                    'request' => $request->all(),
                    'error' => $errorResponse['error']
                ]);

                return $this->errorResponse(
                    'Failed to create template: ' . $errorResponse['error']['message'],
                    Response::HTTP_BAD_REQUEST,
                    $errorResponse['error']['original_error']
                );
            }

            return $this->successResponse($result, 'Template created successfully', 201);
        } catch (\Exception $e) {
            Log::error('Error in createTemplate: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to create template: ' . $e->getMessage(),
                500,
                [
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage(),
                    'exception' => get_class($e)
                ]
            );
        }
    }

    public function updateTemplate(Request $request, $templateId)
    {
        try {
            $rules = [
                'body' => 'required|string|max:1024',
                'header_type' => 'nullable|in:TEXT,MEDIA',
                'header_content' => 'nullable|string',
                'header_default_value' => 'nullable|array',
                'body_default_value' => 'nullable|array',
                'button_type' => 'nullable|in:QUICK_REPLIES,CALL_TO_ACTION',
                'button_action' => 'nullable|string|in:REPLY,MARKETING_OPT_OUT,LINK,CALL_NUMBER',
                'button_url' => 'nullable|string|url',
                'button_text' => 'nullable|string|max:25',
                'button_phone_number' => 'nullable|string',
                'button_footer_text' => 'nullable|string|max:60',
            ];

            if ($request->input('header_type') === 'MEDIA') {
                $rules['header_content'] = 'required|file|mimes:jpg,jpeg,png,pdf,mp4';
            } elseif ($request->input('header_type') === 'TEXT') {
                $rules['header_content'] = 'required|string|max:60';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($request->input('header_type') === 'MEDIA') {
                $validator->after(function ($validator) use ($request) {
                    if ($request->hasFile('header_content')) {
                        $file = $request->file('header_content');
                        $extension = strtolower($file->getClientOriginalExtension());
                        $fileSizeKB = $file->getSize() / 1024;

                        if (in_array($extension, ['jpg', 'jpeg', 'png']) && $fileSizeKB > 5120) {
                            $validator->errors()->add('header_content', 'Image files must not exceed 5MB.');
                        } elseif ($extension === 'pdf' && $fileSizeKB > 102400) {
                            $validator->errors()->add('header_content', 'PDF files must not exceed 100MB.');
                        } elseif ($extension === 'mp4' && $fileSizeKB > 16384) {
                            $validator->errors()->add('header_content', 'Video files must not exceed 16MB.');
                        }
                    }
                });
            }

            $validator->after(function ($validator) use ($request) {
                if ($request->filled('header_type') && !$request->filled('header_content')) {
                    $validator->errors()->add('header_content', 'Header content is required when header type is specified.');
                }
                if ($request->filled('header_content') && !$request->filled('header_type')) {
                    $validator->errors()->add('header_type', 'Header type is required when header content is specified.');
                }

                if ($request->filled('button_type') && !$request->filled('button_action')) {
                    $validator->errors()->add('button_action', 'Button action is required when button type is specified.');
                }

                if ($request->filled('button_type')) {
                    $buttonType = $request->input('button_type');
                    $buttonAction = $request->input('button_action');

                    if ($buttonType === 'QUICK_REPLIES' && !in_array($buttonAction, ['REPLY', 'MARKETING_OPT_OUT'])) {
                        $validator->errors()->add('button_action', 'For QUICK_REPLIES, action must be REPLY or MARKETING_OPT_OUT.');
                    }

                    if ($buttonType === 'CALL_TO_ACTION' && !in_array($buttonAction, ['LINK', 'CALL_NUMBER'])) {
                        $validator->errors()->add('button_action', 'For CALL_TO_ACTION, action must be LINK or CALL_NUMBER.');
                    }

                    if ($buttonAction === 'LINK') {
                        if (!$request->filled('button_url')) {
                            $validator->errors()->add('button_url', 'Button URL is required for LINK action.');
                        }
                        if (!$request->filled('button_text')) {
                            $validator->errors()->add('button_text', 'Button text is required for LINK action.');
                        }
                    }

                    if ($buttonAction === 'CALL_NUMBER') {
                        if (!$request->filled('button_phone_number')) {
                            $validator->errors()->add('button_phone_number', 'Button phone number is required for CALL_NUMBER action.');
                        }
                        if (!$request->filled('button_text')) {
                            $validator->errors()->add('button_text', 'Button text is required for CALL_NUMBER action.');
                        }
                    }

                    if (in_array($buttonAction, ['REPLY', 'MARKETING_OPT_OUT'])) {
                        if (!$request->filled('button_text')) {
                            $validator->errors()->add('button_text', 'Button text is required for ' . $buttonAction . ' action.');
                        }
                    }
                }

                if ($request->input('header_type') === 'TEXT' && $request->filled('header_content')) {
                    $headerContent = $request->input('header_content');
                    preg_match_all('/\{\{[^}]+\}\}/', $headerContent, $headerMatches);
                    $headerParamsCount = count($headerMatches[0]);

                    if ($headerParamsCount > 0) {
                        $headerDefaultValue = $request->input('header_default_value');
                        if (empty($headerDefaultValue) || !is_array($headerDefaultValue)) {
                            $validator->errors()->add('header_default_value', 'Header default value is required when header content contains parameters.');
                        } elseif (count($headerDefaultValue) !== $headerParamsCount) {
                            $validator->errors()->add('header_default_value', 'Number of header default values must match the number of parameters in header content.');
                        }
                    }
                }

                if ($request->filled('body')) {
                    $bodyContent = $request->input('body');
                    preg_match_all('/\{\{[^}]+\}\}/', $bodyContent, $bodyMatches);
                    $bodyParamsCount = count($bodyMatches[0]);

                    if ($bodyParamsCount > 0) {
                        $bodyDefaultValue = $request->input('body_default_value');
                        if (empty($bodyDefaultValue) || !is_array($bodyDefaultValue)) {
                            $validator->errors()->add('body_default_value', 'Body default value is required when body content contains parameters.');
                        } elseif (count($bodyDefaultValue) !== $bodyParamsCount) {
                            $validator->errors()->add('body_default_value', 'Number of body default values must match the number of parameters in body content.');
                        }
                    }
                }
            });

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $template = \Modules\Whatsapp\Models\Template::find($templateId);
            if (!$template) {
                return $this->errorResponse(
                    'Template not found',
                    Response::HTTP_NOT_FOUND
                );
            }

            if (!in_array($template->status, ['DISAPPROVED', 'DRAFT'])) {
                return $this->errorResponse(
                    'Template can only be edited if status is DISAPPROVED or DRAFT',
                    Response::HTTP_FORBIDDEN
                );
            }

            $components = [];
            $mediaPath = null;

            if ($request->filled('header_type')) {
                $headerComponent = [
                    'type' => 'HEADER',
                    'format' => $request->input('header_type')
                ];

                if ($request->input('header_type') === 'TEXT') {
                    $headerComponent['text'] = $request->input('header_content');
                } elseif ($request->input('header_type') === 'MEDIA') {
                    $file = $request->file('header_content');
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('template_media', $fileName, 'public');
                    $mediaPath = $filePath;
                    $headerComponent['example'] = [
                        'header_handle' => [
                            config('app.url') . '/storage/' . $filePath
                        ]
                    ];
                }

                $components[] = $headerComponent;
            }

            $components[] = [
                'type' => 'BODY',
                'text' => $request->input('body')
            ];

            if ($request->filled('button_type')) {
                $buttonComponent = [
                    'type' => 'BUTTONS',
                    'buttons' => []
                ];

                $buttonAction = $request->input('button_action');
                $buttonText = $request->input('button_text');

                if ($buttonAction === 'LINK') {
                    $buttonComponent['buttons'][] = [
                        'type' => 'URL',
                        'text' => $buttonText,
                        'url' => $request->input('button_url')
                    ];
                } elseif ($buttonAction === 'CALL_NUMBER') {
                    $buttonComponent['buttons'][] = [
                        'type' => 'PHONE_NUMBER',
                        'text' => $buttonText,
                        'phone_number' => $request->input('button_phone_number')
                    ];
                } elseif ($buttonAction === 'REPLY') {
                    $buttonComponent['buttons'][] = [
                        'type' => 'QUICK_REPLY',
                        'text' => $buttonText
                    ];
                } elseif ($buttonAction === 'MARKETING_OPT_OUT') {
                    $buttonComponent['buttons'][] = [
                        'type' => 'QUICK_REPLY',
                        'text' => $buttonText
                    ];

                    if ($request->filled('button_footer_text')) {
                        $components[] = [
                            'type' => 'FOOTER',
                            'text' => $request->input('button_footer_text')
                        ];
                    }
                }

                $components[] = $buttonComponent;
            }

            $updateData = [
                'components' => $components,
                'status' => 'DRAFT',
                'body' => $request->input('body'),
                'header_type' => $request->input('header_type'),
                'header_content' => $request->input('header_type') === 'MEDIA' ? $mediaPath : $request->input('header_content'),
                'header_default_value' => $request->input('header_default_value'),
                'body_default_value' => $request->input('body_default_value'),
                'button_type' => $request->input('button_type'),
                'button_action' => $request->input('button_action'),
                'button_url' => $request->input('button_url'),
                'button_text' => $request->input('button_text'),
                'button_phone_number' => $request->input('button_phone_number'),
                'button_footer_text' => $request->input('button_footer_text'),
                'parameter_format' => 'NAMED',
            ];

            $template->update($updateData);

            $result = [
                'id' => $template->id,
                'name' => $template->name,
                'language' => $template->language,
                'category' => $template->category,
                'status' => $template->status,
                'body' => $template->body,
                'header_type' => $template->header_type,
                'header_content' => $template->header_content,
                'header_default_value' => $template->header_default_value,
                'body_default_value' => $template->body_default_value,
                'button_type' => $template->button_type,
                'button_action' => $template->button_action,
                'button_url' => $template->button_url,
                'button_text' => $template->button_text,
                'button_phone_number' => $template->button_phone_number,
                'button_footer_text' => $template->button_footer_text,
                'parameter_format' => $template->parameter_format,
                'updated_at' => $template->updated_at
            ];

            return $this->successResponse($result, 'Template updated successfully');
        } catch (\Exception $e) {
            Log::error('Error in updateTemplate: ' . $e->getMessage(), [
                'template_id' => $templateId,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to update template: ' . $e->getMessage(),
                500,
                [
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage(),
                    'exception' => get_class($e)
                ]
            );
        }
    }

    public function deleteTemplate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'template_id' => 'required|integer|exists:wa_message_templates,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $template = \Modules\Whatsapp\Models\Template::find($request->input('template_id'));

            if (!$template) {
                return $this->errorResponse(
                    'Template not found',
                    Response::HTTP_NOT_FOUND
                );
            }

            $result = $this->whatsappService->deleteTemplate(
                $template->name,
                $request->input('template_id')
            );

            $errorResponse = $this->handleWhatsappError($result, 'Failed to delete template');
            if ($errorResponse) {
                Log::error('Error in deleteTemplate: ' . $errorResponse['error']['message'], [
                    'template_id' => $request->input('template_id'),
                    'template_name' => $template->name,
                    'error' => $errorResponse['error']
                ]);

                return $this->errorResponse(
                    'Failed to delete template: ' . $errorResponse['error']['message'],
                    Response::HTTP_BAD_REQUEST,
                    $errorResponse['error']['original_error']
                );
            }

            return $this->successResponse($result, 'Template deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error in deleteTemplate: ' . $e->getMessage(), [
                'template_id' => $request->input('template_id'),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse(
                'Failed to delete template: ' . $e->getMessage(),
                500,
                [
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage(),
                    'exception' => get_class($e)
                ]
            );
        }
    }

    public function submitTemplate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'template_id' => 'required|integer|exists:wa_message_templates,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $template = \Modules\Whatsapp\Models\Template::find($request->input('template_id'));

            if (!$template) {
                return $this->errorResponse(
                    'Template not found',
                    Response::HTTP_NOT_FOUND
                );
            }

            if (!in_array($template->status, ['DRAFT', 'DISAPPROVED'])) {
                return $this->errorResponse(
                    'Template can only be submitted if status is DRAFT or DISAPPROVED',
                    Response::HTTP_FORBIDDEN
                );
            }

            $result = $this->whatsappService->submitTemplate($template->id, $template->name);

            $errorResponse = $this->handleWhatsappError($result, 'Failed to submit template');
            if ($errorResponse) {
                Log::error('Error in submitTemplate: ' . $errorResponse['error']['message'], [
                    'request' => $request->all(),
                    'error' => $errorResponse['error']
                ]);

                return $this->errorResponse(
                    'Failed to submit template: ' . $errorResponse['error']['message'],
                    Response::HTTP_BAD_REQUEST,
                    $errorResponse['error']['original_error']
                );
            }

            return $this->successResponse($result, 'Template submitted successfully for review');
        } catch (\Exception $e) {
            Log::error('Error in submitTemplate: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to submit template: ' . $e->getMessage(),
                500,
                [
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage(),
                    'exception' => get_class($e)
                ]
            );
        }
    }

    public function getUserAttributes(Request $request)
    {
        try {
            $search = $request->input('search');

            $attributeValues = [
                'name',
                'no',
                'post',
                'city',
                'contact',
                'email',
                'phone'
            ];

            $contactTypes = ContactTypes::orderBy('contact_type_name')->get();

            $data = [];

            foreach ($contactTypes as $contactType) {
                $contactTypeName = $contactType->contact_type_name;
                $contactTypeKey = $contactTypeName;

                $shortContactType = strtolower($contactTypeName);
                $shortContactType = str_replace(' ', '_', $shortContactType);
                if ($shortContactType === 'pharmacy_database') {
                    $shortContactType = 'pharmacydb';
                } elseif ($shortContactType === 'general_newsletter') {
                    $shortContactType = 'newsletter';
                }

                $attributes = [];

                foreach ($attributeValues as $attribute) {
                    $innerText = $shortContactType . '_' . $attribute;

                    if (strlen($innerText) > 20) {
                        $innerText = substr($innerText, 0, 20);
                    }

                    $value = '{{' . $innerText . '}}';

                    $displayName = match ($attribute) {
                        'name' => ucwords(strtolower($contactTypeName)) . ' Name',
                        'no' => ucwords(strtolower($contactTypeName)) . ' No',
                        'post' => 'Post Code',
                        'contact' => 'Contact Person',
                        'phone' => 'Phone No',
                        default => ucwords(str_replace('_', ' ', $attribute))
                    };

                    $attributeData = [
                        'name' => $displayName,
                        'attributes' => $contactTypeName,
                        'value' => $value
                    ];

                    if (!$search || stripos($attributeData['name'], $search) !== false) {
                        $attributes[] = $attributeData;
                    }
                }

                if (!empty($attributes)) {
                    $data[$contactTypeKey] = $attributes;
                }
            }

            return $this->successResponse($data, 'User attributes retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error in getUserAttributes: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to retrieve user attributes: ' . $e->getMessage(),
                500,
                [
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage(),
                    'exception' => get_class($e)
                ]
            );
        }
    }

    public function archiveTemplate($id)
    {
        try {
            $template = \Modules\Whatsapp\Models\Template::find($id);

            if (!$template) {
                return $this->errorResponse(
                    'Template not found',
                    Response::HTTP_NOT_FOUND
                );
            }

            $template->update(['status' => 'ARCHIVED']);

            return $this->successResponse([
                'id' => $template->id,
                'name' => $template->name,
                'status' => $template->status,
                'updated_at' => $template->updated_at
            ], 'Template archived successfully');
        } catch (\Exception $e) {
            Log::error('Error in archiveTemplate: ' . $e->getMessage(), [
                'template_id' => $id,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to archive template: ' . $e->getMessage(),
                500,
                [
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage(),
                    'exception' => get_class($e)
                ]
            );
        }
    }

    public function duplicateTemplate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'template_id' => 'required|integer|exists:wa_message_templates,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $originalTemplate = \Modules\Whatsapp\Models\Template::find($request->input('template_id'));

            if (!$originalTemplate) {
                return $this->errorResponse(
                    'Template not found',
                    Response::HTTP_NOT_FOUND
                );
            }

            $baseName = $originalTemplate->name;
            $newName = $baseName . '_copy';
            $counter = 1;

            while (\Modules\Whatsapp\Models\Template::where('name', $newName)->exists()) {
                $newName = $baseName . '_copy' . $counter;
                $counter++;
            }

            $duplicateData = $originalTemplate->toArray();

            unset($duplicateData['id']);
            unset($duplicateData['created_at']);
            unset($duplicateData['updated_at']);

            $duplicateData['name'] = $newName;
            $duplicateData['fbid'] = null;
            $duplicateData['api_status'] = null;
            $duplicateData['status'] = 'DRAFT';

            $duplicatedTemplate = \Modules\Whatsapp\Models\Template::create($duplicateData);

            return $this->successResponse([
                'id' => $duplicatedTemplate->id,
                'name' => $duplicatedTemplate->name,
                'language' => $duplicatedTemplate->language,
                'category' => $duplicatedTemplate->category,
                'status' => $duplicatedTemplate->status,
                'body' => $duplicatedTemplate->body,
                'header_type' => $duplicatedTemplate->header_type,
                'header_content' => $duplicatedTemplate->header_content,
                'header_default_value' => $duplicatedTemplate->header_default_value,
                'body_default_value' => $duplicatedTemplate->body_default_value,
                'button_type' => $duplicatedTemplate->button_type,
                'button_action' => $duplicatedTemplate->button_action,
                'button_text' => $duplicatedTemplate->button_text,
                'button_url' => $duplicatedTemplate->button_url,
                'button_phone_number' => $duplicatedTemplate->button_phone_number,
                'button_footer_text' => $duplicatedTemplate->button_footer_text,
                'parameter_format' => $duplicatedTemplate->parameter_format,
                'created_at' => $duplicatedTemplate->created_at,
                'updated_at' => $duplicatedTemplate->updated_at
            ], 'Template duplicated successfully', 201);
        } catch (\Exception $e) {
            Log::error('Error in duplicateTemplate: ' . $e->getMessage(), [
                'template_id' => $request->input('template_id'),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to duplicate template: ' . $e->getMessage(),
                500,
                [
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage(),
                    'exception' => get_class($e)
                ]
            );
        }
    }
}
