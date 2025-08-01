<?php

namespace Modules\WhatsappNewsletter\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ApiResponder;
use Modules\WhatsappNewsletter\Models\WaNewsLetter;
use Modules\WhatsappNewsletter\Models\WaNewsLetterStats;
use Modules\WhatsappNewsletter\Models\WaCampaignInteractions;
use Modules\WhatsappNewsletter\Models\WaCampaignMessageTracking;
use Modules\Whatsapp\Models\Message;
use Modules\NewContactData\Models\ContactTypes;
use Modules\WhatsappNewsletter\Services\FilterConfigService;
use Modules\WhatsappNewsletter\Helpers\FilterValidationHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\NewContactData\Models\Contacts;
use Modules\WhatsappNewsletter\Helpers\FilterQueryHelper;
use Modules\AuditLog\Events\AuditLogged;
use Modules\AuditLog\Models\AuditLogs;

class WhatsappNewsletterController extends Controller
{
    use ApiResponder;

    public function index()
    {
        return view('whatsappnewsletter::index');
    }

    public function create()
    {
        return view('whatsappnewsletter::create');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaignName' => 'required|string|max:255',
            'contactTypeId' => 'required|integer|exists:contact_types,id',
        ]);

        try {
            $contactType = ContactTypes::where('id', $validated['contactTypeId'])->firstOrFail();
            $contactFlag = 'b2c';

            if (in_array(strtolower($contactType->contact_type_name), ['pharmacy', 'supplier', 'general newsletter'])) {
                $contactFlag = 'b2b';
            } elseif (in_array(strtolower($contactType->contact_type_name), ['community', 'pharmacy database'])) {
                $contactFlag = 'b2c';
            }

            $newsletter = WaNewsLetter::create([
                'name' => $validated['campaignName'],
                'contact_type_id' => $validated['contactTypeId'],
                'contact_flag' => $contactFlag,
                'status' => WaNewsLetter::STATUS_DRAFT_NOT_SUBMITTED,
                'created_by' => $request->user() ? $request->user()->id : null,
            ]);

            event(new AuditLogged(AuditLogs::MODULE_WA_NEWSLETTER, 'Create WhatApp Campaigns'));

            return $this->successResponse(
                [
                    'id' => $newsletter->id,
                    'name' => $newsletter->name,
                    'contactTypeId' => $contactType->id,
                    'contactTypeName' => $contactType->contact_type_name
                ],
                'Newsletter campaign created successfully',
                201
            );
        } catch (\Exception $e) {
            Log::error('Failed to create newsletter campaign: ' . $e->getMessage());
            return $this->errorResponse(
                'Failed to create newsletter campaign',
                500
            );
        }
    }

    public function show($id)
    {
        return view('whatsappnewsletter::show');
    }

    public function edit($id)
    {
        return view('whatsappnewsletter::edit');
    }

    public function update(Request $request, $newsletterId): JsonResponse
    {
        try {
            $newsletter = WaNewsLetter::findOrFail($newsletterId);

            if (
                !empty($newsletter->contact_ids) &&
                json_decode($newsletter->contact_ids) &&
                count(json_decode($newsletter->contact_ids)) > 0 &&
                $newsletter->wa_template_id !== null
            ) {
                event(new AuditLogged(AuditLogs::MODULE_WA_NEWSLETTER, 'Edit WhatApp Campaigns'));
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'scheduled_at' => 'sometimes|nullable|date_format:Y-m-d H:i:s',
                'schedule_timezone' => 'sometimes|string',
                'contact_type_id' => 'sometimes|integer|exists:contact_types,id',
                'wa_template_id' => 'sometimes|integer',
                'filters' => 'sometimes|nullable',
                'send_type' => 'sometimes|string|in:SEND NOW,SCHEDULED',
                'batch_amount' => 'sometimes|integer',
                'interval_days' => 'sometimes|integer',
                'interval_hours' => 'sometimes|integer',
                'send_message_start_hours' => 'sometimes|string',
                'send_message_end_hours' => 'sometimes|string',
                'timezone' => 'sometimes|string',
                'frequency_cap_enabled' => 'sometimes|boolean',
                'frequency_cap_limit' => 'sometimes|integer',
                'frequency_cap_period' => 'sometimes|integer',
                'frequency_cap_unit' => 'sometimes|string|in:day,week,month,year',
                'contact_ids' => 'sometimes|array',
                'saved_filter_id' => 'sometimes|integer'
            ]);

            if (isset($validated['saved_filter_id'])) {
                if ($newsletter->contact_flag === 'b2b') {
                    $savedFilter = \Modules\B2BContact\Models\SavedFilters::find($validated['saved_filter_id']);
                } else {
                    $savedFilter = \Modules\NewContactData\Models\SavedFilters::find($validated['saved_filter_id']);
                }

                if (!$savedFilter) {
                    return $this->errorResponse(
                        'The selected saved filter does not exist',
                        422,
                        ['saved_filter_id' => ['The selected saved filter does not exist']]
                    );
                }
            }

            $this->handleSchedulingLogic($validated);

            if (isset($validated['filters'])) {
                $validated['filters'] = json_encode($validated['filters']);
            }

            if (isset($validated['wa_template_id'])) {
                $template = \Modules\Whatsapp\Models\Template::find($validated['wa_template_id']);

                if (!$template) {
                    return $this->errorResponse(
                        'The selected WhatsApp template does not exist',
                        422,
                        ['wa_template_id' => ['The selected WhatsApp template does not exist']]
                    );
                }

                $templateStatus = $template->status ?? null;

                if (in_array($newsletter->status, [
                    WaNewsLetter::STATUS_DRAFT_NOT_SUBMITTED,
                    WaNewsLetter::STATUS_DRAFT_IN_REVIEW,
                    WaNewsLetter::STATUS_DRAFT_APPROVED,
                    WaNewsLetter::STATUS_DRAFT_DISAPPROVED
                ])) {
                    if ($templateStatus === 'APPROVED') {
                        $validated['status'] = WaNewsLetter::STATUS_DRAFT_APPROVED;
                    } elseif (in_array($templateStatus, ['IN REVIEW', 'PENDING'])) {
                        $validated['status'] = WaNewsLetter::STATUS_DRAFT_IN_REVIEW;
                    } elseif ($templateStatus === 'DISAPPROVED' || $templateStatus === 'REJECTED') {
                        $validated['status'] = WaNewsLetter::STATUS_DRAFT_DISAPPROVED;
                    } else {
                        $validated['status'] = WaNewsLetter::STATUS_DRAFT_NOT_SUBMITTED;
                    }
                }
            }

            if (isset($validated['send_message_start_hours'])) {
                $validated['send_message_start_hours'] = sprintf('%02d:00:00', (int)$validated['send_message_start_hours']);
            }

            if (isset($validated['send_message_end_hours'])) {
                $validated['send_message_end_hours'] = sprintf('%02d:00:00', (int)$validated['send_message_end_hours']);
            }

            if (isset($validated['contact_ids']) && is_array($validated['contact_ids'])) {
                if (isset($validated['filters'])) {
                    $validated['filters'] = null;
                }

                $contactIds = $validated['contact_ids'];

                if ($newsletter->contact_flag === 'b2b') {
                    $validContactIds = \Modules\NewContactData\Models\B2BContacts::whereIn('id', $contactIds)
                        ->where('contact_type_id', $newsletter->contact_type_id)
                        ->pluck('id')
                        ->toArray();
                } else {
                    $validContactIds = \Modules\NewContactData\Models\Contacts::whereIn('id', $contactIds)
                        ->where('contact_type_id', $newsletter->contact_type_id)
                        ->pluck('id')
                        ->toArray();
                }

                if (empty($validContactIds)) {
                    return $this->errorResponse(
                        'No valid contacts found with the provided IDs',
                        422
                    );
                }

                $validated['contact_ids'] = json_encode($validContactIds);
            }

            $newsletter->update($validated);
            $newsletterResponse = $newsletter->toArray();

            return $this->successResponse(
                $newsletterResponse,
                'Newsletter updated successfully',
                200
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse(
                'Newsletter not found',
                404
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            Log::error('Failed to update newsletter: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->errorResponse(
                'Failed to update newsletter: ' . $e->getMessage(),
                500
            );
        }
    }

    private function handleSchedulingLogic(array &$validated): void
    {
        $dataTrickleFields = [
            'batch_amount',
            'interval_days',
            'interval_hours',
            'send_message_start_hours',
            'send_message_end_hours',
            'timezone'
        ];

        $scheduledFields = [
            'scheduled_at',
            'schedule_timezone'
        ];

        $hasAnyDataTrickleField = false;
        foreach ($dataTrickleFields as $field) {
            if (isset($validated[$field]) && !is_null($validated[$field])) {
                $hasAnyDataTrickleField = true;
                break;
            }
        }

        if ($hasAnyDataTrickleField) {
            $missingFields = [];
            foreach ($dataTrickleFields as $field) {
                if (!isset($validated[$field]) || is_null($validated[$field])) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                $errors = [];
                foreach ($missingFields as $field) {
                    $errors[$field] = ['This field is required when using data trickle parameters.'];
                }
                throw \Illuminate\Validation\ValidationException::withMessages($errors);
            }
        }

        if (isset($validated['send_type'])) {
            if ($validated['send_type'] === 'SEND NOW') {
                $validated['scheduled_at'] = now()->format('Y-m-d H:i:s');
                if (!isset($validated['schedule_timezone'])) {
                    $validated['schedule_timezone'] = null;
                }

                if (!$hasAnyDataTrickleField) {
                    foreach ($dataTrickleFields as $field) {
                        $validated[$field] = null;
                    }
                }
            } elseif ($validated['send_type'] === 'SCHEDULED') {
                $missingScheduledFields = [];
                foreach ($scheduledFields as $field) {
                    if (!isset($validated[$field]) || is_null($validated[$field])) {
                        $missingScheduledFields[] = $field;
                    }
                }

                if (!empty($missingScheduledFields)) {
                    $errors = [];
                    foreach ($missingScheduledFields as $field) {
                        $errors[$field] = ['This field is required when using SCHEDULED send type.'];
                    }
                    throw \Illuminate\Validation\ValidationException::withMessages($errors);
                }

                if (!$hasAnyDataTrickleField) {
                    foreach ($dataTrickleFields as $field) {
                        $validated[$field] = null;
                    }
                }
            }
        } else {
            if (!$hasAnyDataTrickleField) {
                foreach ($dataTrickleFields as $field) {
                    $validated[$field] = null;
                }
            }
        }

        if (isset($validated['send_type']) && $validated['send_type'] === 'SCHEDULED') {
            if (isset($validated['scheduled_at']) && !is_null($validated['scheduled_at'])) {
                if (!isset($validated['schedule_timezone']) || is_null($validated['schedule_timezone'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'schedule_timezone' => ['The schedule timezone field is required when using SCHEDULED send type.']
                    ]);
                }
            }
        }
    }

    public function saveFilter(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'contactTypeId' => 'required|integer|exists:contact_types,id',
                'filters' => 'required|array',
                'filterName' => 'required|string|max:255',
            ]);

            $contactType = ContactTypes::where('id', $validated['contactTypeId'])->firstOrFail();

            $filters = ['filters' => $validated['filters']];
            $filterValidationHelper = app(FilterValidationHelper::class);
            $filterErrors = $filterValidationHelper->validate($filters, $contactType->contact_type_name);

            if (!empty($filterErrors)) {
                return $this->errorResponse(
                    'Invalid filter structure',
                    422,
                    $filterErrors
                );
            }

            $filterPreset = \Modules\WhatsappNewsletter\App\Models\FilterPreset::create([
                'name' => $validated['filterName'],
                'contact_type_id' => $validated['contactTypeId'],
                'filters' => $validated['filters']
            ]);

            return $this->successResponse(
                [
                    'id' => $filterPreset->id,
                    'name' => $filterPreset->name,
                    'contactTypeId' => $filterPreset->contact_type_id,
                    'filters' => $filterPreset->filters
                ],
                'Filter configuration saved successfully',
                201
            );
        } catch (\Exception $e) {
            Log::error('Failed to save filter configuration: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->errorResponse(
                'Failed to save filter configuration: ' . $e->getMessage(),
                500
            );
        }
    }

    public function destroy($id) {}

    public function getNewsletters(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = max(1, min(100, (int) $request->input('per_page', 10)));
            $search = $request->input('search');

            $query = WaNewsLetter::with('stats')
                ->orderBy('created_at', 'desc');

            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }

            $total = $query->count();
            $offset = ($page - 1) * $perPage;
            $newsletters = $query->skip($offset)->take($perPage)->get();

            $results = $newsletters->map(function ($newsletter) {
                $stats = $newsletter->stats;
                $sentTo = $stats ? $stats->total_sent : 0;
                $delivered = $stats ? $stats->total_delivered : 0;
                $clicks = $stats ? $stats->total_clicks : 0;
                $unsubscribed = $stats ? $stats->total_unsubscribed : 0;

                $sentToPercent = 0;
                $deliveredPercent = $sentTo > 0 ? round(($delivered / $sentTo) * 100, 2) : 0;
                $clicksPercent = $sentTo > 0 ? round(($clicks / $sentTo) * 100, 2) : 0;
                $unsubscribedPercent = $sentTo > 0 ? round(($unsubscribed / $sentTo) * 100, 2) : 0;

                return [
                    'id' => $newsletter->id,
                    'name' => $newsletter->name,
                    'sent_to' => $sentTo,
                    'delivered' => $delivered,
                    'clicks' => $clicks,
                    'unsubscribed' => $unsubscribed,
                    'sent_at' => $newsletter->sent_at ? $newsletter->sent_at->format('Y-m-d H:i:s') : null,
                    'status' => $newsletter->status,
                    'sent_to_percent' => $sentToPercent,
                    'delivered_percent' => $deliveredPercent,
                    'clicks_percent' => $clicksPercent,
                    'unsubscribed_percent' => $unsubscribedPercent,
                ];
            });

            $lastPage = ceil($total / $perPage);

            return $this->successResponse([
                'results' => $results,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total),
                ]
            ], 'Newsletter campaigns retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving newsletter campaigns: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->errorResponse(
                'Failed to retrieve newsletter campaigns: ' . $e->getMessage(),
                500
            );
        }
    }

    public function getNewsletterOverview(Request $request): JsonResponse
    {
        try {
            $filter = $request->input('filter', 'alltime');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $newsletterQuery = WaNewsLetter::query();

            switch ($filter) {
                case 'this_month':
                    $startOfMonth = Carbon::now()->startOfMonth();
                    $endOfMonth = Carbon::now()->endOfMonth();
                    $newsletterQuery->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
                    break;

                case 'date_range':
                    if ($startDate && $endDate) {
                        $start = Carbon::parse($startDate)->startOfDay();
                        $end = Carbon::parse($endDate)->endOfDay();
                        $newsletterQuery->whereBetween('created_at', [$start, $end]);
                    }
                    break;

                case 'alltime':
                default:
                    break;
            }

            $newsletterIds = $newsletterQuery->pluck('id')->toArray();

            $campaignsSent = $newsletterQuery->whereIn('status', [
                WaNewsLetter::STATUS_SENDING_COMPLETE,
                WaNewsLetter::STATUS_SENDING_IN_PROGRESS
            ])->count();

            $statsQuery = WaNewsLetterStats::query();
            if (!empty($newsletterIds)) {
                $statsQuery->whereIn('campaign_id', $newsletterIds);
            }

            $totalSent = $statsQuery->sum('total_sent');
            $totalDelivered = $statsQuery->sum('total_delivered');
            $totalOpened = $statsQuery->sum('total_opened');
            $totalUnsubscribed = $statsQuery->sum('total_unsubscribed');

            $clicksQuery = WaCampaignInteractions::where('interaction_type', 'click');
            if (!empty($newsletterIds)) {
                $clicksQuery->whereIn('campaign_id', $newsletterIds);
            }
            $totalClicks = $clicksQuery->count();

            $deliveredPercent = $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 2) : 0;
            $openedPercent = $totalSent > 0 ? round(($totalOpened / $totalSent) * 100, 2) : 0;
            $clicksPercent = $totalSent > 0 ? round(($totalClicks / $totalSent) * 100, 2) : 0;
            $unsubscribePercent = $totalSent > 0 ? round(($totalUnsubscribed / $totalSent) * 100, 2) : 0;

            $responseData = [
                'campaign_sent' => [
                    'value' => (int) $campaignsSent
                ],
                'messages_sent' => [
                    'value' => (int) $totalSent
                ],
                'messages_delivered' => [
                    'value' => (int) $totalDelivered,
                    'percent' => $deliveredPercent
                ],
                'messages_opened' => [
                    'value' => (int) $totalOpened,
                    'percent' => $openedPercent
                ],
                'link_clicks' => [
                    'value' => (int) $totalClicks,
                    'percent' => $clicksPercent
                ],
                'total_unsubscribes' => [
                    'value' => (int) $totalUnsubscribed,
                    'percent' => $unsubscribePercent
                ]
            ];

            return $this->successResponse($responseData, 'Newsletter overview statistics retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving newsletter overview: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->errorResponse(
                'Failed to retrieve newsletter overview: ' . $e->getMessage(),
                500
            );
        }
    }

    public function getSavedFilters(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = max(1, min(100, (int) $request->input('per_page', 10)));
            $contactTypeId = $request->input('contact_type_id');

            $query = \Modules\WhatsappNewsletter\App\Models\FilterPreset::query()
                ->orderBy('created_at', 'desc');

            if ($contactTypeId) {
                $query->where('contact_type_id', $contactTypeId);
            }

            $total = $query->count();
            $offset = ($page - 1) * $perPage;
            $filterPresets = $query->skip($offset)->take($perPage)->get();

            $contactTypeIds = $filterPresets->pluck('contact_type_id')->unique()->toArray();
            $contactTypes = \Modules\NewContactData\Models\ContactTypes::whereIn('id', $contactTypeIds)->get()
                ->keyBy('id');

            $results = $filterPresets->map(function ($filterPreset) use ($contactTypes) {
                $contactType = $contactTypes[$filterPreset->contact_type_id] ?? null;

                return [
                    'id' => $filterPreset->id,
                    'name' => $filterPreset->name,
                    'contactTypeId' => $filterPreset->contact_type_id,
                    'contactTypeName' => $contactType ? $contactType->contact_type_name : null,
                    'createdAt' => $filterPreset->created_at->format('Y-m-d H:i:s')
                ];
            });

            $lastPage = ceil($total / $perPage);

            return $this->successResponse([
                'results' => $results,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total),
                ]
            ], 'Saved filter presets retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving saved filter presets: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->errorResponse(
                'Failed to retrieve saved filter presets: ' . $e->getMessage(),
                500
            );
        }
    }

    public function getSavedFilterById($id): JsonResponse
    {
        try {
            $filterPreset = \Modules\WhatsappNewsletter\App\Models\FilterPreset::findOrFail($id);

            $contactType = null;
            if ($filterPreset->contact_type_id) {
                $contactType = \Modules\NewContactData\Models\ContactTypes::find($filterPreset->contact_type_id);
            }

            $result = [
                'id' => $filterPreset->id,
                'name' => $filterPreset->name,
                'contactTypeId' => $filterPreset->contact_type_id,
                'contactTypeName' => $contactType ? $contactType->contact_type_name : null,
                'filters' => $filterPreset->filters,
                'createdAt' => $filterPreset->created_at->format('Y-m-d H:i:s'),
                'updatedAt' => $filterPreset->updated_at->format('Y-m-d H:i:s'),
            ];

            return $this->successResponse($result, 'Saved filter preset retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Filter preset not found', 404);
        } catch (\Exception $e) {
            Log::error('Error retrieving saved filter preset: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to retrieve saved filter preset: ' . $e->getMessage(),
                500
            );
        }
    }

    public function getFilteredContacts(Request $request, $newsletterId): JsonResponse
    {
        try {
            $newsletter = WaNewsLetter::findOrFail($newsletterId);
            $filters = $newsletter->filters ? json_decode($newsletter->filters, true) : [];
            $query = Contacts::query();

            $filterQueryHelper = new FilterQueryHelper();
            $query = $filterQueryHelper->applyFilters($query, $filters, $newsletter->contact_type_id);

            if ($newsletter->contact_type_id) {
                $query->where('contact_type_id', $newsletter->contact_type_id);
            }

            $query->select([
                'id',
                'contact_name',
                'phone_no',
                'contact_person',
                'email',
                'address',
                'city',
                'country',
                'post_code',
                'contact_type_id',
                'last_message_at',
                'created_date',
                'updated_date'
            ]);

            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $contacts = $query->paginate($perPage, ['*'], 'page', $page);

            $contacts->getCollection()->transform(function ($contact) {
                return $contact;
            });

            return $this->successResponse([
                'data' => $contacts->items(),
                'pagination' => [
                    'total' => $contacts->total(),
                    'per_page' => $contacts->perPage(),
                    'current_page' => $contacts->currentPage(),
                    'last_page' => $contacts->lastPage()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting filtered contacts: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getSummary(Request $request, $newsletterId): JsonResponse
    {
        try {
            $newsletter = WaNewsLetter::findOrFail($newsletterId);
            $contactType = ContactTypes::where('id', $newsletter->contact_type_id)->first();
            $stats = WaNewsLetterStats::where('campaign_id', $newsletter->id)->first();
            $filters = $newsletter->filters ? json_decode($newsletter->filters, true) : [];
            $newsletterData = $newsletter->toArray();

            if (isset($newsletterData['filters'])) {
                unset($newsletterData['filters']);
            }

            $response = array_merge($newsletterData, [
                'contact_type' => $contactType ? [
                    'id' => $contactType->id,
                    'name' => $contactType->contact_type_name
                ] : null,
            ]);

            return $this->successResponse($response, 'Newsletter Summary retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Newsletter not found', 404);
        } catch (\Exception $e) {
            Log::error('Error retrieving newsletter: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return $this->errorResponse(
                'Failed to retrieve newsletter: ' . $e->getMessage(),
                500
            );
        }
    }

    public function getNewsletterById($newsletterId): JsonResponse
    {
        try {
            $newsletter = WaNewsLetter::with(['stats'])->findOrFail($newsletterId);
            $contactType = ContactTypes::find($newsletter->contact_type_id);
            $newsletterData = $newsletter->toArray();

            unset(
                $newsletterData['frequency_cap_enabled'],
                $newsletterData['frequency_cap_limit'],
                $newsletterData['frequency_cap_period'],
                $newsletterData['frequency_cap_unit']
            );

            if (isset($newsletterData['filters']) && !empty($newsletterData['filters'])) {
                $newsletterData['filters'] = json_decode($newsletterData['filters'], true);
            } else {
                $newsletterData['filters'] = [];
            }

            $contactTypeInfo = null;

            if ($contactType) {
                $contactTypeInfo = $contactType->toArray();
            }

            $response = array_merge($newsletterData, [
                'contactTypeId' => $newsletter->contact_type_id,
                'contactTypeName' => $contactType ? $contactType->contact_type_name : null,
                'contactType' => $contactTypeInfo
            ]);

            return $this->successResponse($response, 'Newsletter retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Newsletter not found', 404);
        } catch (\Exception $e) {
            Log::error('Error retrieving newsletter by ID: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to retrieve newsletter: ' . $e->getMessage(),
                500
            );
        }
    }

    public function deleteNewsletter($newsletterId): JsonResponse
    {
        try {
            $newsletter = WaNewsLetter::findOrFail($newsletterId);

            $allowedStatuses = [
                WaNewsLetter::STATUS_SCHEDULED,
                WaNewsLetter::STATUS_DRAFT_APPROVED,
                WaNewsLetter::STATUS_DRAFT_IN_REVIEW,
                WaNewsLetter::STATUS_DRAFT_NOT_SUBMITTED,
                WaNewsLetter::STATUS_DRAFT_DISAPPROVED,
                WaNewsLetter::STATUS_ARCHIVE
            ];

            if (!in_array($newsletter->status, $allowedStatuses)) {
                return $this->errorResponse(
                    'Newsletter cannot be deleted in its current status',
                    422,
                    ['status' => 'Only newsletters with status: Scheduled, Draft - Approved, Draft - In review, Draft - Not submitted for review, Draft - Disapproved by Meta, or Archive can be deleted']
                );
            }

            $newsletter->delete();

            event(new AuditLogged(AuditLogs::MODULE_WA_NEWSLETTER, 'Delete WhatApp Campaigns'));

            return $this->successResponse(null, 'Newsletter deleted successfully', 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Newsletter not found', 404);
        } catch (\Exception $e) {
            Log::error('Error deleting newsletter: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to delete newsletter: ' . $e->getMessage(),
                500
            );
        }
    }

    public function getMetricsById($newsletterId): JsonResponse
    {
        try {
            $newsletter = WaNewsLetter::findOrFail($newsletterId);

            $filterName = null;
            if ($newsletter->saved_filter_id) {
                if ($newsletter->contact_flag === 'b2b') {
                    $savedFilter = \Modules\B2BContact\Models\SavedFilters::find($newsletter->saved_filter_id);
                } else {
                    $savedFilter = \Modules\NewContactData\Models\SavedFilters::find($newsletter->saved_filter_id);
                }

                if ($savedFilter) {
                    $filterName = $savedFilter->filter_name;
                }
            } else {
                $filterName = '-';
            }

            $newsletterData = [
                'name' => $newsletter->name,
                'submitted_on' => $newsletter->created_at,
                'filter_name' => $filterName,
                'from' => 'Cansativa (+49 123456789)',
            ];

            $sentTo = 0;
            if (!empty($newsletter->contact_ids)) {
                $contactIds = json_decode($newsletter->contact_ids, true);
                if (is_array($contactIds)) {
                    $sentTo = count($contactIds);
                }
            }

            $contactCategory = null;
            if ($newsletter->contact_type_id) {
                $contactType = ContactTypes::find($newsletter->contact_type_id);
                if ($contactType) {
                    $contactFlag = strtoupper($newsletter->contact_flag ?? 'B2C');
                    $contactCategory = $contactFlag . ' - ' . ucwords(strtolower($contactType->contact_type_name));
                }
            }

            $appliedFilters = [];
            if (!empty($newsletter->filters)) {
                $appliedFilters = json_decode($newsletter->filters, true);
                if (!is_array($appliedFilters)) {
                    $appliedFilters = [];
                }
            }

            $stats = WaNewsLetterStats::where('campaign_id', $newsletter->id)->first();
            $deliveredTo = $stats ? $stats->total_delivered : 0;
            $reads = $stats ? $stats->total_opened : 0;
            $optOuts = $stats ? $stats->total_failed : 0;

            $clicks = WaCampaignInteractions::where('campaign_id', $newsletter->id)
                ->where('interaction_type', 'click')
                ->count();

            $deliveryRate = $sentTo > 0 ? round(($deliveredTo / $sentTo) * 100, 2) : 0;
            $readRate = $sentTo > 0 ? round(($reads / $sentTo) * 100, 2) : 0;
            $clickRate = $sentTo > 0 ? round(($clicks / $sentTo) * 100, 2) : 0;
            $optOutRate = $sentTo > 0 ? round(($optOuts / $sentTo) * 100, 2) : 0;

            $metrics = [
                'sent_to' => $sentTo,
                'contact_category' => $contactCategory,
                'applied_filters' => $appliedFilters,
                'delivered_to' => $deliveredTo,
                'delivery_rate' => $deliveryRate,
                'reads' => $reads,
                'read_rate' => $readRate,
                'clicks' => $clicks,
                'click_rate' => $clickRate,
                'opt_outs' => $optOuts,
                'opt_out_rate' => $optOutRate
            ];

            $undelivered = [];

            $failedTrackingMessages = WaCampaignMessageTracking::where('campaign_id', $newsletter->id)
                ->where('status', 'failed')
                ->pluck('message_id')
                ->toArray();

            if (!empty($failedTrackingMessages)) {
                $failedMessages = Message::whereIn('wamid', $failedTrackingMessages)
                    ->where('status', 'failed')
                    ->whereNotNull('error_code')
                    ->select('error_code', 'error_message', DB::raw('COUNT(*) as total'))
                    ->groupBy('error_code', 'error_message')
                    ->get();

                $undelivered = $failedMessages->map(function ($message) use ($sentTo) {
                    $percentage = $sentTo > 0 ? round(($message->total / $sentTo) * 100, 2) : 0;

                    return [
                        'error_code' => $message->error_code,
                        'error_message' => $message->error_message,
                        'total' => (int) $message->total,
                        'percentage' => $percentage
                    ];
                })->toArray();
            }

            $readsChart = [];
            $clicksChart = [];

            $hours = [];
            for ($i = 8; $i < 24; $i++) {
                $hours[] = sprintf('%02d:00', $i);
            }
            for ($i = 0; $i < 8; $i++) {
                $hours[] = sprintf('%02d:00', $i);
            }

            $hourlyReads = WaCampaignMessageTracking::where('campaign_id', $newsletter->id)
                ->where('status', 'read')
                ->select(DB::raw('EXTRACT(HOUR FROM read_at) as hour'), DB::raw('COUNT(*) as total'))
                ->whereNotNull('read_at')
                ->groupBy(DB::raw('EXTRACT(HOUR FROM read_at)'))
                ->pluck('total', 'hour')
                ->toArray();

            $hourlyClicks = WaCampaignInteractions::where('campaign_id', $newsletter->id)
                ->where('interaction_type', 'click')
                ->select(DB::raw('EXTRACT(HOUR FROM created_at) as hour'), DB::raw('COUNT(*) as total'))
                ->groupBy(DB::raw('EXTRACT(HOUR FROM created_at)'))
                ->pluck('total', 'hour')
                ->toArray();

            foreach ($hours as $hour) {
                $hourInt = (int)substr($hour, 0, 2);

                $readsChart[$hour] = [
                    'hour_range' => sprintf('%s - %02d:59', $hour, $hourInt),
                    'total' => $hourlyReads[$hourInt] ?? 0
                ];
            }

            foreach ($hours as $hour) {
                $hourInt = (int)substr($hour, 0, 2);

                $clicksChart[$hour] = [
                    'hour_range' => sprintf('%s - %02d:59', $hour, $hourInt),
                    'total' => $hourlyClicks[$hourInt] ?? 0
                ];
            }

            $chart = [
                "reads" => $readsChart,
                "clicks" => $clicksChart,
            ];

            $response = [
                'newsletter' => $newsletterData,
                'metrics' => $metrics,
                'undelivered' => $undelivered,
                'chart' => $chart
            ];

            return $this->successResponse($response, 'Newsletter metrics retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Newsletter not found', 404);
        } catch (\Exception $e) {
            Log::error('Error retrieving newsletter metrics: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to retrieve newsletter metrics: ' . $e->getMessage(),
                500
            );
        }
    }
}
