<?php


namespace Office365\PHP\Client\OutlookServices;

/**
 * An event attendee.
 */
class Attendee extends Recipient
{

    /**
     * The response (none, accepted, declined, etc.) and time.
     * @var ResponseStatus $Status
     */
    public $Status;

    /**
     * The type of the attendee: Required = 0, Optional = 1, Resource = 2.
     * @var string $Type
     */
    public $Type;

}