<?php


namespace Office365\PHP\Client\OutlookServices;


use Office365\PHP\Client\Runtime\ClientValueObject;

/**
 * Class Reminder
 * @package Office365\PHP\Client\OutlookServices
 */
class Reminder extends ClientValueObject
{

    /**
     * @var string $EventId
     */
    public $EventId;


    /**
     * @var DateTimeTimeZone $EventStartTime
     */
    public $EventStartTime;


    /**
     * @var DateTimeTimeZone $EventEndTime
     */
    public $EventEndTime;


    /**
     * @var string $ChangeKey
     */
    public $ChangeKey;


    /**
     * @var string $EventSubject
     */
    public $EventSubject;


    /**
     * @var Location $EventLocation
     */
    public $EventLocation;


    /**
     * @var string $EventWebLink
     */
    public $EventWebLink;


    /**
     * @var DateTimeTimeZone $ReminderFireTime
     */
    public $ReminderFireTime;

}