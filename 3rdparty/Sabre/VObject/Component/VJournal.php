<?php

/**
 * VJournal component
 *
 * This component contains some additional functionality specific for VJOURNALs.
 * 
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_Component_VJournal extends Sabre_VObject_Component {

    /**
     * Returns true or false depending on if the event falls in the specified 
     * time-range. This is used for filtering purposes. 
     *
     * The rules used to determine if an event falls within the specified 
     * time-range is based on the CalDAV specification.
     *
     * @param DateTime $start
     * @param DateTime $end 
     * @return bool 
     */
    public function isInTimeRange(DateTime $start, DateTime $end) {

        $dtstart = isset($this->DTSTART)?$this->DTSTART->getDateTime():null;
        if ($dtstart) {
            $effectiveEnd = clone $dtstart;
            if ($this->DTSTART->getDateType() == Sabre_VObject_Element_DateTime::DATE) {
                $effectiveEnd->modify('+1 day');
            }

            return ($start <= $effectiveEnd && $end > $dtstart);

        }
        return false;


    }

}

?>
