<?php

namespace Modules\Dashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Modules\B2BContact\Models\B2BContacts;
use Modules\B2BContact\Models\HistoryExports as B2BHistoryExports;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignContact;
use Modules\NewContactData\Models\Contacts;
use Modules\NewContactData\Models\ContactTypes;
use Modules\NewContactData\Models\HistoryExports as B2CHistoryExports;
use Modules\Whatsapp\Http\Controllers\WhatsappMessageController;
use Modules\Whatsapp\Models\Message;
use Modules\Whatsapp\Models\MessageAssignment;
use Modules\Whatsapp\Services\WhatsappAPIService;
use Modules\WhatsappNewsletter\Models\WaCampaignInteractions;
use Modules\WhatsappNewsletter\Models\WaCampaignMessageTracking;
use Modules\WhatsappNewsletter\Models\WaNewsLetter;
use Modules\NewContactData\Helpers\TranslatorHelper;

class DashboardController extends Controller
{
    /**
     * global
     */
    private $request_data;
    /**
     * Get dashboard data based on type and channel
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->request_data = $request;
            $validated = $request->validate([
                'type' => ['required', Rule::in(['b2b', 'b2c'])],
                'channel' => ['required', Rule::in(['whatsapp', 'email'])],
                'period' => ['sometimes', Rule::in(['7d', '30d', '90d', '1y'])],
            ]);

            $type = $validated['type'];
            $channel = $validated['channel'];
            $period = $validated['period'] ?? '30d';

            $dashboardData = $this->getDashboardData($type, $channel, $period);

            return response()->json([
                'status' => 'success',
                'message' => 'Dashboard data retrieved successfully',
                'data' => $dashboardData
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid parameters',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function charts(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'type' => ['required', Rule::in(['b2b', 'b2c'])],
                'channel' => ['required', Rule::in(['whatsapp', 'email'])],
                'chart' => ['required', Rule::in(['engagement', 'unsubscribe', 'delivery_rate', 'bounce'])],
                'period' => ['sometimes', Rule::in(['7d', '30d', '90d', '1y', 'thisMonth', 'allTime', 'custom'])],
                'startDate' => ['required_if:period,custom', 'date'],
                'endDate'   => ['required_if:period,custom', 'date', 'after:startDate'],
            ]);

            $chartData = $this->getChartData(
                $validated['type'],
                $validated['channel'], 
                $validated['chart'],
                $validated['period'] ?? 'thisMonth',
                $validated['startDate'] ?? null,
                $validated['endDate'] ?? null
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Chart data retrieved successfully',
                'data' => $chartData
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve chart data: ', [$e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve chart data',
            ], 400);
        }
    }

    /**
     * Get mock dashboard data
     */
    private function getDashboardData(string $type, string $channel, string $period): array
    {
        $data = [
            'type' => $type,
            'channel' => $channel,
            'period' => $period,
            'last_updated' => now()->toISOString(),
        ];

        $data = array_merge($data, $this->getDataWithoutCharts($channel, $type));

        $data['whatsapp_center'] = $this->getDashboardOverview();
        $data['recent_campaigns'] = $this->getRecentCampaigns($channel, $type);
        $data['recent_exports'] = $this->getRecentExports($type);

        return $data;
    }

    /**
     * Get chart data based on specific filters
     */
    private function getChartData(string $type, string $channel, string $chart, string $period, $startDate=null, $endDate=null): array
    {
        $data = [
            'type' => $type,
            'channel' => $channel,
            'chart' => $chart,
            'period' => $period,
            'last_updated' => now()->toISOString(),
        ];

        if ($channel === 'whatsapp') {
            $data['chart_data'] = $this->getWhatsappDataChart($type, $chart, $period, $startDate, $endDate);
        } else {
            $data['chart_data'] = $this->getEmailChartData($type, $chart, $period, $startDate, $endDate);
        }

        return $data;
    }

