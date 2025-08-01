# Filter JSON Examples for WhatsApp Newsletter Module

This document provides examples of JSON structures for filtering different contact types in the WhatsApp Newsletter module.

## Introduction

The filter JSON structure should match the configuration defined in `FilterConfigService.php`. Each contact type has different filter groups and options.

Key features of the filter structure:
- Each filter has an `isInclude` property to determine whether to include or exclude matching records
- Text filters have operators and values (which can be arrays for multiple values)
- Range filters have min/max values
- Date range filters have start/end dates
- Multi-select filters have arrays of selected values

## JSON Examples by Contact Type

### 1. PHARMACY Contact Type

```json
{
  "filters": {
    "pharmacyInfo": {
      "pharmacyName": {
        "isInclude": true,
        "operator": "Contains",
        "value": ["Medic", "Gesundheit", "Apotheke"]
      },
      "pharmacyNumber": {
        "isInclude": true,
        "operator": "Is equal to",
        "value": ["12345", "67890"]
      }
    },
    "location": {
      "postcode": {
        "isInclude": true,
        "value": ["10115", "10117", "10119"]
      },
      "city": {
        "isInclude": false,
        "value": ["Berlin", "Munich"]
      },
      "country": {
        "isInclude": true,
        "value": ["Germany"]
      }
    },
    "purchase": {
      "orderedInTheLast": {
        "isInclude": true,
        "value": "30",
        "unit": "day"
      },
      "amountOfPurchases": {
        "isInclude": true,
        "minAmountPurchases": 5,
        "maxAmountPurchases": 20
      },
      "totalPurchaseValue": {
        "isInclude": true,
        "minTotalPurchaseValue": 1000,
        "maxTotalPurchaseValue": 5000
      },
      "averagePurchaseValue": {
        "isInclude": false,
        "minAveragePurchaseValue": 200,
        "maxAveragePurchaseValue": 1000
      }
    },
    "contact": {
      "contactPerson": {
        "isInclude": true,
        "operator": "Contains",
        "value": ["Schmidt", "Meyer"]
      },
      "email": {
        "isInclude": true,
        "operator": "Contains",
        "value": ["@pharmacy.com", "@apotheke.de"]
      },
      "phoneNumber": {
        "isInclude": true,
        "operator": "Starts with",
        "value": ["+49", "+43"]
      }
    },
    "creation": {
      "createdAt": {
        "isInclude": true,
        "earliestDate": "2024-01-01",
        "latestDate": "2025-06-01"
      }
    }
  }
}
```

### 2. SUPPLIER Contact Type

```json
{
  "filters": {
    "supplierInfo": {
      "companyName": {
        "isInclude": true,
        "operator": "Contains",
        "value": ["Pharma", "Medical", "Health"]
      },
      "vatId": {
        "isInclude": true,
        "operator": "Is equal to",
        "value": ["DE123456789", "DE987654321"]
      }
    },
    "location": {
      "address": {
        "isInclude": true,
        "operator": "Contains",
        "value": ["Hauptstraße", "Marktplatz", "Industrieweg"]
      },
      "postcode": {
        "isInclude": true,
        "value": ["60306", "60308"]
      },
      "city": {
        "isInclude": true,
        "value": ["Frankfurt", "Hamburg"]
      },
      "country": {
        "isInclude": true,
        "value": ["Germany"]
      }
    },
    "purchase": {
      "orderedInTheLast": {
        "isInclude": true,
        "value": "6",
        "unit": "month"
      },
      "amountOfPurchases": {
        "isInclude": true,
        "minAmountPurchases": 10,
        "maxAmountPurchases": 50
      },
      "totalPurchaseValue": {
        "isInclude": true,
        "minTotalPurchaseValue": 10000,
        "maxTotalPurchaseValue": 50000
      },
      "averagePurchaseValue": {
        "isInclude": false,
        "minAveragePurchaseValue": 1000,
        "maxAveragePurchaseValue": 5000
      }
    },
    "contact": {
      "contactPerson": {
        "isInclude": true,
        "operator": "Contains",
        "value": ["Müller", "Weber", "Fischer"]
      },
      "email": {
        "isInclude": true,
        "operator": "Contains",
        "value": ["@supplier.com", "@vendor.de"]
      },
      "phoneNumber": {
        "isInclude": false,
        "operator": "Starts with",
        "value": ["+49", "+43"]
      }
    },
    "creation": {
      "createdAt": {
        "isInclude": true,
        "earliestDate": "2023-01-01",
        "latestDate": "2025-06-01"
      }
    }
  }
}
```

