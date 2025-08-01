<?php

namespace Modules\WhatsappNewsletter\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\NewContactData\Models\ContactTypes;

class FilterQueryHelper
{
    public static function getFieldMapping(): array
    {
        return [
            'pharmacyInfo.pharmacyName' => 'contact_name',
            'pharmacyInfo.pharmacyNumber' => 'contact_no',
            'location.address' => 'address',
            'location.postcode' => 'post_code',
            'location.city' => 'city',
            'location.country' => 'country',
            'contact.contactPerson' => 'contact_person',
            'contact.email' => 'email',
            'contact.phoneNumber' => 'phone_no',
            'purchase.amountOfPurchases' => 'amount_purchase',
            'purchase.totalPurchaseValue' => 'total_purchase',
            'purchase.averagePurchaseValue' => 'average_purchase',
            'purchase.orderedInTheLast' => 'last_purchase_date',
            'creation.createdAt' => 'created_date',
            'creation.accountCreation' => 'created_date',
            'userActivity.latestLogin' => 'updated_date',
            'supplierInfo.companyName' => 'contact_name',
            'supplierInfo.vatId' => 'vat_id',
            'contactInfo.fullName' => 'contact_name',
            'contactInfo.email' => 'email',
            'contactInfo.phoneNumber' => 'phone_no',
            'channel.subscribedTo' => [
                'Whatsapp Subcribers' => 'whatsapp_subscription',
                'Email Subscribers' => 'canasativa_newsletter'
            ],
            'interactions.amountOfLikes' => null,
            'interactions.amountOfComments' => null,
            'interactions.amountOfSubmissions' => null,
            'community_user' => 'community_user'
        ];
    }

    public static function getContactTypeMapping(): array
    {
        return Cache::remember('contact_types_mapping', 60 * 60 * 24, function () {
            $contactTypes = ContactTypes::all();
            $mapping = [];

            foreach ($contactTypes as $type) {
                $typeName = str_replace(' ', '_', $type->contact_type_name);
                $mapping[$type->id] = $typeName;
            }

            return $mapping;
        });
    }

    public static function getContactTypeName(int $contactTypeId): ?string
    {
        $typeMapping = self::getContactTypeMapping();
        return $typeMapping[$contactTypeId] ?? null;
    }

    public static function isB2BContactType(int $contactTypeId): bool
    {
        $contactType = ContactTypes::find($contactTypeId);
        if (!$contactType) {
            return false;
        }

        $b2bTypes = ['Pharmacy', 'Supplier', 'General Newsletter'];
        return in_array($contactType->contact_type_name, $b2bTypes);
    }

    public static function applyFilters(Builder $query, array $filters, int $contactTypeId, ?string $modelType = null): Builder
    {
        $contactTypeMapping = self::getContactTypeMapping();
        $contactType = $contactTypeMapping[$contactTypeId] ?? null;

        if (!$contactType || empty($filters)) {
            return $query;
        }

        if ($modelType === null) {
            $modelType = self::isB2BContactType($contactTypeId) ? 'B2B' : 'B2C';
        }

        $fieldMapping = self::getFieldMapping();

        foreach ($filters as $filterGroup => $filterGroupData) {
            foreach ($filterGroupData as $filterKey => $filterData) {
                $fullFilterKey = "{$filterGroup}.{$filterKey}";

                if (!isset($fieldMapping[$fullFilterKey]) || $fieldMapping[$fullFilterKey] === null) {
                    continue;
                }

                $dbField = $fieldMapping[$fullFilterKey];
                $query = self::applyFilter($query, $dbField, $filterData);
            }
        }

        return $query;
    }