    private function getWhatsappDataChart($type, $chart, $period=null, $startDate, $endDate)
    {
        switch ($chart) {
            case 'engagement':
                return $this->getEngagementByContactType($type, $period, $startDate, $endDate);
                break;
            case 'unsubscribe':
                return $this->getUnsubscribeByContactType($type, $period, $startDate, $endDate);
                break;
            case 'delivery_rate':
                return $this->getDeliveryRateByContactType($type, $period, $startDate, $endDate);
                break;
            default:
                # code...
                break;
        }
    }
    
    private function getEmailChartData($type, $chart, $period=null, $startDate, $endDate)
    {
        if ($chart == 'bounce') {
            return $this->getEmailBouncesByType($type, $period, $startDate, $endDate);
        }else{
            return $this->getEmailDataByContactType($type, $period, $chart, $startDate, $endDate);
        }
        // switch ($chart) {
        //     case 'engagement':
        //         break;
        //     case 'unsubscribe':
        //         return $this->getEmailUnsubscribeByContactType($type, $period);
        //         break;
        //     case 'delivery_rate':
        //         return $this->getEmailDeliveryRateByContactType($type, $period);
        //         break;
        //     case 'bounce':
        //         break;
        //     default:
        //         # code...
        //         break;
        // }
    }

    /**
     * Get B2B chart data
     */
    private function getEngagementByContactType($type, $period, $startDate, $endDate)
    {
        $whatsappService = new WhatsappAPIService;
        $waController = new WhatsappMessageController($whatsappService);
        $waRequest = new Request();

        $dateRange = $this->formatDateRangeforChart($period, $startDate, $endDate);
        $waRequest->merge([
            'contactFlag' => $type,
            'startDate'   => $dateRange['startDate'],
            'endDate'   => $dateRange['endDate'],
        ]);

        $response = $waController->getDashboardEngagementByContactType($waRequest);
        $response = $response->getData(true);
        
        $mapping = [
            "pharmacy" => "Pharmacies",
            "supplier" => "Suppliers",
            "generalNewsletter" => "General Newsletter",
            "community" => "Community",
            "pharmacyDatabase" => "Pharmacy Databases",
        ];

        $transformed = collect($response['data'])
                ->except('contactFlag')
                ->map(function ($item, $key) use ($mapping) {
                    return [
                        "category" => $mapping[$key] ?? ucfirst($key),
                        "opened"   => $item['totalOpened'],
                        "clicked"  => $item['totalClicked'],
                    ];
                })
                ->values()
                ->toArray();

        return $transformed;
    }

    private function getUnsubscribeByContactType($type, $period, $startDate, $endDate)
    {
        $whatsappService = new WhatsappAPIService;
        $waController = new WhatsappMessageController($whatsappService);
        $waRequest = new Request();

        $dateRange = $this->formatDateRangeforChart($period, $startDate, $endDate);
        $waRequest->merge([
            'contactFlag' => $type,
            'startDate'   => $dateRange['startDate'],
            'endDate'   => $dateRange['endDate'],
        ]);

        $response = $waController->getDashboardUnsubscribeByContactType($waRequest);
        $response = $response->getData(true);
        
        $mapping = [
            "pharmacy" => "Pharmacies",
            "supplier" => "Suppliers",
            "generalNewsletter" => "General Newsletter",
            "community" => "Community",
            "pharmacyDatabase" => "Pharmacy Databases",
        ];

        $transformed = collect($response['data'])
                ->except('contactFlag')
                ->map(function ($item, $key) use ($mapping) {
                    return [
                        "category" => $mapping[$key] ?? ucfirst($key),
                        "count"   => $item['count'],
                    ];
                })
                ->values()
                ->toArray();

        return $transformed;
    }

