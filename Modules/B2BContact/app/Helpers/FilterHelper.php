<?php

namespace Modules\B2BContact\Helpers;

use Carbon\Carbon;
use Modules\Campaign\Models\CampaignContact;
use Modules\WhatsappNewsletter\Models\WaCampaignMessageTracking;

class FilterHelper
{
    public static function getFilterQuery($baseQuery, $filter, $table=null)
    {
        if (!$filter || !isset($filter['key'])) return $baseQuery;

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
            if ($include) {                
                if (isset($filter['items']['list'])) {
                    foreach ($filter['items']['list'] as $item) {
                        $baseQuery->whereIn($key, [$filter['items']['list']]);
                    }
                    return $baseQuery;
                }

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
                    $baseQuery->whereIn($filter['key'], $filter['items']);
                    return $baseQuery;
                }
                
            }else{
                if (isset($filter['items']['list'])) {
                    foreach ($filter['items']['list'] as $item) {
                        $baseQuery->whereNotIn($key, [$filter['items']['list']]);
                    }
                    return $baseQuery;
                }

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
                    $baseQuery->whereNotIn($filter['key'], $filter['items']);
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
            $operator = self::mapDateOperatorToSql($filter['items']['operator']);
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
                $contactIds = self::getEmailCampaignContactIds($filter['items']['status'], $filter['items']['campaign_id'], $filter['items']['frequency'], $filter['items']['date']);
            }

            if($key == 'whatsapp_campaign'){
                $contactIds = self::getWhatsappCampaignContactIds($filter['items']['status'], $filter['items']['campaign_id'], $filter['items']['frequency'], $filter['items']['date']);
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
        $dateOperator = self::mapDateOperatorToSql($date['operator']);

        // $queryCampaign = WaNewsLetter::query();
        $queryCampaign = WaCampaignMessageTracking::where('status', $status)
        ->where('campaign_id', $campaignId)
        ->select('contact_id')
        ->groupBy('contact_id', 'campaign_id')
        ->havingRaw("COUNT(*) $frequencyOperator ?", [$frequency['value']]);

        if ($date['operator'] == 'all_time') {
            return $queryCampaign->distinct()->pluck('contact_id')->toArray();
        }

        if ($date['operator'] == 'fixed_period') {
            $queryCampaign->whereBetween('created_at', [$date['startDate'], $date['endDate']]);
        }

        if ($date['operator'] == 'today') {
            $queryCampaign->whereDate('created_at', Carbon::today());
        }

        if ($date['operator'] == 'yesterday') {
            $queryCampaign->whereDate('created_at', Carbon::yesterday());
        }
        
        if ($date['operator'] == 'in_the_last' || $date['operator'] == 'more_than') {
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
        if(in_array($date['operator'], ['exact_date', 'before', 'after'])){
            $queryCampaign->whereDate('created_at', $dateOperator, $date['value']);
        }

        return $queryCampaign->distinct()->pluck('contact_id')->toArray();
    }

    private static function getEmailCampaignContactIds($status, $campaignId, $frequency, $date)
    {
        $frequencyOperator = self::mapFrequencyOperatorToSql($frequency['operator']);
        $dateOperator = self::mapDateOperatorToSql($date['operator']);

        $queryCampaign = CampaignContact::where('status', $status)
            ->where('campaign_id', $campaignId)
            ->select('contact_id')
            ->groupBy('contact_id', 'campaign_id')
            ->havingRaw("COUNT(*) $frequencyOperator ?", [$frequency['value']]);

            if ($date['operator'] == 'all_time') {
                return $queryCampaign->distinct()->pluck('contact_id')->toArray();
            }
            
            if ($date['operator'] == 'fixed_period') {
                $queryCampaign->whereBetween('created_date', [$date['startDate'], $date['endDate']]);
            }

            if ($date['operator'] == 'today') {
                $queryCampaign->whereDate('created_date', Carbon::today());
            }

            if ($date['operator'] == 'yesterday') {
                $queryCampaign->whereDate('created_date', Carbon::yesterday());
            }
            
            if ($date['operator'] == 'in_the_last' || $date['operator'] == 'more_than') {
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
            if(in_array($date['operator'], ['exact_date', 'before', 'after'])){
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

    private static function mapDateOperatorToSql($operator)
    {
        return match ($operator) {
            'equal', 'empty', 'exactly', 'exact_date' => '=',
            'not_equal', 'not_empty' => '!=',
            'contain', 'start_with','end_with' => 'ilike',
            'not_contain', 'not_start_with', 'not_end_with' => 'not ilike',
            'at_least', 'in_the_last' => '>=',
            'at_most' => '<=',
            'less_than', 'after' => '>',
            'more_than', 'before' => '<',
            default => '='
        };
    }

    private static function mapFrequencyOperatorToSql($operator)
    {
        return match ($operator) {
            'equal', 'exactly' => '=',
            'not_equal' => '!=',
            'at_least' => '>=',
            'at_most' => '<=',
            'more_than' => '>',
            'less_than' => '<',
            default => '='
        };
    }

    private static function mapFreetextValueToSql($operator, $freetext)
    {
        if ($operator == 'contain' || $operator == 'not_contain') {
            return "%{$freetext}%";
        }
        if ($operator == 'start_with' || $operator == 'not_start_with') {
            return "{$freetext}%";
        } 
        
        if ($operator == 'end_with' || $operator == 'not_end_with') {
            return "%{$freetext}";
        }

        return $freetext;
    }
}
