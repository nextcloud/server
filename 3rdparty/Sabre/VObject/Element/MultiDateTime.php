<?php

/**
 * Multi-DateTime property 
 *
 * This element is used for iCalendar properties such as the EXDATE property. 
 * It basically provides a few helper functions that make it easier to deal 
 * with these. It supports both DATE-TIME and DATE values.
 *
 * In order to use this correctly, you must call setDateTimes and getDateTimes 
 * to retrieve and modify dates respectively.
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
class Sabre_VObject_Element_MultiDateTime extends Sabre_VObject_Property {

    /**
     * DateTime representation
     *
     * @var DateTime[]
     */
    protected $dateTimes;

    /**
     * dateType
     *
     * This is one of the Sabre_VObject_Element_DateTime constants.
     *
     * @var int 
     */
    protected $dateType;

    /**
     * Updates the value 
     * 
     * @param array $dt Must be an array of DateTime objects. 
     * @param int $dateType 
     * @return void
     */
    public function setDateTimes(array $dt, $dateType = Sabre_VObject_Element_DateTime::LOCALTZ) {

        foreach($dt as $i) 
            if (!$i instanceof DateTime) 
                throw new InvalidArgumentException('You must pass an array of DateTime objects');

        $this->offsetUnset('VALUE');
        $this->offsetUnset('TZID');
        switch($dateType) {

            case Sabre_VObject_Element_DateTime::LOCAL :
                $val = array();
                foreach($dt as $i) {
                    $val[] = $i->format('Ymd\\THis');
                }
                $this->setValue(implode(',',$val));
                $this->offsetSet('VALUE','DATETIME'); 
                break;
            case Sabre_VObject_Element_DateTime::UTC :
                $val = array();
                foreach($dt as $i) {
                    $i->setTimeZone(new DateTimeZone('UTC'));
                    $val[] = $i->format('Ymd\\THis\\Z');
                }
                $this->setValue(implode(',',$val));
                $this->offsetSet('VALUE','DATETIME');
                break;
            case Sabre_VObject_Element_DateTime::LOCALTZ :
                $val = array();
                foreach($dt as $i) {
                    $val[] = $i->format('Ymd\\THis');
                }
                $this->setValue(implode(',',$val));
                $this->offsetSet('VALUE','DATETIME');
                $this->offsetSet('TZID', $dt[0]->getTimeZone()->getName());
                break; 
            case Sabre_VObject_Element_DateTime::DATE :
                $val = array();
                foreach($dt as $i) {
                    $val[] = $i->format('Ymd');
                }
                $this->setValue(implode(',',$val));
                $this->offsetSet('VALUE','DATE');
                break;
            default :
                throw new InvalidArgumentException('You must pass a valid dateType constant');

        }
        $this->dateTimes = $dt;
        $this->dateType = $dateType;

    }

    /**
     * Returns the current DateTime value.
     *
     * If no value was set, this method returns null.
     *
     * @return array|null 
     */
    public function getDateTimes() {

        if ($this->dateTimes)
            return $this->dateTimes;

        $dts = array();
    
        if (!$this->value) {
            $this->dateTimes = null;
            $this->dateType = null;
            return null;
        }

        foreach(explode(',',$this->value) as $val) {
            list(
                $type,
                $dt
            ) = Sabre_VObject_Element_DateTime::parseData($val, $this->offsetGet('TZID'));
            $dts[] = $dt;
            $this->dateType = $type;
        }
        $this->dateTimes = $dts;
        return $this->dateTimes;

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
    
        if (!$this->value) {
            $this->dateTimes = null;
            $this->dateType = null;
            return null;
        }

        $dts = array();
        foreach(explode(',',$this->value) as $val) {
            list(
                $type,
                $dt
            ) = Sabre_VObject_Element_DateTime::parseData($val, $this->offsetGet('TZID'));
            $dts[] = $dt;
            $this->dateType = $type; 
        }
        $this->dateTimes = $dts;
        return $this->dateType;

    }

}

?>
