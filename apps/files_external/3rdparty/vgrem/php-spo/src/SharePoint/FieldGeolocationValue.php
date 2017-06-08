<?php


namespace Office365\PHP\Client\SharePoint;


use Office365\PHP\Client\Runtime\ClientValueObject;

class FieldGeolocationValue extends ClientValueObject
{
    /**
     * @var double
     */
    public $Altitude;

    /**
     * @var double
     */
    public $Latitude;


    /**
     * @var double
     */
    public $Longitude;

    /**
     * @var double
     */
    public $Measure;

}