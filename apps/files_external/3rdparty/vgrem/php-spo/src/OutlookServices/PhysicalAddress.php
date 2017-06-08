<?php


namespace Office365\PHP\Client\OutlookServices;

use Office365\PHP\Client\Runtime\ClientValueObject;

/**
 * The physical address of a contact.
 */
class PhysicalAddress extends ClientValueObject
{

    /**
     * The street.
     * @var string
     */
    public $Street;


    /**
     * The city.
     * @var string
     */
    public $City;


    /**
     * The state.
     * @var string
     */
    public $State;


    /**
     * The country or region. It's a free-format string value, for example, "United States".
     * @var string
     */
    public $CountryOrRegion;


    /**
     * The postal code.
     * @var string
     */
    public $PostalCode;

}