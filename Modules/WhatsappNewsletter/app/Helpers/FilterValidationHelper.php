<?php

namespace Modules\WhatsappNewsletter\Helpers;

use Illuminate\Support\Facades\Validator;
use Modules\WhatsappNewsletter\Services\FilterConfigService;

class FilterValidationHelper
{
    /**
     * @var FilterConfigService
     */
    protected $filterConfigService;

    /**
     * FilterValidationHelper constructor.
     * 
     * @param FilterConfigService $filterConfigService
     */
    public function __construct(FilterConfigService $filterConfigService)
    {
        $this->filterConfigService = $filterConfigService;
    }

    /**
     * Validate filter JSON against configuration for a specific contact type
     *
     * @param array $filterData
     * @param string $contactType
     * @return array
     */
    public function validate(array $filterData, string $contactType): array
    {
        $errors = [];

        // Check if filters exist
        if (!isset($filterData['filters']) || !is_array($filterData['filters'])) {
            return ['filters' => 'Filters must be provided as an array'];
        }

        $filterConfig = $this->filterConfigService->getFilterConfig($contactType);
        if (empty($filterConfig)) {
            return ['contact_type' => 'Invalid contact type provided'];
        }

        // Create a flattened map of filter configurations for easier access
        $configMap = $this->createFilterConfigMap($filterConfig);

        // Validate each filter group in the provided data
        foreach ($filterData['filters'] as $groupKey => $groupFilters) {
            if (!array_key_exists($groupKey, $configMap)) {
                $errors["filters.{$groupKey}"] = "Unknown filter group: {$groupKey}";
                continue;
            }

            // Validate each filter in the group
            foreach ($groupFilters as $filterKey => $filterValue) {
                $filterPath = "filters.{$groupKey}.{$filterKey}";

                if (!array_key_exists($filterKey, $configMap[$groupKey])) {
                    $errors[$filterPath] = "Unknown filter: {$filterKey} in group {$groupKey}";
                    continue;
                }

                $filterConfig = $configMap[$groupKey][$filterKey];
                $filterType = $filterConfig['type'];
                $filterOptions = $filterConfig['options'];

                // Validate based on filter type
                $validationErrors = $this->validateFilterByType($filterValue, $filterType, $filterOptions, $filterPath);
                if (!empty($validationErrors)) {
                    $errors = array_merge($errors, $validationErrors);
                }
            }
        }

        return $errors;
    }

    /**
     * Create a map of filter configurations for easier access
     *
     * @param array $filterConfig
     * @return array
     */
    protected function createFilterConfigMap(array $filterConfig): array
    {
        $configMap = [];

        foreach ($filterConfig as $group) {
            $groupName = $this->normalizeGroupName($group['name']);
            $configMap[$groupName] = [];

            foreach ($group['filters'] as $filter) {
                $configMap[$groupName][$filter['name']] = $filter;
            }
        }

        return $configMap;
    }

    /**
     * Normalize group name to match JSON format
     * 
     * @param string $groupName
     * @return string
     */
    protected function normalizeGroupName(string $groupName): string
    {
        // Convert "Group Name" to "groupName" (camelCase)
        $words = explode(' ', strtolower($groupName));
        $camelCase = array_shift($words);

        foreach ($words as $word) {
            $camelCase .= ucfirst($word);
        }

        return $camelCase;
    }

    /**
     * Validate filter value based on its type
     *
     * @param array $filterValue
     * @param string $filterType
     * @param array|mixed $filterOptions
     * @param string $filterPath
     * @return array
     */
    protected function validateFilterByType(array $filterValue, string $filterType, $filterOptions, string $filterPath): array
    {
        $errors = [];

        // Check if isInclude is present and is boolean
        if (!isset($filterValue['isInclude']) || !is_bool($filterValue['isInclude'])) {
            $errors["{$filterPath}.isInclude"] = "isInclude must be a boolean value";
        }

        // Validate based on filter type
        switch ($filterType) {
            case 'multipleText':
                $errors = array_merge($errors, $this->validateMultipleTextField($filterValue, $filterOptions, $filterPath));
                break;

            case 'multiSelect':
                $errors = array_merge($errors, $this->validateMultiSelectField($filterValue, $filterOptions, $filterPath));
                break;

            case 'text':
                $errors = array_merge($errors, $this->validateTextField($filterValue, $filterOptions, $filterPath));
                break;

            case 'range':
                $errors = array_merge($errors, $this->validateRangeField($filterValue, $filterOptions, $filterPath));
                break;

            case 'dateRange':
                $errors = array_merge($errors, $this->validateDateRangeField($filterValue, $filterOptions, $filterPath));
                break;

            default:
                $errors["{$filterPath}"] = "Unknown filter type: {$filterType}";
        }

        return $errors;
    }

    /**
     * Validate multipleText filter type
     *
     * @param array $filterValue
     * @param array $validOperators
     * @param string $filterPath
     * @return array
     */
    protected function validateMultipleTextField(array $filterValue, array $validOperators, string $filterPath): array
    {
        $errors = [];

        // Validate operator
        if (!isset($filterValue['operator'])) {
            $errors["{$filterPath}.operator"] = "Operator is required for text filter";
        } elseif (!in_array($filterValue['operator'], $validOperators)) {
            $errors["{$filterPath}.operator"] = "Invalid operator. Valid options: " . implode(', ', $validOperators);
        }

        // For operators that need values
        $operatorsRequiringValue = [
            'Is equal to',
            'Is not equal to',
            'Contains',
            'Does not contains',
            'Starts with',
            'Does not starts with',
            'Ends with',
            'Does not end with'
        ];

        if (isset($filterValue['operator']) && in_array($filterValue['operator'], $operatorsRequiringValue)) {
            // Validate value
            if (!isset($filterValue['value']) || !is_array($filterValue['value'])) {
                $errors["{$filterPath}.value"] = "Value must be an array for text filter";
            } elseif (empty($filterValue['value'])) {
                $errors["{$filterPath}.value"] = "Value cannot be empty for text filter";
            }
        }

        return $errors;
    }

