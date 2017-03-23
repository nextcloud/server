<?php


namespace Office365\PHP\Client\Runtime;

/**
 *  Complex type represents a key value pair.
 *  Ref: https://msdn.microsoft.com/en-us/library/hh642428(v=office.12).aspx
 */
class KeyValue
{

    /**
     * @var string The value of the key in the key value pair.
     */
    public $Key;

    /**
     * @var string The string representation of the value in the key value pair.
     */
    public $Value;

    /**
     * @var string The EDM type name of the value in the key value pair.
     */
    public $ValueType;
}