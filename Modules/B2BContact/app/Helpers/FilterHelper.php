<?php

namespace Modules\B2BContact\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\B2BContact\Models\B2BContacts;
use Modules\B2BContact\Models\ColumnMappings;
use Modules\Campaign\Models\CampaignContact;
use Modules\NewContactData\Models\Contacts;
use Modules\NewContactData\Models\ContactTypes;
use Modules\WhatsappNewsletter\Models\WaCampaignMessageTracking;

class FilterHelper
{
    public static function getFilterQuery($baseQuery, $filter, $table=null)
    {
        if (!$filter || !isset($filter['key'])) return $baseQuery;

        $mapDefaultCol = [
            'company_name'   => 'contact_name',
            'pharmacy_name'  => 'contact_name',
            'full_name'      => 'contact_name',
            'pharmacy_number'=> 'contact_no',
            'created_at'     => 'created_date',
            'creation_date'  => 'created_date',
        ];
        $filter['key'] = $mapDefaultCol[$filter['key']] ?? $filter['key'];

        if ($table) {
            $key = $table.'.'.$filter['key'];
        }else{
            $key = $filter['key'];
        }

        $include = filter_var($filter['include'], FILTER_VALIDATE_BOOLEAN);

        if ($filter['type'] == 'amount') {
            if ($include) {
                if(isset($filter['items']['min'], $filter['items']['max'])){
                    return $baseQuery->whereBetween($key, [$filter['items']['min'], $filter['items']['max']]);
                }

                if (isset($filter['items']['min'])) {
                    return $baseQuery->where($key, '>', $filter['items']['min']);
                }

                if (isset($filter['items']['max'])) {
                    return $baseQuery->where($key, '<', $filter['items']['max']);
                }
            }else{
                if(isset($filter['items']['min'], $filter['items']['max'])){
                    return $baseQuery->whereNotBetween($key, [$filter['items']['min'], $filter['items']['max']]);
                }

                if (isset($filter['items']['min'])) {
                    return $baseQuery->whereNot($key, '>', $filter['items']['min']);
                }

                if (isset($filter['items']['max'])) {
                    return $baseQuery->whereNot($key, '<', $filter['items']['max']);
                }
            }
        }

        if ($filter['type'] == 'day') {
            $substractDate = null;
            if ($filter['items']['unit'] == 'day' || $filter['items']['unit'] == 'days' || $filter['items']['unit'] == 'Day(s)') {
                $substractDate = Carbon::now()->subDays($filter['items']['value']);
            }
            if ($filter['items']['unit'] == 'week' || $filter['items']['unit'] == 'weeks' || $filter['items']['unit'] == 'Week(s)') {
                $substractDate = Carbon::now()->subWeeks($filter['items']['value']);
            }
            if ($filter['items']['unit'] == 'month' || $filter['items']['unit'] == 'months' || $filter['items']['unit'] == 'Month(s)') {
                $substractDate = Carbon::now()->subMonths($filter['items']['value']);
            }
            if ($filter['items']['unit'] == 'year' || $filter['items']['unit'] == 'years' || $filter['items']['unit'] == 'Year(s)') {
                $substractDate = Carbon::now()->subYears($filter['items']['value']);
            }

            if (!$substractDate) {
                return;
            }

            if ($include) {
                return $baseQuery->where($key, '>', $substractDate);
            }else{
                return $baseQuery->whereNot($key, '>', $substractDate);
            }
        }

        if ($filter['type'] == 'location') {
            $locations = explode(',', $filter['items']['value']);
            if ($include) {
                return $baseQuery->whereIn($key, $locations);
            }else{
                return $baseQuery->whereNotIn($key, $locations);
            }
        }

        if ($filter['type'] == 'creation') {
            if ($include) {
                if(isset($filter['items']['startDate'], $filter['items']['endDate'])){
                    return $baseQuery->whereBetween($key, [$filter['items']['startDate'], $filter['items']['endDate']]);
                }

                if (isset($filter['items']['startDate'])) {
                    return $baseQuery->where($key, '>', $filter['items']['startDate']);
                }

                if (isset($filter['items']['endDate'])) {
                    return $baseQuery->where($key, '<', $filter['items']['endDate']);
                }
            }else{
                if(isset($filter['items']['startDate'], $filter['items']['endDate'])){
                    return $baseQuery->whereNotBetween($key, [$filter['items']['startDate'], $filter['items']['endDate']]);
                }

                if (isset($filter['items']['startDate'])) {
                    return $baseQuery->whereNot($key, '>', $filter['items']['startDate']);
                }

                if (isset($filter['items']['endDate'])) {
                    return $baseQuery->whereNot($key, '<', $filter['items']['endDate']);
                }
            }
        }

        if ($filter['type'] == 'date') {
            if ($key == 'created_at') {
                $key = 'created_date';
            }

            if (isset($filter['items']['endDate'])) {
                $filter['items']['endDate'] = Carbon::parse($filter['items']['endDate'])->endOfDay();
            }
            
            if ($include) {
                if(isset($filter['items']['startDate'], $filter['items']['endDate'])){
                    return $baseQuery->whereBetween($key, [$filter['items']['startDate'], $filter['items']['endDate']]);
                }

                if (isset($filter['items']['startDate'])) {
                    return $baseQuery->where($key, '>', $filter['items']['startDate']);
                }

                if (isset($filter['items']['endDate'])) {
                    return $baseQuery->where($key, '<', $filter['items']['endDate']);
                }
            }else{
                if(isset($filter['items']['startDate'], $filter['items']['endDate'])){
                    return $baseQuery->whereNotBetween($key, [$filter['items']['startDate'], $filter['items']['endDate']]);
                }

                if (isset($filter['items']['startDate'])) {
                    return $baseQuery->whereNot($key, '>', $filter['items']['startDate']);
                }

                if (isset($filter['items']['endDate'])) {
                    return $baseQuery->whereNot($key, '<', $filter['items']['endDate']);
                }
            }
        }

        if ($filter['type'] == 'list') {
            if ($table == 'saved_filters' && $filter['key'] == 'contact_type') {
                $contact_type = [
                    'pharmacy'          => 1,
                    'supplier'          => 2,
                    'general newsletter'=> 3,
                    'community'         => 4,
                    'pharmacy databases'=> 5,
                ];

                $searchKeys = array_map(fn($v) => $contact_type[strtolower($v)] ?? null, $filter['items']);
                $baseQuery->whereIn('contact_type_id', $searchKeys);
                return $baseQuery;
            }

            if (isset($filter['operator'])) {
                $operatorValue = $filter['operator']['value'];
                $operator = self::mapFilterOperatorToSql($operatorValue);
                $baseQuery->where(function ($q) use ($filter, $operator, $key, $include, $operatorValue) {
                    if ($operatorValue === 'is_empty') {
                        if ($include) {
                            $q->whereNull($key)->orWhere($key, '=', '');
                        } else {
                            $q->whereNotNull($key)->orWhere($key, '!=', '');
                        }
                        return $q;
                    }

                    if ($operatorValue === 'is_not_empty') {
                        if ($include) {
                            $q->whereNotNull($key)->orWhere($key, '!=', '');
                        } else {
                            $q->whereNull($key)->orWhere($key, '=', '');
                        }
                        return $q;
                    }

                    foreach ($filter['items'] as $index => $item) {
                        $queryValue = self::mapFreetextValueToSql($operatorValue, $item);

                        if ($include) {
                            if ($index === 0) {
                                $q->where($key, $operator, $queryValue);
                            } else {
                                $q->orWhere($key, $operator, $queryValue);
                            }
                        } else {
                            if ($index === 0) {
                                $q->whereNot($key, $operator, $queryValue);
                            } else {
                                $q->orWhereNot($key, $operator, $queryValue);
                            }
                        }
                    }
                    return $q;
                });

                return $baseQuery;
            }

            if ($include) {

                if (isset($filter['items']) && $filter['key'] == 'subscription') {
                    foreach ($filter['items'] as $item) {
                        $key = null;
                        if ($item == 'Email Subscribers' || $item == 'email_subscription') {
                            $key = 'email_subscription';
                        }
                        if ($item == 'WhatsApp Subscribers' || $item == 'whatsapp_subscription') {
                            $key = 'whatsapp_subscription';
                        }
                        if (!$key) {
                            return;
                        }
                        $baseQuery->where($key, true);
                    }
                    return $baseQuery;
                }

                if (isset($filter['items'])) {
                    if ($filter['key'] == 'contactType') {
                        $contact_type = [
                            'pharmacy'=> 'pharmacy',
                            'supplier'=> 'supplier',
                            'general newsletter'=> 'general-newsletter',
                            'community'=> 'community',
                            'pharmacy databases'=> 'pharmacy-database'
                        ];
                        foreach ($filter['items'] as $key => $item) {
                            $searchKeys[] = $contact_type[strtolower($item)];
                        }

                        $baseQuery->whereIn('contact_type', $searchKeys);
                        return $baseQuery;
                    }

                    if ($filter['key'] == 'exportType') {
                        $export_type = [
                            '.xlsx'=> 'xlsx',
                            'e-mail'=> 'email',
                            'whatsapp'=> 'whatsapp',
                        ];
                        foreach ($filter['items'] as $key => $item) {
                            $searchKeys[] = $export_type[strtolower($item)];
                        }

                        $baseQuery->whereIn('export_to', $searchKeys);
                        return $baseQuery;
                    }

                    // if common filter type list
                    $placeholders = implode(',', array_fill(0, count($filter['items']), '?'));
                    $baseQuery->whereRaw($key.' ilike any (array['.$placeholders.'])', $filter['items']);
                    return $baseQuery;
                }
                
            }else{
                if (isset($filter['items']) && $filter['key'] == 'subscription') {
                    foreach ($filter['items'] as $item) {
                        $key = null;
                        if ($item == 'Email Subscribers' || $item == 'email_subscription') {
                            $key = 'email_subscription';
                        }
                        if ($item == 'WhatsApp Subscribers' || $item == 'whatsapp_subscription') {
                            $key = 'whatsapp_subscription';
                        }
                        if (!$key) {
                            return;
                        }
                        $baseQuery->whereNot($key, true);
                    }
                    return $baseQuery;
                }

                if (isset($filter['items'])) {
                    if ($filter['key'] == 'contactType') {
                        $contact_type = [
                            'pharmacy'=> 'pharmacy',
                            'supplier'=> 'supplier',
                            'general newsletter'=> 'general-newsletter',
                            'community'=> 'community',
                            'pharmacy databases'=> 'pharmacy-database'
                        ];
                        foreach ($filter['items'] as $key => $item) {
                            $searchKeys[] = $contact_type[strtolower($item)];
                        }

                        $baseQuery->whereNotIn('contact_type', $searchKeys);
                        return $baseQuery;
                    }

                    if ($filter['key'] == 'exportType') {
                        $export_type = [
                            '.xlsx'=> 'xlsx',
                            'e-mail'=> 'email',
                            'whatsapp'=> 'whatsapp',
                        ];
                        foreach ($filter['items'] as $key => $item) {
                            $searchKeys[] = $export_type[strtolower($item)];
                        }

                        $baseQuery->whereNotIn('export_to', $searchKeys);
                        return $baseQuery;
                    }

                    // if common filter type list
                    $placeholders = implode(',', array_fill(0, count($filter['items']), '?'));
                    $baseQuery->whereRaw($filter['key'].' not ilike all (array['.$placeholders.'])', $filter['items']);
                    return $baseQuery;
                }
            }
        }

        if ($filter['type'] == 'boolean') {
            if ($include) {
                return $baseQuery->where($key, $filter['items']['value']);
            }else{
                return $baseQuery->whereNot($key, $filter['items']['value']);
            }
        }

        if ($filter['type'] == 'array') {
            if ($include) {
                return $baseQuery->whereIn($key, $filter['items']['value']);
            }else{
                return $baseQuery->whereNotIn($key, $filter['items']['value']);
            }
        }

        if ($filter['type'] == 'freetext') {
            $operator = self::mapFilterOperatorToSql($filter['items']['operator']);
            $query = self::mapFreetextValueToSql($filter['items']['operator'], $filter['items']['value']);

            if ($include) {
                if ($filter['items']['operator'] == 'empty') {
                    $baseQuery->whereNull($key);
                    return $baseQuery->orWhere($key, $operator, '');
                }
                if ($filter['items']['operator'] == 'not_empty') {
                    $baseQuery->whereNotNull($key);
                    return $baseQuery->orWhere($key, $operator, '');
                }
                
                return $baseQuery->where($key, $operator, $query);
            }else{
                if ($filter['items']['operator'] == 'empty') {
                    $baseQuery->whereNotNull($key);
                    return $baseQuery->orWhere($key, '!=', '');
                }
                if ($filter['items']['operator'] == 'not_empty') {
                    $baseQuery->whereNull($key);
                    return $baseQuery->orWhere($key, '=', '');
                }

                return $baseQuery->whereNot($key, $operator, $query);
            }
        }

        if ($filter['type'] == 'campaign') {
            // get contact id by campaign filter to filter data
            if($key == 'email_campaign'){
                $contactIds = self::getEmailCampaignContactIds($filter['items']['subject'], null, $filter['items']['frequency'], $filter['items']['date']);
            }

            if($key == 'whatsapp_campaign'){
                $contactIds = self::getWhatsappCampaignContactIds($filter['items']['subject'], null, $filter['items']['frequency'], $filter['items']['date']);
            }

            if ($include) {
                return $baseQuery->whereIn('id', $contactIds);
            }else{
                return $baseQuery->whereNotIn('id', $contactIds);
            }
        }
        
        //return $baseQuery;
    }