    private function getDeliveryRateByContactType($type, $period, $startDate, $endDate)
    {
        $whatsappService = new WhatsappAPIService;
        $waController = new WhatsappMessageController($whatsappService);
        $waRequest = new Request();

        $dateRange = $this->formatDateRangeforChart($period, $startDate, $endDate);
        $waRequest->merge([
            'contactFlag' => $type,
            'startDate'   => $dateRange['startDate'],
            'endDate'   => $dateRange['endDate'],
        ]);

        $response = $waController->getDashboardDeliveryRateByContactType($waRequest);
        $response = $response->getData(true);
        
        $mapping = [
            "pharmacy" => "Pharmacies",
            "supplier" => "Suppliers",
            "generalNewsletter" => "General Newsletter",
            "community" => "Community",
            "pharmacyDatabase" => "Pharmacy Databases",
        ];

        $transformed = collect($response['data'])
                ->except('contactFlag')
                ->map(function ($item, $key) use ($mapping) {
                    return [
                        "category" => $mapping[$key] ?? ucfirst($key),
                        "rate"   => $item['deliveryRate'],
                    ];
                })
                ->values()
                ->toArray();

        return $transformed;
    }

    /**
     * Get B2B specific data (without charts)
     */
    private function getDataWithoutCharts(string $channel, $type): array
    {
        if ($type == 'b2c') {
            $contacts['total'] = Contacts::where('contacts.is_deleted', false)->count();
            $contacts['new_last_30_days'] = Contacts::where('contacts.is_deleted', false)->where('created_date',  '>=', Carbon::now()->subDays(30))->count();
        }else{
            $contacts['total'] = B2BContacts::where('contacts.is_deleted', false)->count();
            $contacts['new_last_30_days'] = B2BContacts::where('contacts.is_deleted', false)->where('created_date',  '>=', Carbon::now()->subDays(30))->count();
        }

        if ($channel == 'whatsapp') {
            $waMetrics = $this->getWaDashboardMetrics($type);
            $results['whatsapp_metrics'] = $waMetrics;
        }

        if ($channel == 'email') {
            $emailMetrics = $this->getEmailDashboardMetrics($type);
            $results['email_metrics'] = $emailMetrics;
        }

        $results['contacts'] = $contacts;
        
        return $results;
    }

    /**
     * Get recent campaigns
     */
    private function getRecentCampaigns($channel, $type): array
    {
        if ($channel == 'whatsapp') {
            $newsletters = WaNewsLetter::with('stats')
            ->where('contact_flag', $type)
            ->latest()
            ->limit(5)
            ->get();
    
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

            return $results->toArray();
        }

        if ($channel == 'email') {
            $campaigns = Campaign::where('recipient_type', $type)
            ->latest()
            ->limit(5)
            ->get();

            $results = $campaigns->map(function ($campaign) {
                $totalCampaignSent = CampaignContact::where('campaign_id', $campaign->id)->count();
                $sentTo = CampaignContact::where('campaign_id', $campaign->id)->where('status', '!=', 'draft')->count();
                $delivered = CampaignContact::where('campaign_id', $campaign->id)->whereNotIn('status', ['draft', 'softbounce', 'hardbounce', 'softBounce', 'hardBounce'])->count();
                $clicks = CampaignContact::where('campaign_id', $campaign->id)->where('status', '=', 'click')->count();
                $unsubscribed = CampaignContact::where('campaign_id', $campaign->id)->where('status', '=', 'unsubscribed')->count();
    
                $sentToPercent = $totalCampaignSent ? round(($sentTo / $totalCampaignSent) * 100, 2) : 0;
                $deliveredPercent = $totalCampaignSent ? round(($delivered / $totalCampaignSent) * 100, 2) : 0;
                $clicksPercent = $totalCampaignSent ? round(($clicks / $totalCampaignSent) * 100, 2) : 0;
                $unsubscribedPercent = $totalCampaignSent ? round(($unsubscribed / $totalCampaignSent) * 100, 2) : 0;
    
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->campaign_name,
                    'sent_to' => $sentTo,
                    'delivered' => $delivered,
                    'clicks' => $clicks,
                    'unsubscribed' => $unsubscribed,
                    'sent_at' => $campaign->created_date ? $campaign->created_date->format('Y-m-d H:i:s') : null,
                    'status' => 'complete',
                    'sent_to_percent' => $sentToPercent,
                    'delivered_percent' => $deliveredPercent,
                    'clicks_percent' => $clicksPercent,
                    'unsubscribed_percent' => $unsubscribedPercent,
                ];
            });

            return $results->toArray();
        }