    private static function applyFilter(Builder $query, $dbField, array $filterData): Builder
    {
        $isInclude = isset($filterData['isInclude']) && $filterData['isInclude'] === true;

        if (is_array($dbField) && isset($filterData['value']) && is_array($filterData['value'])) {
            foreach ($filterData['value'] as $subscriptionType) {
                if (isset($dbField[$subscriptionType])) {
                    if ($isInclude) {
                        $query = $query->where($dbField[$subscriptionType], 1);
                    } else {
                        $query = $query->where(function ($q) use ($dbField, $subscriptionType) {
                            $q->where($dbField[$subscriptionType], 0)
                                ->orWhereNull($dbField[$subscriptionType]);
                        });
                    }
                }
            }
            return $query;
        }

        if (isset($filterData['operator']) && isset($filterData['value'])) {
            return self::applyTextFilter($query, $dbField, $filterData);
        }

        if (
            isset($filterData['minAmountPurchases']) || isset($filterData['maxAmountPurchases']) ||
            isset($filterData['minTotalPurchaseValue']) || isset($filterData['maxTotalPurchaseValue']) ||
            isset($filterData['minAveragePurchaseValue']) || isset($filterData['maxAveragePurchaseValue'])
        ) {
            return self::applyRangeFilter($query, $dbField, $filterData);
        }

        if (isset($filterData['earliestDate']) || isset($filterData['latestDate'])) {
            return self::applyDateRangeFilter($query, $dbField, $filterData);
        }

        if (isset($filterData['value']) && isset($filterData['unit']) && $dbField === 'last_purchase_date') {
            return self::applyTimeBasedFilter($query, $dbField, $filterData);
        }

        if (isset($filterData['value']) && is_array($filterData['value'])) {
            $isInclude = isset($filterData['isInclude']) && $filterData['isInclude'] === true;

            if ($isInclude) {
                return $query->whereIn($dbField, $filterData['value']);
            } else {
                return $query->whereNotIn($dbField, $filterData['value']);
            }
        }

        return $query;
    }

    private static function applyTextFilter(Builder $query, string $dbField, array $filterData): Builder
    {
        $operator = self::mapOperatorToSql($filterData['operator']);
        $values = (array)$filterData['value'];
        $connection = DB::connection()->getDriverName();
        $isInclude = isset($filterData['isInclude']) && $filterData['isInclude'] === true;

        if (count($values) > 1) {
            if ($isInclude) {
                $query->where(function ($subQuery) use ($values, $operator, $filterData, $connection, $dbField) {
                    foreach ($values as $index => $value) {
                        if ($operator === 'like') {
                            if ($filterData['operator'] === 'Contains') {
                                $value = "%{$value}%";
                            } elseif ($filterData['operator'] === 'Starts with') {
                                $value = "{$value}%";
                            } elseif ($filterData['operator'] === 'Ends with') {
                                $value = "%{$value}";
                            }

                            if ($connection === 'pgsql') {
                                $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                                $subQuery->$method($dbField . " ILIKE ?", [$value]);
                            } else {
                                $method = $index === 0 ? 'where' : 'orWhere';
                                $subQuery->$method($dbField, 'LIKE', $value);
                            }
                        } else {
                            $method = $index === 0 ? 'where' : 'orWhere';
                            $subQuery->$method($dbField, $operator, $value);
                        }
                    }
                });
            } else {
                $query->where(function ($subQuery) use ($values, $operator, $filterData, $connection, $dbField) {
                    foreach ($values as $value) {
                        if ($operator === 'like') {
                            if ($filterData['operator'] === 'Contains') {
                                $value = "%{$value}%";
                            } elseif ($filterData['operator'] === 'Starts with') {
                                $value = "{$value}%";
                            } elseif ($filterData['operator'] === 'Ends with') {
                                $value = "%{$value}";
                            }

                            if ($connection === 'pgsql') {
                                $subQuery->whereRaw("NOT (" . $dbField . " ILIKE ?)", [$value]);
                            } else {
                                $subQuery->where($dbField, 'NOT LIKE', $value);
                            }
                        } else {
                            if ($operator === '=') {
                                $subQuery->where(function ($q) use ($dbField, $value) {
                                    $q->where($dbField, '!=', $value)
                                        ->orWhereNull($dbField);
                                });
                            } elseif ($operator === '!=') {
                                $subQuery->where(function ($q) use ($dbField, $value) {
                                    $q->where($dbField, '=', $value)
                                        ->orWhereNull($dbField);
                                });
                            } else {
                                $subQuery->where(function ($q) use ($dbField, $operator, $value) {
                                    $q->where($dbField, $operator === '>' ? '<=' : ($operator === '<' ? '>=' : ($operator === '>=' ? '<' : ($operator === '<=' ? '>' : '!='))), $value)
                                        ->orWhereNull($dbField);
                                });
                            }
                        }
                    }
                });
            }
        } else {
            $value = reset($values);

            if ($operator === 'like') {
                if ($filterData['operator'] === 'Contains') {
                    $value = "%{$value}%";
                } elseif ($filterData['operator'] === 'Starts with') {
                    $value = "{$value}%";
                } elseif ($filterData['operator'] === 'Ends with') {
                    $value = "%{$value}";
                }

                if ($connection === 'pgsql') {
                    if ($isInclude) {
                        $query = $query->whereRaw($dbField . " ILIKE ?", [$value]);
                    } else {
                        $query = $query->whereRaw("NOT (" . $dbField . " ILIKE ?)", [$value]);
                    }
                } else {
                    if ($isInclude) {
                        $query = $query->where($dbField, 'LIKE', $value);
                    } else {
                        $query = $query->where($dbField, 'NOT LIKE', $value);
                    }
                }
            } else {
                if ($isInclude) {
                    $query = $query->where($dbField, $operator, $value);
                } else {
                    $query = $query->where(function ($q) use ($dbField, $operator, $value) {
                        if ($operator === '=') {
                            $q->where($dbField, '!=', $value)
                                ->orWhereNull($dbField);
                        } elseif ($operator === '!=') {
                            $q->where($dbField, '=', $value)
                                ->orWhereNull($dbField);
                        } else {
                            $q->where($dbField, $operator === '>' ? '<=' : ($operator === '<' ? '>=' : ($operator === '>=' ? '<' : ($operator === '<=' ? '>' : '!='))), $value)
                                ->orWhereNull($dbField);
                        }
                    });
                }
            }
        }

        return $query;
    }

