<?php

namespace Modules\NewContactData\Helpers;

class ContactTypeHelper
{
    private static $data_type = ["email","phone number","postcode","number","boolean","text"];
    private static $pattern_type = ["/.+@.+\..+/","/\+?\d{3,20}/","/\d{6}/","/[^\w]+\d+/","/(yes|no|Yes|No)/","/[a-zA-Z0-9]+/"];
    public static function checkDataType($input) {
        $result = '';
        $counter = 0;
        foreach(self::$pattern_type as $pattern){
            preg_match($pattern,$input,$matches);
            if(count($matches) > 0){
                $result = self::$data_type[$counter];
                break;
            }
            $counter++;
        }
        return $result;
    }
    public function handle() {}
}
