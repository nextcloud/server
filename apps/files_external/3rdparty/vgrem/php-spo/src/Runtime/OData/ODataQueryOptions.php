<?php


namespace Office365\PHP\Client\Runtime\OData;


/**
 * Represents the OData raw query values in the string format from the incoming request.
 */
class ODataQueryOptions
{

    public function isEmpty(){
        return (count($this->getProperties()) == 0);
    }

    public function toUrl()
    {
        $url = implode('&',array_map(
                function ($key,$val) {
                    $key = "\$" . strtolower($key);
                    return "$key=$val";
                },array_keys($this->getProperties()),$this->getProperties())
        );
        return $url;
    }


    private function getProperties(){
        return array_filter((array) $this);
    }

    public $Select;

    public $Filter;

    public $Expand;

    public $OrderBy;

    public $Top;

    public $Skip;

    public $Search;
}