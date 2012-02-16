<?php

/**
 * DateTime property 
 *
 * This element is used for iCalendar properties such as the DTSTART property. 
 * It basically provides a few helper functions that make it easier to deal 
 * with these. It supports both DATE-TIME and DATE values.
 *
 * In order to use this correctly, you must call setDateTime and getDateTime to 
 * retrieve and modify dates respectively.
 *
 * If you use the 'value' or properties directly, this object does not keep 
 * reference and results might appear incorrectly.
 * 
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_Element_DateTime extends Sabre_VObject_Property {

    /**
     * Local 'floating' time
     */
    const LOCAL = 1;

    /**
     * UTC-based time
     */
    const UTC = 2;

    /**
     * Local time plus timezone
     */
    const LOCALTZ = 3;

    /**
     * Only a date, time is ignored
     */
    const DATE = 4;

    /**
     * DateTime representation
     *
     * @var DateTime
     */
    protected $dateTime;

    /**
     * dateType 
     * 
     * @var int 
     */
    protected $dateType;

    /**
     * Updates the Date and Time. 
     * 
     * @param DateTime $dt 
     * @param int $dateType 
     * @return void
     */
    public function setDateTime(DateTime $dt, $dateType = self::LOCALTZ) {

        switch($dateType) {

            case self::LOCAL :
                $this->setValue($dt->format('Ymd\\THis'));
                $this->offsetUnset('VALUE');
                $this->offsetUnset('TZID');
                $this->offsetSet('VALUE','DATE-TIME'); 
                break;
            case self::UTC :
                $dt->setTimeZone(new DateTimeZone('UTC'));
                $this->setValue($dt->format('Ymd\\THis\\Z'));
                $this->offsetUnset('VALUE');
                $this->offsetUnset('TZID');
                $this->offsetSet('VALUE','DATE-TIME');
                break;
            case self::LOCALTZ :
                $this->setValue($dt->format('Ymd\\THis'));
                $this->offsetUnset('VALUE');
                $this->offsetUnset('TZID');
                $this->offsetSet('VALUE','DATE-TIME');
                $this->offsetSet('TZID', $dt->getTimeZone()->getName());
                break; 
            case self::DATE :
                $this->setValue($dt->format('Ymd'));
                $this->offsetUnset('VALUE');
                $this->offsetUnset('TZID');
                $this->offsetSet('VALUE','DATE');
                break;
            default :
                throw new InvalidArgumentException('You must pass a valid dateType constant');

        }
        $this->dateTime = $dt;
        $this->dateType = $dateType;

    }

    /**
     * Returns the current DateTime value.
     *
     * If no value was set, this method returns null.
     *
     * @return DateTime|null 
     */
    public function getDateTime() {

        if ($this->dateTime)
            return $this->dateTime;

        list(
            $this->dateType,
            $this->dateTime
        ) = self::parseData($this->value, $this);
        return $this->dateTime;

    }

    /**
     * Returns the type of Date format.
     *
     * This method returns one of the format constants. If no date was set, 
     * this method will return null.
     *
     * @return int|null
     */
    public function getDateType() {

        if ($this->dateType)
            return $this->dateType;

        list(
            $this->dateType,
            $this->dateTime,
        ) = self::parseData($this->value, $this);
        return $this->dateType;

    }

    /**
     * Parses the internal data structure to figure out what the current date 
     * and time is.
     *
     * The returned array contains two elements:
     *   1. A 'DateType' constant (as defined on this class), or null. 
     *   2. A DateTime object (or null)
     *
     * @param string|null $propertyValue The string to parse (yymmdd or 
     *                                   ymmddThhmmss, etc..)
     * @param Sabre_VObject_Property|null $property The instance of the 
     *                                              property we're parsing. 
     * @return array 
     */
    static public function parseData($propertyValue, Sabre_VObject_Property $property = null) {

        if (is_null($propertyValue)) {
            return array(null, null);
        }

        $date = '(?P<year>[1-2][0-9]{3})(?P<month>[0-1][0-9])(?P<date>[0-3][0-9])';
        $time = '(?P<hour>[0-2][0-9])(?P<minute>[0-5][0-9])(?P<second>[0-5][0-9])';
        $regex = "/^$date(T$time(?P<isutc>Z)?)?$/";

        if (!preg_match($regex, $propertyValue, $matches)) {
            throw new InvalidArgumentException($propertyValue . ' is not a valid DateTime or Date string');
        }

        if (!isset($matches['hour'])) {
            // Date-only
            return array(
                self::DATE,
                new DateTime($matches['year'] . '-' . $matches['month'] . '-' . $matches['date'] . ' 00:00:00'),
            );
        }

        $dateStr = 
            $matches['year'] .'-' . 
            $matches['month'] . '-' . 
            $matches['date'] . ' ' .
            $matches['hour'] . ':' .
            $matches['minute'] . ':' .
            $matches['second']; 

        if (isset($matches['isutc'])) {
            $dt = new DateTime($dateStr,new DateTimeZone('UTC'));
            $dt->setTimeZone(new DateTimeZone('UTC'));
            return array(
                self::UTC,
                $dt
            );
        }

        // Finding the timezone.
        $tzid = $property['TZID'];
        if (!$tzid) {
            return array(
                self::LOCAL,
                new DateTime($dateStr)
            );
        }

        try {
            $tz = new DateTimeZone($tzid->value);
        } catch (Exception $e) {

            // The id was invalid, we're going to try to find the information 
            // through the VTIMEZONE object.

            // First we find the root object
            $root = $property;
            while($root->parent) {
                $root = $root->parent;
            }

            if (isset($root->VTIMEZONE)) {
                foreach($root->VTIMEZONE as $vtimezone) {
                    if (((string)$vtimezone->TZID) == $tzid) {
                        if (isset($vtimezone->{'X-LIC-LOCATION'})) {
                            $tzid = (string)$vtimezone->{'X-LIC-LOCATION'};
                        }
                    }
                }
            }

            $tz = new DateTimeZone($tzid);
            
        }
        $dt = new DateTime($dateStr, $tz);
        $dt->setTimeZone($tz);

        return array(
            self::LOCALTZ,
            $dt
        );

    }

}

?>
