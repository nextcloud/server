<?php


namespace Office365\PHP\Client\OutlookServices;


use Office365\PHP\Client\Runtime\ClientValueObject;

/**
 * The location of an event.
 */
class Location extends ClientValueObject
{

    /**
     * The name associated with the location.
     * @var string
     */
    public $DisplayName;


    /**
     * The physical address of the location.
     * @var PhysicalAddress
     */
    public $Address;


    /**
     * The geographic coordinates and elevation of the location.
     * @var GeoCoordinates
     */
    public $Coordinates;
}