<?php

namespace Office365\PHP\Client\Runtime\Utilities;


class JsonConvert
{

    /**
     * Specify data which should be serialized to JSON payload
     * @param mixed $object
     * @param callable $onSerialize
     * @return string
     */
    public static function serialize($object,callable $onSerialize = null){
        if(isset($onSerialize))
            $object = call_user_func($onSerialize,$object);
        return json_encode($object);
    }


    /**
     * Creates client object from JSON payload
     * @param string $jsonString
     * @param string $objectTypeName
     * @return mixed
     */
    public static function deserialize ($jsonString, $objectTypeName = null)
    {
        if(isset($objectTypeName)){
            $json = json_decode($jsonString);
            $object = new $objectTypeName;
            self::populate($object,$json);
            return $object;
        }
        return json_decode($jsonString);
    }


    /**
     * Populates the object with values from the JSON payload
     * @param mixed $target
     * @param \stdClass $json
     */
    public static function populate($target, \stdClass $json)
    {
        foreach ($json as $key => $value) {
            $target->{$key} = $value;
        }
    }



}