<?php

namespace Modules\WhatsappNewsletter\Services;

class FilterConfigService
{
    /**
     * Get filter configuration based on contact type
     *
     * @param string $contactTypeName
     * @return array
     */
    public function getFilterConfig(string $contactTypeName): array
    {
        $contactTypeName = strtoupper($contactTypeName);

        switch ($contactTypeName) {
            case 'PHARMACY':
                return $this->getPharmacyFilters();
            case 'SUPPLIER':
                return $this->getSupplierFilters();
            case 'GENERAL NEWSLETTER':
                return $this->getGeneralNewsletterFilters();
            case 'COMMUNITY':
                return $this->getCommunityFilters();
            case 'PHARMACY DATABASE':
                return $this->getPharmacyDatabaseFilters();
            default:
                return [];
        }
    }

    /**
     * Get pharmacy filters
     *
     * @return array
     */
    private function getPharmacyFilters(): array
    {
        return [
            [
                'name' => 'Pharmacy Info',
                'filters' => [
                    [
                        'name' => 'pharmacyName',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ],
                    [
                        'name' => 'pharmacyNumber',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ]
                ]
            ],
            [
                'name' => 'Location',
                'filters' => [
                    [
                        'name' => 'postcode',
                        'type' => 'multiSelect',
                        'options' => []
                    ],
                    [
                        'name' => 'city',
                        'type' => 'multiSelect',
                        'options' => []
                    ],
                    [
                        'name' => 'country',
                        'type' => 'multiSelect',
                        'options' => []
                    ]
                ]
            ],
            [
                'name' => 'Purchase',
                'filters' => [
                    [
                        'name' => 'orderedInTheLast',
                        'type' => 'text',
                        'options' => [
                            'day',
                            'month',
                            'year'
                        ]
                    ],
                    [
                        'name' => 'amountOfPurchases',
                        'type' => 'range',
                        'options' => [
                            'min' => 'minAmountPurchases',
                            'max' => 'maxAmountPurchases'
                        ]
                    ],
                    [
                        'name' => 'totalPurchaseValue',
                        'type' => 'range',
                        'options' => [
                            'min' => 'minTotalPurchaseValue',
                            'max' => 'maxTotalPurchaseValue'
                        ]
                    ],
                    [
                        'name' => 'averagePurchaseValue',
                        'type' => 'range',
                        'options' => [
                            'min' => 'minAveragePurchaseValue',
                            'max' => 'maxAveragePurchaseValue'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Contact',
                'filters' => [
                    [
                        'name' => 'contactPerson',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ],
                    [
                        'name' => 'email',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ],
                    [
                        'name' => 'phoneNumber',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ]
                ]
            ],
            [
                'name' => 'Creation',
                'filters' => [
                    [
                        'name' => 'createdAt',
                        'type' => 'dateRange',
                        'options' => [
                            'start' => 'earliestDate',
                            'end' => 'latestDate'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get supplier filters
     *
     * @return array
     */
    private function getSupplierFilters(): array
    {
        return [
            [
                'name' => 'Supplier Info',
                'filters' => [
                    [
                        'name' => 'companyName',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ],
                    [
                        'name' => 'vatId',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ]
                ]
            ],
            [
                'name' => 'Location',
                'filters' => [
                    [
                        'name' => 'address',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ],
                    [
                        'name' => 'postcode',
                        'type' => 'multiSelect',
                        'options' => []
                    ],
                    [
                        'name' => 'city',
                        'type' => 'multiSelect',
                        'options' => []
                    ],
                    [
                        'name' => 'country',
                        'type' => 'multiSelect',
                        'options' => []
                    ]
                ]
            ],
            [
                'name' => 'Purchase',
                'filters' => [
                    [
                        'name' => 'orderedInTheLast',
                        'type' => 'text',
                        'options' => [
                            'day',
                            'month',
                            'year'
                        ]
                    ],
                    [
                        'name' => 'amountOfPurchases',
                        'type' => 'range',
                        'options' => [
                            'min' => 'minAmountPurchases',
                            'max' => 'maxAmountPurchases'
                        ]
                    ],
                    [
                        'name' => 'totalPurchaseValue',
                        'type' => 'range',
                        'options' => [
                            'min' => 'minTotalPurchaseValue',
                            'max' => 'maxTotalPurchaseValue'
                        ]
                    ],
                    [
                        'name' => 'averagePurchaseValue',
                        'type' => 'range',
                        'options' => [
                            'min' => 'minAveragePurchaseValue',
                            'max' => 'maxAveragePurchaseValue'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Contact',
                'filters' => [
                    [
                        'name' => 'contactPerson',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ],
                    [
                        'name' => 'email',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ],
                    [
                        'name' => 'phoneNumber',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ]
                ]
            ],
            [
                'name' => 'Creation',
                'filters' => [
                    [
                        'name' => 'createdAt',
                        'type' => 'dateRange',
                        'options' => [
                            'start' => 'earliestDate',
                            'end' => 'latestDate'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get general newsletter filters
     *
     * @return array
     */
    private function getGeneralNewsletterFilters(): array
    {
        return [
            [
                'name' => 'Contact Info',
                'filters' => [
                    [
                        'name' => 'fullName',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ],
                    [
                        'name' => 'email',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ],
                    [
                        'name' => 'phoneNumber',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ]
                ]
            ],
            [
                'name' => 'Channel',
                'filters' => [
                    [
                        'name' => 'subscribedTo',
                        'type' => 'multiSelect',
                        'options' => [
                            'Whatsapp Subcribers',
                            'Email Subscribers'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Creation',
                'filters' => [
                    [
                        'name' => 'createdAt',
                        'type' => 'dateRange',
                        'options' => [
                            'start' => 'earliestDate',
                            'end' => 'latestDate'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get community filters
     *
     * @return array
     */
    private function getCommunityFilters(): array
    {
        return [
            [
                'name' => 'Contact Info',
                'filters' => [
                    [
                        'name' => 'fullName',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ],
                    [
                        'name' => 'email',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ],
                    [
                        'name' => 'phoneNumber',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ]
                ]
            ],
            [
                'name' => 'Channel',
                'filters' => [
                    [
                        'name' => 'subscribedTo',
                        'type' => 'multiSelect',
                        'options' => [
                            'Whatsapp Subcribers',
                            'Email Subscribers'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Interactions',
                'filters' => [
                    [
                        'name' => 'amountOfLikes',
                        'type' => 'range',
                        'options' => [
                            'min' => 'minAmountLikes',
                            'max' => 'maxAmountLikes'
                        ]
                    ],
                    [
                        'name' => 'amountOfComments',
                        'type' => 'range',
                        'options' => [
                            'min' => 'minComments',
                            'max' => 'maxComments'
                        ]
                    ],
                    [
                        'name' => 'amountOfSubmissions',
                        'type' => 'range',
                        'options' => [
                            'min' => 'minSubsmissions',
                            'max' => 'maxSubmissions'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Creation',
                'filters' => [
                    [
                        'name' => 'accountCreation',
                        'type' => 'dateRange',
                        'options' => [
                            'start' => 'earliestDate',
                            'end' => 'latestDate'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'User Activity',
                'filters' => [
                    [
                        'name' => 'latestLogin',
                        'type' => 'dateRange',
                        'options' => [
                            'start' => 'earliestDate',
                            'end' => 'latestDate'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get pharmacy database filters
     *
     * @return array
     */
    private function getPharmacyDatabaseFilters(): array
    {
        return [
            [
                'name' => 'Contact Info',
                'filters' => [
                    [
                        'name' => 'fullName',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ],
                    [
                        'name' => 'email',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ],
                    [
                        'name' => 'phoneNumber',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ]
                ]
            ],
            [
                'name' => 'Location',
                'filters' => [
                    [
                        'name' => 'address',
                        'type' => 'multipleText',
                        'options' => $this->getFreetextOptions()
                    ],
                    [
                        'name' => 'postCode',
                        'type' => 'multiSelect',
                        'options' => []
                    ],
                    [
                        'name' => 'city',
                        'type' => 'multiSelect',
                        'options' => []
                    ],
                    [
                        'name' => 'country',
                        'type' => 'multiSelect',
                        'options' => []
                    ]
                ]
            ],
            [
                'name' => 'Channel',
                'filters' => [
                    [
                        'name' => 'subscribedTo',
                        'type' => 'multiSelect',
                        'options' => [
                            'Whatsapp Subcribers',
                            'Email Subscribers'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Creation',
                'filters' => [
                    [
                        'name' => 'createdAt',
                        'type' => 'dateRange',
                        'options' => [
                            'start' => 'earliestDate',
                            'end' => 'latestDate'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get freetext options
     *
     * @return array
     */
    private function getFreetextOptions(): array
    {
        return [
            'Is equal to',
            'Is not equal to',
            'Contains',
            'Does not contains',
            'Starts with',
            'Does not starts with',
            'Ends with',
            'Does not end with',
            'Is empty',
            'Is not empty'
        ];
    }
}
