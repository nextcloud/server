<?php


namespace Office365\PHP\Client\OutlookServices;

use Office365\PHP\Client\Runtime\ClientValueObject;

/**
 * The duration of an event.
 */
class RecurrenceRange extends ClientValueObject
{

    /**
     * The recurrence range: EndDate = 0, NoEnd = 1, Numbered = 2.
     * @var int
     */
    public $Type;


    /**
     * The start date of the series.
     * @var string
     */
    public $StartDate;


    /**
     * The end date of the series.
     * @var string
     */
    public $EndDate;


    /**
     * How many times to repeat the event.
     * @var int
     */
    public $NumberOfOccurrences;

}