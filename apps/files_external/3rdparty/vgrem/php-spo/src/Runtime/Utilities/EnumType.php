<?php


namespace Office365\PHP\Client\Runtime\Utilities;

use ReflectionClass;

abstract class EnumType
{

    public static function getName($value){
        $reflection = new ReflectionClass(get_called_class());
        $enums = array_flip($reflection->getConstants());
        return $enums[$value];
    }

    public static function getNames(){
        $reflection = new ReflectionClass(get_called_class());
        $enums = $reflection->getConstants();
        return array_keys($enums);
    }


    public static function getValues(){
        $reflection = new ReflectionClass(get_called_class());
        $enums = $reflection->getConstants();
        return array_values($enums);
    }
}