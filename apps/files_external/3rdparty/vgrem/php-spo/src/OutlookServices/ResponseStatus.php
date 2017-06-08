<?php


namespace Office365\PHP\Client\OutlookServices;


use Office365\PHP\Client\Runtime\ClientValueObject;

/**
 * The response status of a meeting request.
 */
class ResponseStatus extends ClientValueObject
{

    /**
     * The response type: None = 0, Organizer = 1, TentativelyAccepted = 2, Accepted = 3, Declined = 4, NotResponded = 5.
     * @var int
     */
    public $Response;


    /**
     * The date and time that the response was returned.
     * @var \DateTime
     */
    public $Time;

}