    /**
     * Validate multiSelect filter type
     *
     * @param array $filterValue
     * @param array $validOptions
     * @param string $filterPath
     * @return array
     */
    protected function validateMultiSelectField(array $filterValue, array $validOptions, string $filterPath): array
    {
        $errors = [];

        // Validate value
        if (!isset($filterValue['value']) || !is_array($filterValue['value'])) {
            $errors["{$filterPath}.value"] = "Value must be an array for multiSelect filter";
        } elseif (empty($filterValue['value'])) {
            $errors["{$filterPath}.value"] = "Value cannot be empty for multiSelect filter";
        } elseif (!empty($validOptions)) {
            // Validate against valid options if provided
            $invalidOptions = array_diff($filterValue['value'], $validOptions);
            if (!empty($invalidOptions)) {
                $errors["{$filterPath}.value"] = "Invalid options: " . implode(', ', $invalidOptions) .
                    ". Valid options: " . implode(', ', $validOptions);
            }
        }

        return $errors;
    }

    /**
     * Validate text filter type
     *
     * @param array $filterValue
     * @param array $validUnits
     * @param string $filterPath
     * @return array
     */
    protected function validateTextField(array $filterValue, array $validUnits, string $filterPath): array
    {
        $errors = [];

        // Validate value
        if (!isset($filterValue['value'])) {
            $errors["{$filterPath}.value"] = "Value is required for text filter";
        } elseif (!is_numeric($filterValue['value'])) {
            $errors["{$filterPath}.value"] = "Value must be numeric for text filter";
        } elseif ((int)$filterValue['value'] <= 0) {
            $errors["{$filterPath}.value"] = "Value must be greater than 0";
        }

        // Validate unit
        if (!isset($filterValue['unit'])) {
            $errors["{$filterPath}.unit"] = "Unit is required for text filter";
        } elseif (!in_array($filterValue['unit'], $validUnits)) {
            $errors["{$filterPath}.unit"] = "Invalid unit. Valid options: " . implode(', ', $validUnits);
        }

        return $errors;
    }

    /**
     * Validate range filter type
     *
     * @param array $filterValue
     * @param array $options
     * @param string $filterPath
     * @return array
     */
    protected function validateRangeField(array $filterValue, array $options, string $filterPath): array
    {
        $errors = [];
        $minKey = $options['min'];
        $maxKey = $options['max'];

        // Validate min value
        if (isset($filterValue[$minKey]) && !is_numeric($filterValue[$minKey])) {
            $errors["{$filterPath}.{$minKey}"] = "Minimum value must be numeric";
        }

        // Validate max value
        if (isset($filterValue[$maxKey]) && !is_numeric($filterValue[$maxKey])) {
            $errors["{$filterPath}.{$maxKey}"] = "Maximum value must be numeric";
        }

        // Validate min <= max if both are provided
        if (isset($filterValue[$minKey]) && isset($filterValue[$maxKey])) {
            $min = (float)$filterValue[$minKey];
            $max = (float)$filterValue[$maxKey];

            if ($min > $max) {
                $errors["{$filterPath}"] = "Minimum value must be less than or equal to maximum value";
            }
        }

        return $errors;
    }

    /**
     * Validate dateRange filter type
     *
     * @param array $filterValue
     * @param array $options
     * @param string $filterPath
     * @return array
     */
    protected function validateDateRangeField(array $filterValue, array $options, string $filterPath): array
    {
        $errors = [];
        $startKey = $options['start'];
        $endKey = $options['end'];

        // Validate start date
        if (isset($filterValue[$startKey])) {
            $startErrors = $this->validateDateFormat($filterValue[$startKey], "{$filterPath}.{$startKey}");
            if (!empty($startErrors)) {
                $errors = array_merge($errors, $startErrors);
            }
        }

        // Validate end date
        if (isset($filterValue[$endKey])) {
            $endErrors = $this->validateDateFormat($filterValue[$endKey], "{$filterPath}.{$endKey}");
            if (!empty($endErrors)) {
                $errors = array_merge($errors, $endErrors);
            }
        }

        // Validate start <= end if both are provided
        if (isset($filterValue[$startKey]) && isset($filterValue[$endKey])) {
            try {
                $startDate = new \DateTime($filterValue[$startKey]);
                $endDate = new \DateTime($filterValue[$endKey]);

                if ($startDate > $endDate) {
                    $errors["{$filterPath}"] = "Start date must be less than or equal to end date";
                }
            } catch (\Exception $e) {
                // Date format errors are already caught in validateDateFormat
            }
        }

        return $errors;
    }

    /**
     * Validate date format (YYYY-MM-DD)
     *
     * @param string $date
     * @param string $fieldPath
     * @return array
     */
    protected function validateDateFormat(string $date, string $fieldPath): array
    {
        $errors = [];

        // Check format using validator
        $validator = Validator::make(['date' => $date], [
            'date' => 'date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            $errors[$fieldPath] = "Invalid date format. Expected format: YYYY-MM-DD";
        }

        return $errors;
    }
}