    private static function getWhatsappCampaignContactIds($status, $campaignId, $frequency, $date)
    {
        $frequencyOperator = self::mapFrequencyOperatorToSql($frequency['operator']);
        $dateOperator = self::mapFilterOperatorToSql($date['operator']);
        $statusOperator = self::mapWhatsappCampaignEventToStatus($status);

        // $queryCampaign = WaCampaignMessageTracking::where('status', $statusOperator)
        // ->where('campaign_id', $campaignId)
        // ->select('contact_id')
        // ->groupBy('contact_id', 'campaign_id')
        // ->havingRaw("COUNT(*) $frequencyOperator ?", [$frequency['value']]);

        $queryCampaign = WaCampaignMessageTracking::where('status', $statusOperator)
        ->select('contact_id')
        ->groupBy('contact_id')
        ->havingRaw("COUNT(*) $frequencyOperator ?", [$frequency['value']]);

        if ($date['operator'] == 'overallTime') {
            return $queryCampaign->distinct()->pluck('contact_id')->toArray();
        }

        if ($date['operator'] == 'fixedPeriod') {
            $queryCampaign->whereBetween('created_at', [$date['startDate'], $date['endDate']]);
        }

        if ($date['operator'] == 'today') {
            $queryCampaign->whereDate('created_at', Carbon::today());
        }

        if ($date['operator'] == 'yesterday') {
            $queryCampaign->whereDate('created_at', Carbon::yesterday());
        }
        
        if ($date['operator'] == 'inTheLast' || $date['operator'] == 'moreThan') {
            if ($date['unit'] == 'day') {
                $substractDate = Carbon::now()->subDays($date['value']);
            }
            if ($date['unit'] == 'month') {
                $substractDate = Carbon::now()->subMonths($date['value']);
            }
            if ($date['unit'] == 'year') {
                $substractDate = Carbon::now()->subYears($date['value']);
            }
            $queryCampaign->whereDate('created_at', $dateOperator, $substractDate);
        }
        
        //  exact_date, before, after
        if(in_array($date['operator'], ['exactDate', 'before', 'after'])){
            $queryCampaign->whereDate('created_at', $dateOperator, $date['value']);
        }

        return $queryCampaign->distinct()->pluck('contact_id')->toArray();
    }

