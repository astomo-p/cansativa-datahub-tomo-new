<?php

namespace Modules\NewContactData\Helpers;

use Modules\NewContactData\Models\ContactField;
use Modules\NewContactData\Models\ContactFieldValue;

class ContactFieldHelper
{
    private static function formatLabel($input) {
        // Replace underscores with spaces
        $spaced = str_replace('_', ' ', $input);

        // Capitalize the first word only
        return ucfirst('this column is for saving ' . $spaced);
    }

    public static function pushContactField($key,$value) {
        return [
            "field_name"=> $key,
            "field_type"=> gettype($value),
            "description"=> self::formatLabel($key), 
            "value"=>$value
        ];
    } 

   public static function pushFieldValue($items,$contact_id) {
        
        $data_capture = [];
        foreach($items as $item){ 
         $field_id = ContactField::where('field_name',$item['field_name'])->pluck('id'); 
        $data_capture[] = [
            "contact_id"=>$contact_id,
            "contact_field_id"=>$field_id[0],
            "value"=>$item["value"]
        ];  
       }
       return $data_capture;
   }

   public static function getContactFieldData($contact_id,$item) {
       $field = ContactFieldValue::where('contact_id',$contact_id)
       ->addSelect([
        'field_name'=> ContactField::select('field_name')
        ->whereColumn('contact_field_id','contact_fields.id')
       ])
       ->get(); 
       if(gettype($item)  == 'array'){
                $item = (object) $item;
                foreach($field as $elem){
                    $item->{$elem->field_name} = $elem->value;
                } 
                return (array) $item; 
       } else {
       foreach($field as $elem){
            $item->{$elem->field_name} = $elem->value;
       } 
       return $item; 
      }
   }
 
   public static function getContactFieldDataCustomOnly($contact_id) {
       $field = ContactFieldValue::where('contact_id',$contact_id)
       ->addSelect([
        'field_name'=> ContactField::select('field_name')
        ->whereColumn('contact_field_id','contact_fields.id')
       ])
       ->get(); 
       $item = [];
       if(gettype($item)  == 'array'){
                $item = (object) $item;
                foreach($field as $elem){
                    $item->{$elem->field_name} = $elem->value;
                } 
                return (array) $item; 
       } else {
       foreach($field as $elem){
            $item->{$elem->field_name} = $elem->value;
       } 
       return $item; 
      }
   }

    public static function getContactFieldDataCustomOnlyWithContactId($contact_id) {
       $field = ContactFieldValue::where('contact_id',$contact_id)
       ->addSelect([
        'field_name'=> ContactField::select('field_name')
        ->whereColumn('contact_field_id','contact_fields.id')
       ])
       ->get(); 
       $item = [];
       if(gettype($item)  == 'array'){
                $item = (object) $item;
                $item->contact_id = $contact_id;
                foreach($field as $elem){
                    $item->{$elem->field_name} = $elem->value;
                } 
                return (array) $item; 
       } else {
       foreach($field as $elem){
            $item->{$elem->field_name} = $elem->value;
       } 
       return $item; 
      }
   }
   

    public function handle() {}
}
