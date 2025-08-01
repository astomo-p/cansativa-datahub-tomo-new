<?php

namespace Modules\WhatsappNewsletter\App\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Modules\NewContactData\Models\Contacts;
use Modules\WhatsappNewsletter\App\Models\FilterPreset;
use Carbon\Carbon;
use Modules\NewContactData\Models\ContactTypes;

class FilterHelper
{
    public static function parseFiltersToQuery(Builder $query, array $filters): Builder
    {
        foreach ($filters as $filter) {
            $mode = $filter['mode'] ?? 'include';
            $field = $filter['field'] ?? '';
            $operator = $filter['operator'] ?? 'equals';
            $values = $filter['values'] ?? [];

            if (empty($field) || empty($values)) {
                continue;
            }

            switch ($operator) {
                case 'contains':
                    self::applyContainsFilter($query, $field, $values, $mode);
                    break;

                case 'equals':
                    self::applyEqualsFilter($query, $field, $values, $mode);
                    break;

                case 'between':
                    self::applyBetweenFilter($query, $field, $values, $mode);
                    break;

                case 'days':
                    self::applyDaysFilter($query, $field, $values, $mode);
                    break;

                case 'before':
                    self::applyBeforeFilter($query, $field, $values, $mode);
                    break;

                case 'after':
                    self::applyAfterFilter($query, $field, $values, $mode);
                    break;
            }
        }

        return $query;
    }

    private static function applyContainsFilter(Builder $query, string $field, array $values, string $mode): void
    {
        if ($mode === 'include') {
            $query->where(function ($q) use ($field, $values) {
                foreach ($values as $value) {
                    $q->orWhere($field, 'like', '%' . $value . '%');
                }
            });
        } else {
            $query->where(function ($q) use ($field, $values) {
                foreach ($values as $value) {
                    $q->where($field, 'not like', '%' . $value . '%');
                }
            });
        }
    }

    private static function applyEqualsFilter(Builder $query, string $field, array $values, string $mode): void
    {
        if ($mode === 'include') {
            if (count($values) === 1) {
                $query->where($field, $values[0]);
            } else {
                $query->whereIn($field, $values);
            }
        } else {
            if (count($values) === 1) {
                $query->where($field, '!=', $values[0]);
            } else {
                $query->whereNotIn($field, $values);
            }
        }
    }

    private static function applyBetweenFilter(Builder $query, string $field, array $values, string $mode): void
    {
        if (count($values) >= 2) {
            if ($mode === 'include') {
                $query->whereBetween($field, [$values[0], $values[1]]);
            } else {
                $query->whereNotBetween($field, [$values[0], $values[1]]);
            }
        }
    }

    private static function applyDaysFilter(Builder $query, string $field, array $values, string $mode): void
    {
        if (!empty($values[0])) {
            $date = Carbon::now()->subDays((int)$values[0]);

            if ($mode === 'include') {
                $query->where($field, '>=', $date);
            } else {
                $query->where($field, '<', $date);
            }
        }
    }

    private static function applyBeforeFilter(Builder $query, string $field, array $values, string $mode): void
    {
        if (!empty($values[0])) {
            if ($mode === 'include') {
                $query->where($field, '<', $values[0]);
            } else {
                $query->where($field, '>=', $values[0]);
            }
        }
    }

    private static function applyAfterFilter(Builder $query, string $field, array $values, string $mode): void
    {
        if (!empty($values[0])) {
            if ($mode === 'include') {
                $query->where($field, '>', $values[0]);
            } else {
                $query->where($field, '<=', $values[0]);
            }
        }
    }

    /**
     * Save filter preset to database
     * 
     * @param string $name
     * @param int $contactTypeId
     * @param array $filters
     * @return FilterPreset
     */
    public static function saveFilterPreset(string $name, int $contactTypeId, array $filters): FilterPreset
    {
        return FilterPreset::create([
            'name' => $name,
            'contact_type_id' => $contactTypeId,
            'filters' => $filters
        ]);
    }

    /**
     * Apply contact filter preset by ID
     * 
     * @param int $presetId
     * @return Builder
     * @throws \Exception
     */
    public static function applyContactFilterPreset(int $presetId): Builder
    {
        $preset = FilterPreset::find($presetId);

        if (!$preset) {
            throw new \Exception("Filter preset with ID {$presetId} not found");
        }

        // Start query with contact_type_id from the preset
        $query = Contacts::where('contact_type_id', $preset->contact_type_id);

        return self::parseFiltersToQuery($query, $preset->filters);
    }

    /**
     * Apply contact filter preset by contact type
     * 
     * @param int $contactTypeId
     * @param int $presetId
     * @return Builder
     * @throws \Exception
     */
    public static function applyContactFilterPresetByType(int $contactTypeId, int $presetId): Builder
    {
        $preset = FilterPreset::where('id', $presetId)
            ->where('contact_type_id', $contactTypeId)
            ->first();

        if (!$preset) {
            throw new \Exception("Filter preset with ID {$presetId} not found for contact type {$contactTypeId}");
        }

        $query = Contacts::where('contact_type_id', $contactTypeId);

        return self::parseFiltersToQuery($query, $preset->filters);
    }

    /**
     * Get contacts for a specific contact type with optional filters
     * 
     * @param int $contactTypeId
     * @param array|null $filters
     * @return Builder
     */
    public static function getContactsByType(int $contactTypeId, ?array $filters = null): Builder
    {
        $query = Contacts::where('contact_type_id', $contactTypeId);

        if ($filters && !empty($filters)) {
            return self::parseFiltersToQuery($query, $filters);
        }

        return $query;
    }

    /**
     * Get all available contact types with their preset counts
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getContactTypesWithPresetCounts()
    {
        return ContactTypes::withCount('filterPresets')->get();
    }
}
