# WhatsApp Newsletter Filter Validation Usage Guide

This guide explains how to use the `FilterValidationHelper` to validate filter structures in the WhatsApp Newsletter module.

## Basic Usage

```php
use Modules\WhatsappNewsletter\Helpers\FilterValidationHelper;

class YourController extends Controller
{
    protected $filterValidationHelper;
    
    public function __construct(FilterValidationHelper $filterValidationHelper)
    {
        $this->filterValidationHelper = $filterValidationHelper;
    }
    
    public function validateFilter(Request $request)
    {
        $filterData = $request->input('filters');
        $contactType = $request->input('contactType');
        
        $errors = $this->filterValidationHelper->validate($filterData, $contactType);
        
        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }
        
        // Process valid filter data
        return response()->json(['message' => 'Filter is valid']);
    }
}
```

## Validation Process

The `FilterValidationHelper` validates filters based on these steps:

1. Checks if the overall filter structure is valid
2. Verifies that the contact type exists
3. Validates that filter groups match the configuration
4. Validates each filter by its type:
   - multipleText: Verifies operator and value array
   - multiSelect: Checks if values are from allowed options
   - text: Validates value and unit
   - range: Ensures min/max values are numeric and consistent
   - dateRange: Validates date formats and ranges

## Filter Examples

Here's an example of a valid filter structure for the PHARMACY contact type:

```php
$filterData = [
    'filters' => [
        'pharmacyInfo' => [
            'pharmacyName' => [
                'isInclude' => true,
                'operator' => 'Contains',
                'value' => ['Medic', 'Gesundheit']
            ]
        ],
        'location' => [
            'postcode' => [
                'isInclude' => true,
                'value' => ['10115', '10117']
            ]
        ],
        'purchase' => [
            'orderedInTheLast' => [
                'isInclude' => true,
                'value' => '30',
                'unit' => 'day'
            ],
            'amountOfPurchases' => [
                'isInclude' => true,
                'minAmountPurchases' => 5,
                'maxAmountPurchases' => 20
            ]
        ],
        'creation' => [
            'createdAt' => [
                'isInclude' => true,
                'earliestDate' => '2023-01-01',
                'latestDate' => '2023-12-31'
            ]
        ]
    ]
];
```

## Error Handling

The validation returns an array of errors with keys that correspond to the path in the filter structure:

```php
[
    'filters.pharmacyInfo.pharmacyName.operator' => 'Invalid operator. Valid options: Is equal to, Contains, ...',
    'filters.purchase.amountOfPurchases' => 'Minimum value must be less than or equal to maximum value',
    'filters.creation.createdAt.earliestDate' => 'Invalid date format. Expected format: YYYY-MM-DD'
]
```

## Available Contact Types

The validation supports these contact types:
- PHARMACY
- SUPPLIER
- GENERAL NEWSLETTER
- COMMUNITY
- PHARMACY DATABASE

Each contact type has its own specific filter configuration.
