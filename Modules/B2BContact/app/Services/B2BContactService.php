<?php

namespace Modules\B2BContact\Services;

use Modules\B2BContact\Models\ContactPersons;

class B2BContactService
{
    public function addContactPerson($new_contact = null, $data_contact_person)
    {
        foreach ($data_contact_person as $data) {
            $contact_persons[] = ContactPersons::create([
                'contact_person' => $new_contact->id,
                'contact_name' => $data['name'],
                'email' => $data['email'],
                'phone_no' => $data['phone_no']
            ]);
        }
        return $contact_persons;
    }

    public function updateContactPerson($id, $data_contact_person)
    {
        foreach ($data_contact_person as $data) {
            $result = ContactPersons::where('contact_person', $id)
            ->where('id', $data['id'])
            ->update([
                'contact_name' => $data['name'],
                'email' => $data['email'],
                'phone_no' => $data['phone_no']
            ]);
            if (!$result) {
                return false;
            }
        }
        return $result;
    }

    public function exportContact($file, $contact_type)
    {
        switch ($contact_type) {
            case 'pharmacy':
                
                break;
            case 'supplier':
                break;
            case 'general-newsletter':
                break;
            default:
                return false;
                break;
        }
    }

}