    private static function applyRangeFilter(Builder $query, string $dbField, array $filterData): Builder
    {
        $isInclude = isset($filterData['isInclude']) && $filterData['isInclude'] === true;

        if (isset($filterData['minAmountPurchases']) || isset($filterData['maxAmountPurchases'])) {
            $minValue = $filterData['minAmountPurchases'] ?? null;
            $maxValue = $filterData['maxAmountPurchases'] ?? null;

            if ($isInclude) {
                if ($minValue !== null) {
                    $query = $query->where('amount_purchase', '>=', $minValue);
                }
                if ($maxValue !== null) {
                    $query = $query->where('amount_purchase', '<=', $maxValue);
                }
            } else {
                $query = $query->where(function ($q) use ($minValue, $maxValue) {
                    if ($minValue !== null && $maxValue !== null) {
                        $q->where('amount_purchase', '<', $minValue)
                            ->orWhere('amount_purchase', '>', $maxValue)
                            ->orWhereNull('amount_purchase');
                    } elseif ($minValue !== null) {
                        $q->where('amount_purchase', '<', $minValue)
                            ->orWhereNull('amount_purchase');
                    } elseif ($maxValue !== null) {
                        $q->where('amount_purchase', '>', $maxValue)
                            ->orWhereNull('amount_purchase');
                    }
                });
            }
        }

        if (isset($filterData['minTotalPurchaseValue']) || isset($filterData['maxTotalPurchaseValue'])) {
            $minValue = $filterData['minTotalPurchaseValue'] ?? null;
            $maxValue = $filterData['maxTotalPurchaseValue'] ?? null;

            if ($isInclude) {
                if ($minValue !== null) {
                    $query = $query->where('total_purchase', '>=', $minValue);
                }
                if ($maxValue !== null) {
                    $query = $query->where('total_purchase', '<=', $maxValue);
                }
            } else {
                $query = $query->where(function ($q) use ($minValue, $maxValue) {
                    if ($minValue !== null && $maxValue !== null) {
                        $q->where('total_purchase', '<', $minValue)
                            ->orWhere('total_purchase', '>', $maxValue)
                            ->orWhereNull('total_purchase');
                    } elseif ($minValue !== null) {
                        $q->where('total_purchase', '<', $minValue)
                            ->orWhereNull('total_purchase');
                    } elseif ($maxValue !== null) {
                        $q->where('total_purchase', '>', $maxValue)
                            ->orWhereNull('total_purchase');
                    }
                });
            }
        }

        if (isset($filterData['minAveragePurchaseValue']) || isset($filterData['maxAveragePurchaseValue'])) {
            $minValue = $filterData['minAveragePurchaseValue'] ?? null;
            $maxValue = $filterData['maxAveragePurchaseValue'] ?? null;

            if ($isInclude) {
                if ($minValue !== null) {
                    $query = $query->where('average_purchase', '>=', $minValue);
                }
                if ($maxValue !== null) {
                    $query = $query->where('average_purchase', '<=', $maxValue);
                }
            } else {
                $query = $query->where(function ($q) use ($minValue, $maxValue) {
                    if ($minValue !== null && $maxValue !== null) {
                        $q->where('average_purchase', '<', $minValue)
                            ->orWhere('average_purchase', '>', $maxValue)
                            ->orWhereNull('average_purchase');
                    } elseif ($minValue !== null) {
                        $q->where('average_purchase', '<', $minValue)
                            ->orWhereNull('average_purchase');
                    } elseif ($maxValue !== null) {
                        $q->where('average_purchase', '>', $maxValue)
                            ->orWhereNull('average_purchase');
                    }
                });
            }
        }

        return $query;
    }

