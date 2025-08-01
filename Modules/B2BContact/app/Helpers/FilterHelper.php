<?php

namespace Modules\B2BContact\Helpers;

use Carbon\Carbon;
use Modules\Campaign\Models\CampaignContact;
use Modules\WhatsappNewsletter\Models\WaCampaignMessageTracking;
use Modules\WhatsappNewsletter\Models\WaNewsLetter;

class FilterHelper
{
    public static function getFilterQuery($baseQuery, $filter)
    {
        if (!$filter) return $baseQuery;

        if ($filter['type'] == 'amount') {
            if ($filter['include']) {
                if(isset($filter['items']['min'], $filter['items']['max'])){
                    return $baseQuery->whereBetween($filter['key'], [$filter['items']['min'], $filter['items']['max']]);
                }

                if (isset($filter['items']['min'])) {
                    return $baseQuery->where($filter['key'], '>', $filter['items']['min']);
                }

                if (isset($filter['items']['max'])) {
                    return $baseQuery->where($filter['key'], '<', $filter['items']['max']);
                }
            }else{
                if(isset($filter['items']['min'], $filter['items']['max'])){
                    return $baseQuery->whereNotBetween($filter['key'], [$filter['items']['min'], $filter['items']['max']]);
                }

                if (isset($filter['items']['min'])) {
                    return $baseQuery->whereNot($filter['key'], '>', $filter['items']['min']);
                }

                if (isset($filter['items']['max'])) {
                    return $baseQuery->whereNot($filter['key'], '<', $filter['items']['max']);
                }
            }
        }

        if ($filter['type'] == 'day') {
            if ($filter['items']['unit'] == 'day') {
                $substractDate = Carbon::now()->subDays($filter['items']['value']);
            }
            if ($filter['items']['unit'] == 'month') {
                $substractDate = Carbon::now()->subMonths($filter['items']['value']);
            }
            if ($filter['items']['unit'] == 'year') {
                $substractDate = Carbon::now()->subYears($filter['items']['value']);
            }

            if ($filter['include']) {
                return $baseQuery->where($filter['key'], '>', $substractDate);
            }else{
                return $baseQuery->whereNot($filter['key'], '>', $substractDate);
            }
        }

        if ($filter['type'] == 'location') {
            if ($filter['include']) {
                return $baseQuery->whereIn($filter['key'], $filter['items']['value']);
            }else{
                return $baseQuery->whereNotIn($filter['key'], $filter['items']['value']);
            }
        }

        if ($filter['type'] == 'creation') {
            if ($filter['include']) {
                if(isset($filter['items']['startDate'], $filter['items']['endDate'])){
                    return $baseQuery->whereBetween($filter['key'], [$filter['items']['startDate'], $filter['items']['endDate']]);
                }

                if (isset($filter['items']['startDate'])) {
                    return $baseQuery->where($filter['key'], '>', $filter['items']['startDate']);
                }

                if (isset($filter['items']['endDate'])) {
                    return $baseQuery->where($filter['key'], '<', $filter['items']['endDate']);
                }
            }else{
                if(isset($filter['items']['startDate'], $filter['items']['endDate'])){
                    return $baseQuery->whereNotBetween($filter['key'], [$filter['items']['startDate'], $filter['items']['endDate']]);
                }

                if (isset($filter['items']['startDate'])) {
                    return $baseQuery->whereNot($filter['key'], '>', $filter['items']['startDate']);
                }

                if (isset($filter['items']['endDate'])) {
                    return $baseQuery->whereNot($filter['key'], '<', $filter['items']['endDate']);
                }
            }
        }

        if ($filter['type'] == 'list') {
            if ($filter['include']) {
                foreach ($filter['items'] as $item) {
                    $baseQuery->where($item, true);
                }
                return $baseQuery;
            }else{
                foreach ($filter['items'] as $item) {
                    $baseQuery->whereNot($item, true);
                }
                return $baseQuery;
            }
        }

        if ($filter['type'] == 'boolean') {
            if ($filter['include']) {
                return $baseQuery->where($filter['key'], $filter['items']['value']);
            }else{
                return $baseQuery->whereNot($filter['key'], $filter['items']['value']);
            }
        }

        if ($filter['type'] == 'array') {
            if ($filter['include']) {
                return $baseQuery->whereIn($filter['key'], $filter['items']['value']);
            }else{
                return $baseQuery->whereNotIn($filter['key'], $filter['items']['value']);
            }
        }

        if ($filter['type'] == 'freetext') {
            $operator = self::mapOperatorToSql($filter['items']['operator']);
            $query = self::mapFreetextValueToSql($filter['items']['operator'], $filter['items']['value']);

            if ($filter['include']) {
                if ($filter['items']['operator'] == 'empty') {
                    $baseQuery->whereNull($filter['key']);
                    return $baseQuery->orWhere($filter['key'], $operator, '');
                }
                if ($filter['items']['operator'] == 'not_empty') {
                    $baseQuery->whereNotNull($filter['key']);
                    return $baseQuery->orWhere($filter['key'], $operator, '');
                }
                
                return $baseQuery->where($filter['key'], $operator, $query);
            }else{
                if ($filter['items']['operator'] == 'empty') {
                    $baseQuery->whereNotNull($filter['key']);
                    return $baseQuery->orWhere($filter['key'], '!=', '');
                }
                if ($filter['items']['operator'] == 'not_empty') {
                    $baseQuery->whereNull($filter['key']);
                    return $baseQuery->orWhere($filter['key'], '=', '');
                }

                return $baseQuery->whereNot($filter['key'], $operator, $query);
            }
        }

        if ($filter['type'] == 'freequency_cap') {
            return $baseQuery->whereIn($filter['key'], $filter['items']['value']);
        }

        if ($filter['type'] == 'campaign') {
            // get contact id by campaign filter to filter data
            if($filter['key'] == 'email_campaign'){
                $contactIds = self::getEmailCampaignContactIds($filter['items']['status'], $filter['items']['campaign_id'], $filter['items']['frequency'], $filter['items']['date']);
            }

            if($filter['key'] == 'whatsapp_campaign'){
                $contactIds = self::getWhatsappCampaignContactIds($filter['items']['status'], $filter['items']['campaign_id'], $filter['items']['frequency'], $filter['items']['date']);
            }

            if ($filter['include']) {
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
            $queryCampaign->whereBetween('sent_at', [$date['startDate'], $date['endDate']]);
        }

        if ($date['operator'] == 'today') {
            $queryCampaign->whereDate('sent_at', Carbon::today());
        }

        if ($date['operator'] == 'yesterday') {
            $queryCampaign->whereDate('sent_at', Carbon::yesterday());
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
            $queryCampaign->whereDate('sent_at', $dateOperator, $substractDate);
        }
        
        //  exact_date, before, after
        if(in_array($date['operator'], ['exact_date', 'before', 'after'])){
            $queryCampaign->whereDate('sent_at', $dateOperator, $date['value']);
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