    private static function getEmailCampaignContactIds($status, $campaignId, $frequency, $date)
    {
        $frequencyOperator = self::mapFrequencyOperatorToSql($frequency['operator']);
        $dateOperator = self::mapFilterOperatorToSql($date['operator']);
        $statusOperator = self::mapEmailCampaignEventToStatus($status);

        if ($status == 'received' || $status == 'not_received') {
            $queryCampaign = CampaignContact::whereIn('status', $statusOperator);
        }else{
            $queryCampaign = CampaignContact::where('status', $statusOperator);
        }
    
        // $queryCampaign->where('campaign_id', $campaignId)
        // ->select('contact_id')
        // ->groupBy('contact_id', 'campaign_id')
        // ->havingRaw("COUNT(*) $frequencyOperator ?", [$frequency['value']]);

        $queryCampaign->select('contact_id')
        ->groupBy('contact_id')
        ->havingRaw("COUNT(*) $frequencyOperator ?", [$frequency['value']]);

        if ($date['operator'] == 'overallTime') {
            return $queryCampaign->distinct()->pluck('contact_id')->toArray();
        }
        
        if ($date['operator'] == 'fixedPeriod') {
            $queryCampaign->whereBetween('created_date', [$date['startDate'], $date['endDate']]);
        }

        if ($date['operator'] == 'today') {
            $queryCampaign->whereDate('created_date', Carbon::today());
        }

        if ($date['operator'] == 'yesterday') {
            $queryCampaign->whereDate('created_date', Carbon::yesterday());
        }
        
        if ($date['operator'] == 'inTheLast' || $date['operator'] == 'moreThan') {
            if ($date['unit'] == 'day') {
                $substractDate = Carbon::now()->subDays($date['value']);
            }
            if ($date['unit'] == 'month') {
                $substractDate = Carbon::now()->subMonths($date['value']);
            }
            if ($date['unit'] == 'year') {
                $substractDate = Carbon::now()->subYears($date['value']);
            }
            $queryCampaign->whereDate('created_date', $dateOperator, $substractDate);
        }
        
        //  exact_date, before, after
        if(in_array($date['operator'], ['exactDate', 'before', 'after'])){
            $queryCampaign->whereDate('created_date', $dateOperator, $date['value']);
        }

        return $queryCampaign->distinct()->pluck('contact_id')->toArray();
    }

