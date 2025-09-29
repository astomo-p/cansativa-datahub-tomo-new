<?php

namespace Modules\NewContactData\Helpers;

class TranslatorHelper
{
    private static function getJsonLangSetup($lang) {
        $path = dirname(__FILE__,5);
        $json_file_content = file_get_contents($path . '/resources/lang/locales/'.$lang.'.json');
        $lang_array = json_decode($json_file_content,true);
        return $lang_array;
    }
    public static function getTranslate($input,$lang) {
        $translate = self::getJsonLangSetup($lang); 
        return $translate[$input];
    }
    public function handle() {}
}