### 3. GENERAL NEWSLETTER Contact Type

```json
{
  "filters": {
    "contactInfo": {
      "fullName": {
        "isInclude": true,
        "operator": "Contains",
        "value": ["Weber", "Schmidt"]
      },
      "email": {
        "isInclude": true,
        "operator": "Contains",
        "value": ["@gmail.com", "@hotmail.com"]
      },
      "phoneNumber": {
        "isInclude": true,
        "operator": "Starts with",
        "value": ["+49", "+43"]
      }
    },
    "channel": {
      "subscribedTo": {
        "isInclude": true,
        "value": ["Whatsapp Subcribers", "Email Subscribers"]
      }
    },
    "creation": {
      "createdAt": {
        "isInclude": true,
        "earliestDate": "2024-01-01",
        "latestDate": "2025-05-01"
      }
    }
  }
}
```

### 4. COMMUNITY Contact Type

```json
{
  "filters": {
    "contactInfo": {
      "fullName": {
        "isInclude": true,
        "operator": "Contains",
        "value": ["Fischer", "Meyer"]
      },
      "email": {
        "isInclude": true,
        "operator": "Contains",
        "value": ["@outlook.com", "@yahoo.com"]
      },
      "phoneNumber": {
        "isInclude": false,
        "operator": "Starts with",
        "value": ["+49", "+43"]
      }
    },
    "channel": {
      "subscribedTo": {
        "isInclude": true,
        "value": ["Whatsapp Subcribers"]
      }
    },
    "interactions": {
      "amountOfLikes": {
        "isInclude": true,
        "minAmountLikes": 10,
        "maxAmountLikes": 100
      },
      "amountOfComments": {
        "isInclude": true,
        "minComments": 5,
        "maxComments": 50
      },
      "amountOfSubmissions": {
        "isInclude": true,
        "minSubsmissions": 1,
        "maxSubmissions": 10
      }
    },
    "creation": {
      "accountCreation": {
        "isInclude": true,
        "earliestDate": "2023-01-01",
        "latestDate": "2025-06-01"
      }
    },
    "userActivity": {
      "latestLogin": {
        "isInclude": true,
        "earliestDate": "2025-01-01",
        "latestDate": "2025-06-01"
      }
    }
  }
}
```

### 5. PHARMACY DATABASE Contact Type

```json
{
  "filters": {
    "contactInfo": {
      "fullName": {
        "isInclude": true,
        "operator": "Contains",
        "value": ["Schneider", "Wagner"]
      },
      "email": {
        "isInclude": true,
        "operator": "Contains",
        "value": ["@pharmacy-db.com", "@apotheke-db.de"]
      },
      "phoneNumber": {
        "isInclude": true,
        "operator": "Starts with",
        "value": ["+49", "+43"]
      }
    },
    "location": {
      "address": {
        "isInclude": true,
        "operator": "Contains",
        "value": ["Bahnhofstraße", "Hauptplatz"]
      },
      "postCode": {
        "isInclude": true,
        "value": ["80331", "80333"]
      },
      "city": {
        "isInclude": true,
        "value": ["Munich"]
      },
      "country": {
        "isInclude": false,
        "value": ["Austria"]
      }
    },
    "channel": {
      "subscribedTo": {
        "isInclude": true,
        "value": ["Email Subscribers"]
      }
    },
    "creation": {
      "createdAt": {
        "isInclude": true,
        "earliestDate": "2024-01-01",
        "latestDate": "2025-06-01"
      }
    }
  }
}
```

## Filter Types and Structure

### Filter Type Details

1. **multipleText**
   - Used for text-based filters with comparison operators
   - Options from `getFreetextOptions()` include: "Is equal to", "Contains", etc.
   - Values can be an array of strings

2. **multiSelect**
   - For selecting multiple values from a predefined list
   - Values are always arrays

3. **text**
   - For text input with specific time options (day, month, year)
   - Has value and unit properties

4. **range**
   - For numeric ranges with min/max values
   - Uses specific min/max property names as defined in the service

5. **dateRange**
   - For date ranges with start/end values
   - Uses specific start/end property names as defined in the service

### The `isInclude` Flag

Each filter has an `isInclude` flag that determines the filter behavior:
- `true`: Include records that match this filter criteria
- `false`: Exclude records that match this filter criteria (negative filtering)

This enables complex queries where some criteria are used for inclusion and others for exclusion.