    public static function getDataByFrequencyCap($frequencyCap, $newsletterChannel, $isApplyFreq)
    {
        if ($frequencyCap['unit'] == 'days') {
            $substractDate = Carbon::now()->subDays($frequencyCap['timeWindow']);
        }
        if ($frequencyCap['unit'] == 'months') {
            $substractDate = Carbon::now()->subMonths($frequencyCap['timeWindow']);
        }
        if ($frequencyCap['unit'] == 'years') {
            $substractDate = Carbon::now()->subYears($frequencyCap['timeWindow']);
        }

        $contactsWaCampaign = [];
        $contactsNewsCampaign = [];

        if ($newsletterChannel['whatsapp'] || $isApplyFreq) {
            $contactsWaCampaign = WaCampaignMessageTracking::select('contact_id')
            ->groupBy('contact_id')
            ->havingRaw("COUNT(*) = ?", [$frequencyCap['maxTimes']])
            ->whereDate('created_at', '>', $substractDate)
            ->distinct()
            ->pluck('contact_id')->toArray();
        }

        if ($newsletterChannel['email'] || $isApplyFreq) {
            $contactsNewsCampaign = CampaignContact::select('contact_id')
            ->groupBy('contact_id')
            ->havingRaw("COUNT(*) = ?", [$frequencyCap['maxTimes']])
            ->whereDate('created_date', '>', $substractDate)
            ->distinct()->pluck('contact_id')->toArray();
        }

        $contactIds = array_unique(array_merge($contactsWaCampaign, $contactsNewsCampaign));
        return $contactIds;        
    }

