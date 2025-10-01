<?php

namespace Modules\B2BContact\Services;

use Modules\B2BContact\Models\B2BContacts;
use Modules\NewContactData\Models\Contacts;

class FilterService
{
    public function getFilterData($contact_type_id, $search, $type)
    {
        if ($type == 'b2b') {
            $contact = new B2BContacts;
        }else{
            $contact = new Contacts;
        }
        $filters[$search] = $contact::where('contact_type_id', $contact_type_id)->distinct()->whereNotNull($search)->pluck($search);
        return $filters;
    }

    public function mapFilter($filter_data)
    {
        $formatted_keys = $this->getKeyTitles($filter_data);
        return $formatted_keys;
    }

    public function getKeyTitles($data)
    {
        $keyTitles = [];
        $filter_keys = ['post_code', 'city', 'address', 'vat_id', 'country', 'email', 'phone_no'];
        
        if (isset($data->include)) {
            foreach ($data->include as $key => $value) {
                if (in_array($key, $filter_keys)) {
                    $newKey = ucfirst(str_replace('_', ' ', $key));
                }else{
                    $newKey = $this->mapKeyToTitle($key);
                }
                $keyTitles['include'][$key]['key'] = $key;
                $keyTitles['include'][$key]['title'] = $newKey;
                $keyTitles['include'][$key]['value'] = $value;
            }
        }

        if (isset($data->exclude)) {
            foreach ($data->exclude as $key => $value) {
                if (in_array($key, $filter_keys)) {
                    $newKey = ucfirst(str_replace('_', ' ', $key));
                }else{
                    $newKey = $this->mapKeyToTitle($key);
                }
                $keyTitles['exclude'][$key]['key'] = $key;
                $keyTitles['exclude'][$key]['title'] = $newKey;
                $keyTitles['exclude'][$key]['value'] = $value;
            }
        }
        
        return $keyTitles;
    }

    public function mapKeyToTitle($key)
    {
        switch ($key) {
            case 'last_purchase_date':
                $title = 'Ordered in the last';
                break;
            case 'average_purchase':
                $title = 'Average Purchase Value';
                break;
            case 'total_purchase':
                $title = 'Total Purchase Value';
                break;
            case 'amount_purchase':
                $title = 'Amount Purchase Value';
                break;
            case 'created_date':
                $title = 'Created At';
                break;            
            default:
                $title = '';
                break;
        }
        return $title;
    }
}
