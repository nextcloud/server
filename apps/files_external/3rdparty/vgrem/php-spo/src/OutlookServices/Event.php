<?php


namespace Office365\PHP\Client\OutlookServices;


/**
 * An event in a calendar.
 */
class Event extends Item
{

    /**
     * @var string
     */
    public $Subject;


    /**
     * The body of the message associated with the event.
     * @var ItemBody
     */
    public $Body;


    /**
     * The collection of attendees for the event.
     * @var array
     */
    public $Attendees;


    /**
     * The location of the event.
     * @var Location
     */
    public $Location;


    /**
     * The status to show: Free = 0, Tentative = 1, Busy = 2, Oof = 3, WorkingElsewhere = 4, Unknown = -1.
     * @var int
     */
    public $ShowAs;


    /**
     * The start time of the event.
     * @var DateTimeTimeZone
     */
    public $Start;


    /**
     * The event type: SingleInstance = 0, Occurrence = 1, Exception = 2, SeriesMaster = 3.
     * @var int
     */
    public $Type;


    /**
     * The URL to open the event in Outlook Web App.
     * @var string
     */
    public $WebLink;
}