    private static function mapFilterOperatorToSql($operator)
    {
        return match ($operator) {
            'is_equal_to', 'is_empty', 'exactly', 'exact_date', 'exactDate' => '=',
            'is_not_equal_to', 'is_not_empty' => '!=',
            'contains', 'starts_with','ends_with' => 'ilike',
            'not_contains', 'not_starts_with', 'not_ends_with' => 'not ilike',
            'at_least', 'in_the_last', 'inTheLast' => '>=',
            'at_most' => '<=',
            'less_than', 'after' => '>',
            'more_than', 'before', 'moreThan' => '<',
            default => '='
        };
    }

    private static function mapFrequencyOperatorToSql($operator)
    {
        return match ($operator) {
            'is_equal_to', 'exactly' => '=',
            'is_not_equal_to' => '!=',
            'at_least' => '>=',
            'at_most' => '<=',
            'more_than' => '>',
            'less_than' => '<',
            'equal', => '=',
            'notequal' => '!=',
            'atleast' => '>=',
            'atmost' => '<=',
            'morethan' => '>',
            'lessthan' => '<',
            default => '='
        };
    }

    private static function mapFreetextValueToSql($operator, $freetext)
    {
        if ($operator == 'contains' || $operator == 'not_contains') {
            return "%{$freetext}%";
        }
        if ($operator == 'starts_with' || $operator == 'not_starts_with') {
            return "{$freetext}%";
        } 
        
        if ($operator == 'ends_with' || $operator == 'not_ends_with') {
            return "%{$freetext}";
        }

        return $freetext;
    }

