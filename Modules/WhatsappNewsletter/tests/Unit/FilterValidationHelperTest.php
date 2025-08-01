<?php

namespace Modules\WhatsappNewsletter\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\WhatsappNewsletter\Helpers\FilterValidationHelper;
use Modules\WhatsappNewsletter\Services\FilterConfigService;

class FilterValidationHelperTest extends TestCase
{
    use WithFaker;

    /**
     * @var FilterValidationHelper
     */
    protected $filterValidationHelper;

    /**
     * @var FilterConfigService
     */
    protected $filterConfigService;

    public function setUp(): void
    {
        parent::setUp();

        $this->filterConfigService = new FilterConfigService();
        $this->filterValidationHelper = new FilterValidationHelper($this->filterConfigService);
    }

    /**
     * Test that validation passes for a valid filter structure
     */
    public function testValidationPassesForValidFilterStructure()
    {
        // Sample valid filter data for PHARMACY
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
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'PHARMACY');
        $this->assertEmpty($errors, 'Validation should pass for valid filter structure');
    }

    /**
     * Test validation for missing filters
     */
    public function testValidationFailsForMissingFilters()
    {
        $filterData = [];

        $errors = $this->filterValidationHelper->validate($filterData, 'PHARMACY');
        $this->assertArrayHasKey('filters', $errors);
    }

    /**
     * Test validation for invalid contact type
     */
    public function testValidationFailsForInvalidContactType()
    {
        $filterData = [
            'filters' => [
                'pharmacyInfo' => [
                    'pharmacyName' => [
                        'isInclude' => true,
                        'operator' => 'Contains',
                        'value' => ['Medic']
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'INVALID_TYPE');
        $this->assertArrayHasKey('contact_type', $errors);
    }

    /**
     * Test validation for unknown filter group
     */
    public function testValidationFailsForUnknownFilterGroup()
    {
        $filterData = [
            'filters' => [
                'unknownGroup' => [
                    'pharmacyName' => [
                        'isInclude' => true,
                        'operator' => 'Contains',
                        'value' => ['Medic']
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'PHARMACY');
        $this->assertArrayHasKey('filters.unknownGroup', $errors);
    }

    /**
     * Test validation for unknown filter
     */
    public function testValidationFailsForUnknownFilter()
    {
        $filterData = [
            'filters' => [
                'pharmacyInfo' => [
                    'unknownFilter' => [
                        'isInclude' => true,
                        'operator' => 'Contains',
                        'value' => ['Medic']
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'PHARMACY');
        $this->assertArrayHasKey('filters.pharmacyInfo.unknownFilter', $errors);
    }

    /**
     * Test validation for multiple text field
     */
    public function testValidationForMultipleTextField()
    {
        // Invalid operator
        $filterData = [
            'filters' => [
                'pharmacyInfo' => [
                    'pharmacyName' => [
                        'isInclude' => true,
                        'operator' => 'InvalidOperator',
                        'value' => ['Medic']
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'PHARMACY');
        $this->assertArrayHasKey('filters.pharmacyInfo.pharmacyName.operator', $errors);

        // Missing value
        $filterData = [
            'filters' => [
                'pharmacyInfo' => [
                    'pharmacyName' => [
                        'isInclude' => true,
                        'operator' => 'Contains'
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'PHARMACY');
        $this->assertArrayHasKey('filters.pharmacyInfo.pharmacyName.value', $errors);
    }

    /**
     * Test validation for multi-select field
     */
    public function testValidationForMultiSelectField()
    {
        // Valid multi-select field with predefined options
        $filterData = [
            'filters' => [
                'channel' => [
                    'subscribedTo' => [
                        'isInclude' => true,
                        'value' => ['Whatsapp Subcribers']
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'GENERAL NEWSLETTER');
        $this->assertEmpty($errors, 'Validation should pass for valid multi-select field');

        // Invalid value (not array)
        $filterData = [
            'filters' => [
                'channel' => [
                    'subscribedTo' => [
                        'isInclude' => true,
                        'value' => 'Not an array'
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'GENERAL NEWSLETTER');
        $this->assertArrayHasKey('filters.channel.subscribedTo.value', $errors);

        // Invalid option value
        $filterData = [
            'filters' => [
                'channel' => [
                    'subscribedTo' => [
                        'isInclude' => true,
                        'value' => ['Invalid Option']
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'GENERAL NEWSLETTER');
        $this->assertArrayHasKey('filters.channel.subscribedTo.value', $errors);
    }

    /**
     * Test validation for text field
     */
    public function testValidationForTextField()
    {
        // Valid text field
        $filterData = [
            'filters' => [
                'purchase' => [
                    'orderedInTheLast' => [
                        'isInclude' => true,
                        'value' => '30',
                        'unit' => 'day'
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'PHARMACY');
        $this->assertEmpty($errors, 'Validation should pass for valid text field');

        // Invalid value (not numeric)
        $filterData = [
            'filters' => [
                'purchase' => [
                    'orderedInTheLast' => [
                        'isInclude' => true,
                        'value' => 'not a number',
                        'unit' => 'day'
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'PHARMACY');
        $this->assertArrayHasKey('filters.purchase.orderedInTheLast.value', $errors);

        // Invalid unit
        $filterData = [
            'filters' => [
                'purchase' => [
                    'orderedInTheLast' => [
                        'isInclude' => true,
                        'value' => '30',
                        'unit' => 'invalid_unit'
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'PHARMACY');
        $this->assertArrayHasKey('filters.purchase.orderedInTheLast.unit', $errors);
    }

    /**
     * Test validation for range field
     */
    public function testValidationForRangeField()
    {
        // Valid range field
        $filterData = [
            'filters' => [
                'purchase' => [
                    'amountOfPurchases' => [
                        'isInclude' => true,
                        'minAmountPurchases' => 5,
                        'maxAmountPurchases' => 20
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'PHARMACY');
        $this->assertEmpty($errors, 'Validation should pass for valid range field');

        // Invalid value (min > max)
        $filterData = [
            'filters' => [
                'purchase' => [
                    'amountOfPurchases' => [
                        'isInclude' => true,
                        'minAmountPurchases' => 30,
                        'maxAmountPurchases' => 20
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'PHARMACY');
        $this->assertArrayHasKey('filters.purchase.amountOfPurchases', $errors);

        // Non-numeric values
        $filterData = [
            'filters' => [
                'purchase' => [
                    'amountOfPurchases' => [
                        'isInclude' => true,
                        'minAmountPurchases' => 'five',
                        'maxAmountPurchases' => 'twenty'
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'PHARMACY');
        $this->assertArrayHasKey('filters.purchase.amountOfPurchases.minAmountPurchases', $errors);
        $this->assertArrayHasKey('filters.purchase.amountOfPurchases.maxAmountPurchases', $errors);
    }

    /**
     * Test validation for date range field
     */
    public function testValidationForDateRangeField()
    {
        // Valid date range field
        $filterData = [
            'filters' => [
                'creation' => [
                    'createdAt' => [
                        'isInclude' => true,
                        'earliestDate' => '2023-01-01',
                        'latestDate' => '2023-12-31'
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'PHARMACY');
        $this->assertEmpty($errors, 'Validation should pass for valid date range field');

        // Invalid date format
        $filterData = [
            'filters' => [
                'creation' => [
                    'createdAt' => [
                        'isInclude' => true,
                        'earliestDate' => '01/01/2023',
                        'latestDate' => '31/12/2023'
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'PHARMACY');
        $this->assertArrayHasKey('filters.creation.createdAt.earliestDate', $errors);
        $this->assertArrayHasKey('filters.creation.createdAt.latestDate', $errors);

        // Invalid date range (start > end)
        $filterData = [
            'filters' => [
                'creation' => [
                    'createdAt' => [
                        'isInclude' => true,
                        'earliestDate' => '2023-12-31',
                        'latestDate' => '2023-01-01'
                    ]
                ]
            ]
        ];

        $errors = $this->filterValidationHelper->validate($filterData, 'PHARMACY');
        $this->assertArrayHasKey('filters.creation.createdAt', $errors);
    }
}
