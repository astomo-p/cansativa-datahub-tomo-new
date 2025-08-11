# Audit Log Module

This document provides examples for Audit Log

## Introduction

Audit Log using events `AuditLogged.php` and listener `SaveAuditLog.php`.

`This log automatically insert full_name and email by authentication`

The event can be called by using:

`event(new AuditLogged('module', 'activity'))`

This event takes the following field:
- `module` : name of module which the user performed action (e.g. : WhatsApp, Contacts, Setting, etc)
- `activity` : name of the activity (e.g. : Create Pharmacy Contact, Create WhatApp Campaigns, Change Language to German, etc)

## Modules

There are several modules available. If needed, add new module in `AuditLogs` Model

```
MODULE_PHARMACY = 'Pharmacies';
MODULE_SUPPLIER = 'Supplier';
MODULE_COMMUNITY = 'High Passion Community';
MODULE_PHARMACY_DB = 'Pharmacy Databases';
MODULE_WA_NEWSLETTER = 'WhatsApp Newsletter';
MODULE_EMAIL_NEWSLETTER = 'Email Newsletter';
MODULE_WA_CENTER = 'WhatsApp Center';
MODULE_SETTING = 'Setting';
```

## Examples 

### 1. Module Contacts - Create New Pharmacy

- in PharmacyController add the following:

`use Modules\AuditLog\Events\AuditLogged;`

- then inside `addPharmacyData function` call the event after add new contact:

`event(new AuditLogged(AuditLogs::MODULE_PHARMACY, 'Create Pharmacy Contacts'));`

### 2. Module WhatsApp - Create WhatsApp Campaigns

- in WhatsappController add the following:

`use Modules\AuditLog\Events\AuditLogged;`

- then call the event after add new whatsapp campaign:

`event(new AuditLogged(AuditLogs::MODULE_WA_NEWSLETTER, 'Create WhatApp Campaigns'));`