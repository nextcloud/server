<?php


namespace Office365\PHP\Client\OutlookServices;


use Office365\PHP\Client\Runtime\ClientValueObject;

/**
 * The frequency of an event.
 */
class RecurrencePattern extends ClientValueObject
{

    /**
     * The recurrence pattern type: Daily = 0, Weekly = 1, AbsoluteMonthly = 2, RelativeMonthly = 3, AbsoluteYearly = 4, RelativeYearly = 5.
     * @var int
     */
    public $Type;


    /**
     * The number of units of a given recurrence type between occurrences.
     * @var int
     */
    public $Interval;


    /**
     * The day of month that the item occurs on.
     * @var int
     */
    public $DayOfMonth;


    /**
     * 	The month that the item occurs on. This is a number from 1 to 12.
     * @var int
     */
    public $Month;


    /**
     * A collection of days of the week: Sunday = 0, Monday = 1, Tuesday = 2, Wednesday = 3, Thursday = 4, Friday = 5, Saturday = 6.
     * @var array
     */
    public $DaysOfWeek;


    /**
     * The day of the week: Sunday = 0, Monday = 1, Tuesday = 2, Wednesday = 3, Thursday = 4, Friday = 5, Saturday = 6.
     * @var int
     */
    public $FirstDayOfWeek;


    /**
     * The week index: First = 0, Second = 1, Third = 2, Fourth = 3, Last = 4.
     * @var int
     */
    public $WeekIndex;

}