    public static function mapEmailCampaignEventToStatus($status)
    {
        return match ($status) {
            'sent', 'delivered', 'deliveredTo' => ['delivered'],
            'received' => ['delivered', 'sent'],
            'opened', 'read', 'reads' => ['opened'],
            'click', 'button_clicked' => ['click'],
            'unsubscribed', 'opt-out', 'optOut' => ['unsubscribed'],
            'soft_bounce' => ['softbounce'],
            'hard_bounce' => ['hardbounce'],
            'not_sent' => ['draft'],
            'not_received' => ['hardbounce', 'softbounce'],
            'not_opened' => ['delivered'],
            'button_not_clicked', 'not_click' => ['opened'],
            default => ['delivered']
        };
    }

    private static function mapWhatsappCampaignEventToStatus($status)
    {
        return match ($status) {
            'whatsapp_campaign_sent' => 'delivered',
            'whatsapp_campaign_received' => 'delivered',
            'whatsapp_campaign_opened' => 'read',
            'whatsapp_campaign_unsubscribed' => 'unsubscribed',
            'whatsapp_campaign_not_sent' => 'failed',
            'whatsapp_campaign_not_received' => 'sent',
            'whatsapp_campaign_not_opened' => 'delivered',
            default => 'delivered'
        };
    }

    public static function mapWhatsappCampaignStatusForReport($status)
    {
        return match ($status) {
            'sent', => ['sent'],
            'delivered', 'read' => ['delivered'],
            'received' => ['delivered'],
            'opened' => ['read'],
            'unsubscribed' => ['unsubscribed'],
            'not_sent' => ['failed'],
            'not_received' => ['sent'],
            'not_opened' => ['delivered'],
            default => ['delivered']
        };
    }