    private static function applyDateRangeFilter(Builder $query, string $dbField, array $filterData): Builder
    {
        $isInclude = isset($filterData['isInclude']) && $filterData['isInclude'] === true;

        if (isset($filterData['earliestDate'])) {
            $startDate = $filterData['earliestDate'];
            if ($isInclude) {
                $query = $query->where($dbField, '>=', $startDate);
            } else {
                $query = $query->where(function ($q) use ($dbField, $startDate) {
                    $q->where($dbField, '<', $startDate)
                        ->orWhereNull($dbField);
                });
            }
        }

        if (isset($filterData['latestDate'])) {
            $endDate = $filterData['latestDate'];
            if ($isInclude) {
                $query = $query->where($dbField, '<=', $endDate);
            } else {
                $query = $query->where(function ($q) use ($dbField, $endDate) {
                    $q->where($dbField, '>', $endDate)
                        ->orWhereNull($dbField);
                });
            }
        }

        return $query;
    }

    private static function applyTimeBasedFilter(Builder $query, string $dbField, array $filterData): Builder
    {
        $value = (int)$filterData['value'];
        $unit = $filterData['unit'];
        $isInclude = isset($filterData['isInclude']) && $filterData['isInclude'] === true;

        if ($value <= 0) {
            return $query;
        }

        $date = Carbon::now();

        if ($unit === 'day') {
            $date->subDays($value);
        } elseif ($unit === 'month') {
            $date->subMonths($value);
        } elseif ($unit === 'year') {
            $date->subYears($value);
        }

        $dateString = $date->toDateTimeString();

        if ($isInclude) {
            return $query->where($dbField, '>=', $dateString);
        } else {
            return $query->where(function ($q) use ($dbField, $dateString) {
                $q->where($dbField, '<', $dateString)
                    ->orWhereNull($dbField);
            });
        }
    }

    private static function mapOperatorToSql(string $operator): string
    {
        return match ($operator) {
            'Is equal to' => '=',
            'Is not equal to' => '!=',
            'Contains', 'Starts with', 'Ends with' => 'like',
            'Is greater than' => '>',
            'Is greater than or equal to' => '>=',
            'Is less than' => '<',
            'Is less than or equal to' => '<=',
            default => '='
        };
    }

    public static function applyContactTypeFilters(Builder $query, array $filters, string $contactType): Builder
    {
        $contactTypeMap = array_flip(self::getContactTypeMapping());
        $contactTypeId = $contactTypeMap[$contactType] ?? null;

        if ($contactTypeId === null) {
            return $query;
        }

        $modelType = self::isB2BContactType($contactTypeId) ? 'B2B' : 'B2C';

        $query = $query->where('contact_type_id', $contactTypeId);
        return self::applyFilters($query, $filters, $contactTypeId, $modelType);
    }
}