        return [];
    }

    /**
     * Get recent exports
     */
    private function getRecentExports($type)
    {
        $exports = null;
        if ($type == 'b2b') {
            $exports = B2BHistoryExports::latest()->orderBy('created_date','desc')->take(5)->get()->toArray();
        }
        if ($type == 'b2c') {
            $exports = B2CHistoryExports::latest()->orderBy('created_date','desc')->take(5)->get()->toArray();
        }
        if (!$exports) {
            return null;
        }

        $request = $this->request_data; 

        $exported = [];

        foreach($exports as $item){
            $lang = $request->header('Lang');  
            $item['lang'] = $lang;
            if($lang == 'de'){
                $meticulous = ucwords(str_replace('-', ' ',$item['contact_type']));
                $item['contact_type'] = TranslatorHelper::getTranslate($meticulous,$lang);
            }
            $exported[] = $item;
        }

        return $exported;
    }

    public function getWaDashboardMetrics($type)
    {
        try {
            if ($type == 'b2c') {
                $reachableContacts = Contacts::whereNotNull('phone_no')
                    ->where('phone_no', '!=', '')
                    ->where('is_deleted', false)
                    ->count();
                
                $totalContacts = Contacts::where('is_deleted', false)->count();
                $totalSubscribersCount = Contacts::where('whatsapp_subscription', true)->where('is_deleted', false)->count();
                $totalUnsubscribesCount = Contacts::where('whatsapp_subscription', false)->where('is_deleted', false)->count();
            }else{
                $reachableContacts = B2BContacts::whereNotNull('phone_no')
                    ->where('phone_no', '!=', '')
                    ->where('is_deleted', false)
                    ->count();
                $totalContacts = B2BContacts::where('is_deleted', false)->count();
                $totalSubscribersCount = B2BContacts::where('whatsapp_subscription', true)->where('is_deleted', false)->count();
                $totalUnsubscribesCount = B2BContacts::where('whatsapp_subscription', false)->where('is_deleted', false)->count();
            }

            $subscribersPercentage = $totalContacts > 0 ? round(($totalSubscribersCount / $totalContacts) * 100, 2) : 0;
            $unsubscribesPercentage = $totalContacts > 0 ? round(($totalUnsubscribesCount / $totalContacts) * 100, 2) : 0;

            $totalMessages = Message::where('contact_flag', $type)->count();

            $messageSent = Message::where('contact_flag', $type)->where('status', 'sent')
                ->count();

            $messagesDeliveredCount = Message::where('contact_flag', $type)->where('status', 'delivered')
                ->count();
            $deliveredPercentage = $totalMessages > 0 ? round(($messagesDeliveredCount / $totalMessages) * 100, 2) : 0;

            $messagesOpenedCount = Message::where('contact_flag', $type)->where('status', 'read')
                ->count();
            $openedPercentage = $totalMessages > 0 ? round(($messagesOpenedCount / $totalMessages) * 100, 2) : 0;

            $linkClicks = WaCampaignInteractions::where('contact_flag', $type)->where('interaction_type', 'click')
                ->count();

            $campaignsSent = WaCampaignMessageTracking::where('contact_flag', $type)->where('status', 'sent')
                ->distinct('campaign_id')
                ->count('campaign_id');

            return [
                'reachable_contacts' => $reachableContacts,
                'total_subscribers' => $totalSubscribersCount,
                'subscriber_percentage' => $subscribersPercentage,
                'total_unsubscribes' => $totalUnsubscribesCount,
                'unsubscribe_percentage' => $unsubscribesPercentage,
                'messages_sent' => $messageSent,
                'messages_delivered' => $messagesDeliveredCount,
                'delivery_percentage' => $deliveredPercentage,
                'messages_opened' => $messagesOpenedCount,
                'open_percentage' => $openedPercentage,
                'link_clicks' => $linkClicks,
                'campaigns_sent' => $campaignsSent,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting dashboard metrics: ' . $e->getMessage());
            return [
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    public function getDashboardOverview()
    {
        try {
            $unreadMessages = Message::where('is_read', false)
                ->where('direction', 0)
                ->where('status', '!=', 'failed')
                ->count();

            $totalMessagesToday = Message::whereDate('created_at', today())
                ->where('status', '!=', 'failed')
                ->count();

            $activeConversations = MessageAssignment::where('message_status', MessageAssignment::MESSAGE_STATUS_IN_PROGRESS)
                ->count();

            return [
                'unread_messages' => $unreadMessages,
                'total_messages_today' => $totalMessagesToday,
                'active_conversations' => $activeConversations,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting dashboard overview: ' . $e->getMessage());
            return [
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }

    private function getEmailDashboardMetrics($recipient_type){
        $baseQuery = CampaignContact::where('contact_type', $recipient_type);

        $totalEmailReachable = (clone $baseQuery)->distinct('contact_id')->count();
        $totalSubscribed = (clone $baseQuery)->where('status', '<>', 'unsubscribed')->distinct('contact_id')->count();
        $totalUnsubscribed = (clone $baseQuery)->where('status', 'unsubscribed')->distinct('contact_id')->count();
        $totalEmailCampaign = (clone $baseQuery)->distinct('campaign_id')->count();
        $totalRecipient = (clone $baseQuery)->where('status_message', 1)->distinct('contact_id')->count();
        $totalOpens = (clone $baseQuery)->where(['status_message' => 1, 'status' => 'opened'])->distinct('contact_id')->count();
        $totalUniqueClicks = (clone $baseQuery)->where(['status_message' => 1, 'status' => 'click'])->distinct('contact_id')->count();
        $totalBounces = (clone $baseQuery)->whereIn('status', ['softBounce', 'hardBounce'])->where('status_message', 1)->distinct('contact_id')->count();

        // Calculate percentages based on correct base values
        $subscribedPercentage = $totalEmailReachable > 0 ? round(($totalSubscribed / $totalEmailReachable) * 100, 1) : 0;
        $unsubscribedPercentage = $totalEmailReachable > 0 ? round(($totalUnsubscribed / $totalEmailReachable) * 100, 1) : 0;
        $opensPercentage = $totalRecipient > 0 ? round(($totalOpens / $totalRecipient) * 100, 1) : 0;
        $uniqueClicksPercentage = $totalRecipient > 0 ? round(($totalUniqueClicks / $totalRecipient) * 100, 1) : 0;
        $bouncesPercentage = $totalRecipient > 0 ? round(($totalBounces / $totalRecipient) * 100, 1) : 0;

        return [
                'reachable_contacts' => $totalEmailReachable,
                'total_subscribers' => $totalSubscribed,
                'subscriber_percentage' => $subscribedPercentage,
                'total_unsubscribes' => $totalUnsubscribed,
                'unsubscribe_percentage' => $unsubscribedPercentage,
                'newsletters_sent' => $totalEmailCampaign,
                'total_recipients' => $totalRecipient,
                'messages_opened' => $totalOpens,
                'open_percentage' => $opensPercentage,
                'link_clicks' => $totalUniqueClicks,
                'click_percentage' => $uniqueClicksPercentage,
                'bounces' => $totalBounces,
                'bounce_percentage' => $bouncesPercentage,
            ];
    }

    public function getEmailBouncesByType($type, $period, $startDate, $endDate)
    {
        $recipientType = $type ?? 'b2b';

        $dateRange = match (strtolower($period)) {
            '30d' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            '1y' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            default => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };

        $baseQuery = CampaignContact::where('contact_type', $recipientType)
            ->where('status', 'softBounce')
            ->where('status_message', 1);

            if ($dateRange !== 'allTime') {
                $baseQuery->whereBetween('cc.created_date', [$dateRange['startDate'], $dateRange['endDate']]);
            }
            
            $softBounces = $baseQuery->distinct('contact_id')
            ->count();

        $baseQuery = CampaignContact::where('contact_type', $recipientType)
            ->where('status', 'hardBounce')
            ->where('status_message', 1);

            if ($dateRange !== 'allTime') {
                $baseQuery->whereBetween('cc.created_date', [$dateRange['startDate'], $dateRange['endDate']]);
            }
            
            $hardBounces = $baseQuery->distinct('contact_id')
            ->count();

        $totalBounces = $softBounces + $hardBounces;
        $softPercentage = $totalBounces > 0 ? round(($softBounces / $totalBounces) * 100) : 0;
        $hardPercentage = $totalBounces > 0 ? round(($hardBounces / $totalBounces) * 100) : 0;

        return [
            [
                'type' => 'total_bounces',
                'count' => $totalBounces,
            ],
            [
                'type' => 'soft_bounces',
                'count' => $softBounces,
                'percentage' => $softPercentage
            ],
            [
                'type' => 'hard_bounces',
                'count' => $hardBounces,
                'percentage' => $hardPercentage
            ]
        ];
    }

    public function getEmailDataByContactType($type, $period, $chart, $startDate, $endDate)
    {
        $dateRange = $this->formatDateRangeforChart($period, $startDate, $endDate);

        $contactTypeMap = [
            1 => 'Pharmacies',
            2 => 'Suppliers',
            3 => 'General Newsletter',
            4 => 'Community',
            5 => 'Pharmacy Databases'
        ];

        if ($type == 'b2b') {
            $contactTypes = collect($contactTypeMap)->only([1,2,3]);
        }else{
            $contactTypes = collect($contactTypeMap)->only([4,5]);
        }

        switch ($chart) {
            case 'engagement':
                return $this->getEngagementEmailByContactType($contactTypes, $dateRange);
                break;
            case 'unsubscribe':
                return $this->getUnsubscribeEmailByContactType($contactTypes, $dateRange);
                break;
            case 'delivery_rate':
                return $this->getDeliveryRateEmailByContactType($contactTypes, $dateRange);
                break;
            default:
                return null;
                break;
        }
    }

    private function getEngagementEmailByContactType($contactTypes, $dateRange)
    {
        $baseQuery = DB::connection('pgsql_b2b_shared')->table('campaigns as c')
            ->join('campaign_contacts as cc', 'c.id', '=', 'cc.campaign_id')
            ->select(
                'c.contact_type_id',
                DB::raw("SUM(CASE WHEN cc.status = 'opened' THEN 1 ELSE 0 END) as total_opened"),
                DB::raw("SUM(CASE WHEN cc.status = 'click' THEN 1 ELSE 0 END) as total_clicked")
            )
            ->whereIn('c.contact_type_id', $contactTypes->keys())
            ->where('cc.status_message', 1)
            ->whereIn('cc.status', ['opened', 'click']);
        
            if ($dateRange !== 'allTime') {
                $baseQuery->whereBetween('cc.created_date', [$dateRange['startDate'], $dateRange['endDate']]);
            }

            $campaigns = $baseQuery->groupBy('c.contact_type_id')
            ->orderBy('c.contact_type_id')
            ->get()
            ->keyBy('contact_type_id');

        $chartData = collect($contactTypes)->map(function ($name, $id) use ($campaigns) {
            $row = $campaigns->get($id);

            return [
                'category' => $name,
                'opened'   => $row ? (int) $row->total_opened : 0,
                'clicked'  => $row ? (int) $row->total_clicked : 0,
            ];
        })->values();

        return $chartData;
    }

    private function getUnsubscribeEmailByContactType($contactTypes, $dateRange)
    {
        $baseQuery = DB::connection('pgsql_b2b_shared')->table('campaigns as c')
            ->join('campaign_contacts as cc', 'c.id', '=', 'cc.campaign_id')
            ->select(
                'c.contact_type_id',
                DB::raw("SUM(CASE WHEN cc.status = 'unsubscribed' THEN 1 ELSE 0 END) as total_unsubscribed"),
            )
            ->whereIn('c.contact_type_id', $contactTypes->keys())
            ->where('cc.status_message', 1)
            ->whereIn('cc.status', ['unsubscribed']);
        
            if ($dateRange !== 'allTime') {
                $baseQuery->whereBetween('cc.created_date', [$dateRange['startDate'], $dateRange['endDate']]);
            }

            $campaigns = $baseQuery->groupBy('c.contact_type_id')
            ->orderBy('c.contact_type_id')
            ->get()
            ->keyBy('contact_type_id');

        $chartData = collect($contactTypes)->map(function ($name, $id) use ($campaigns) {
            $row = $campaigns->get($id);

            return [
                'category' => $name,
                'count'   => $row ? (int) $row->total_unsubscribed : 0,
            ];
        })->values();

        return $chartData;
    }

    private function getDeliveryRateEmailByContactType($contactTypes, $dateRange)
    {
        $baseQuery = DB::connection('pgsql_b2b_shared')->table('campaigns as c')
            ->join('campaign_contacts as cc', 'c.id', '=', 'cc.campaign_id')
            ->select('c.contact_type_id', DB::raw('COUNT(cc.id) as total'))
            ->whereIn('c.contact_type_id', $contactTypes->keys())
            ->where('cc.status_message', 1);
        
            if ($dateRange !== 'allTime') {
                $baseQuery->whereBetween('cc.created_date', [$dateRange['startDate'], $dateRange['endDate']]);
            }

        $totalCampaigns = $baseQuery->groupBy('c.contact_type_id')
            ->get()
            ->keyBy('contact_type_id');


        $baseQuery = DB::connection('pgsql_b2b_shared')->table('campaigns as c')
            ->join('campaign_contacts as cc', 'c.id', '=', 'cc.campaign_id')
            ->select('c.contact_type_id', DB::raw('COUNT(cc.id) as success'))
            ->whereIn('c.contact_type_id', $contactTypes->keys())
            ->where('cc.status_message', 1)
            ->whereNotIn('cc.status', ['softBounce', 'hardBounce']);
        
            if ($dateRange !== 'allTime') {
                $baseQuery->whereBetween('cc.created_date', [$dateRange['startDate'], $dateRange['endDate']]);
            }

        $successCampaigns = $baseQuery->groupBy('c.contact_type_id')
            ->get()
            ->keyBy('contact_type_id');

        $chartData = collect($contactTypes)->map(function ($name, $id) use ($totalCampaigns, $successCampaigns) {
            $total   = $totalCampaigns->get($id)->total   ?? 0;
            $success = $successCampaigns->get($id)->success ?? 0;

            $rate = $total > 0 ? round(($success / $total) * 100, 1) : 0;

            return [
                'category' => $name,
                'rate'     => $rate,
            ];
        })->values();

        return $chartData;
    }

    private function formatDateRangeforChart($period, $startDate, $endDate)
    {
        switch ($period) {
            case 'custom':
                $dateRange['startDate'] = $startDate;
                $dateRange['endDate'] = $endDate;
                return $dateRange;
                break;
            case '7d':
                $dateRange['startDate'] = Carbon::now()->subDays(7)->toDateString();
                $dateRange['endDate'] = Carbon::now()->today()->toDateString();
                return $dateRange;
                break;
            case '30d':
                $dateRange['startDate'] = Carbon::now()->subDays(30)->toDateString();
                $dateRange['endDate'] = Carbon::now()->today()->toDateString();
                return $dateRange;
                break;
            case '90d':
                $dateRange['startDate'] = Carbon::now()->subMonths(3)->toDateString();
                $dateRange['endDate'] = Carbon::now()->today()->toDateString();
                return $dateRange;
                break;
            case '1y':
                $dateRange['startDate'] = Carbon::now()->subYears(1)->toDateString();
                $dateRange['endDate'] = Carbon::now()->today()->toDateString();
                return $dateRange;
                break;
            case 'thisMonth':
                $dateRange['startDate'] = Carbon::now()->startOfMonth()->toDateString();
                $dateRange['endDate'] = Carbon::now()->endOfMonth()->toDateString();
                return $dateRange;
                break;
            case 'allTime':
                return 'allTime';
                break;
            default:
                $dateRange['startDate'] = Carbon::now()->startOfMonth()->toDateString();
                $dateRange['endDate'] = Carbon::now()->endOfMonth()->toDateString();
                return $dateRange;
                break;
        }
    }
}