    public static function formatFilterForExportExcel($appliedFilters)
    {
        $rows = [];

        foreach ($appliedFilters as $filter) {
            $includeText = $filter['include'] === "true" ? "Include" : "Exclude";
            $category = $includeText . ": " . $filter['text'];
            $value = "";

            switch ($filter['type']) {
                case 'list':
                    if (!empty($filter['list'])) {
                        $value = implode(", ", $filter['list']);
                    }
                    break;

                case 'day':
                    if (!empty($filter['items']['value']) && !empty($filter['items']['unit'])) {
                        $value = $filter['items']['value'] . " " . $filter['items']['unit'];
                    }
                    break;

                case 'date':
                    if (!empty($filter['items']['startDateFormat']) && !empty($filter['items']['endDateFormat'])) {
                        $value = $filter['items']['startDateFormat'] . " - " . $filter['items']['endDateFormat'];
                    }
                    break;

                case 'amount':
                case 'like':
                    $min = $filter['items']['min'] ?? null;
                    $max = $filter['items']['max'] ?? null;
                    $extra = $filter['items']['value'] ?? null;

                    $parts = [];
                    if ($min !== null && $min !== "") {
                        $parts[] = "Min: " . number_format((float)$min, 0, ',', '.');
                    }
                    if ($max !== null && $max !== "") {
                        $parts[] = "Max: " . number_format((float)$max, 0, ',', '.');
                    }
                    if ($extra !== null && $extra !== "") {
                        $parts[] = $extra;
                    }

                    $value = implode(" ", $parts);
                    break;

                default:
                    if (is_array($filter['items'])) {
                        $value = implode(", ", $filter['items']);
                    } else {
                        $value = (string) $filter['items'];
                    }
                    break;
            }

            $rows[] = [
                'category' => $category,
                'value' => $value,
                'include' => $filter['include'] === "true" ? 1 : 0
            ];
        }

        // sort so Include first, then Exclude
        usort($rows, function ($a, $b) {
            return $b['include'] <=> $a['include'];
        });

        // drop helper "include"
        return array_map(function ($row) {
            return [
                'category' => $row['category'],
                'value' => $row['value']
            ];
        }, $rows);
    }

    public static function createBaseQuery($contact_type_id, $request){
        if (in_array($contact_type_id, [1,2,3])) {
            $baseQuery = B2BContacts::query();
        }else{
            $baseQuery = Contacts::query();
        }

        $baseQuery = $baseQuery->with(['customFieldValues.contactField'])
            ->selectRaw('ROW_NUMBER() OVER (ORDER BY id desc) as row_no, *')
            ->where('contact_type_id', $contact_type_id)
            ->where('contacts.is_deleted', false);
            
            if ($request->has('applied_filters')) {
                $checkDefaultColumn = array_merge(
                    ColumnMappings::where('contact_type_id', 1)->pluck('field_name')->toArray(),
                    ['subscription', 'contact_type', 'contactType', 'exportType']
                );

                foreach ($request->applied_filters as $key => $filter) {
                    if (in_array($filter['key'], $checkDefaultColumn)) {
                        self::getFilterQuery($baseQuery, $filter);
                    }else{
                         // custom field from contacts
                        $baseQuery->whereHas('customFieldValues', function ($queryContactField) use ($filter) {
                            $queryContactField->whereHas('contactField', function ($queryFieldValue) use ($filter) {
                                $filter['items'] = [$filter['key']];
                                $filter['key'] = 'field_name';
                                self::getFilterQuery($queryFieldValue, $filter);
                            });

                            $filter['key'] = 'value';
                            self::getFilterQuery($queryContactField, $filter);
                        });
                    }
                }
            }

            if ($request->has('is_frequence')) {
                $params = $request->all();
                $contactIds = self::getDataByFrequencyCap($params['frequency_cap'], $params['newsletter_channel'], $params['is_apply_freq']);
                $baseQuery->whereNotIn('id', $contactIds);
            }

        return $baseQuery;
    }
}
