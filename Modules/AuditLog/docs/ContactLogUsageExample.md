# Contact Log Module

This document provides examples for Contact Log

## Introduction

Contact Log using events `ContactLogged.php` and listener `SaveContactLog.php`.

The event can be called by using:

`event(new ContactLogged($type, $contact_flag, $contact_id, $campaign_id, $description, $creator_name, $creator_email))`

This event takes the following field:
- `type` : type of contact log
- `contact_flag` : b2b or b2c
- `contact_id` : primary_key(id) of contacts
- `campaign_id` : primary_key(id) of campaign_id
- `description` : details of log. must be `json` type
- `creator_name` : created or updated user_name
- `creator_email` : created or updated user_email

## Examples 

### 1. Module Contacts - Create New Pharmacy

- in PharmacyController add the following:

`use Modules\AuditLog\Events\ContactLogged;`

- then inside `addPharmacyData function` call the event after add new contact:

`$description['title'] = "Added manually by user_name";`

`event(new ContactLogged('pharmacy', 'b2b', $contact_id, null, $description, 'user_name', 'user_email'));`

### 2. Module WhatsApp - Create WhatsApp Campaigns

- in WhatsappController add the following:

`use Modules\AuditLog\Events\ContactLogged;`

- then call the event after add new whatsapp campaign:

$description is as follows:

```
$description['title'] = "WhasApp Campaign Sent";
$description['detail']['title'] = "(#1) [Export Name 1]";
$description['detail']['image_url'] = "image_url";
$description['detail']['view_report_url'] = "view_report_url";
$description['detail']['view_whatsapp_message'] = "view_url";
$description['detail']['template'] = "Template used: [template_name_1]";
$description['detail']['from'] = "From: +49 123 456 789";
```

or in json will look like this

```
{
    "title" : "WhasApp Campaign Sent",
    "details" : {
        "title" : "(#1) [Export Name 1]",
        "image_url" : "image_url",
        "view_report_url" : "view_url",
        "view_whatsapp_message" : "view_url"
        "img": "/logo.png",
        "template": "Template used: [template_name_1]",
        "from": "From: +49 123 456 789"
    }
}
```

`event(new ContactLogged('wa_campaign', 'b2b', $contact_id, $campaign_id, $description, 'user_name', 'user_email'));`