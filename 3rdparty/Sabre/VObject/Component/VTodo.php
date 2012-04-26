<?php

/**
 * VTodo component
 *
 * This component contains some additional functionality specific for VTODOs.
 * 
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_Component_VTodo extends Sabre_VObject_Component {

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
        $duration = isset($this->DURATION)?Sabre_VObject_DateTimeParser::parseDuration($this->DURATION):null;
        $due = isset($this->DUE)?$this->DUE->getDateTime():null;
        $completed = isset($this->COMPLETED)?$this->COMPLETED->getDateTime():null;
        $created = isset($this->CREATED)?$this->CREATED->getDateTime():null;

        if ($dtstart) {
            if ($duration) {
                $effectiveEnd = clone $dtstart;
                $effectiveEnd->add($duration);
                return $start <= $effectiveEnd && $end > $dtstart;
            } elseif ($due) {
                return
                    ($start < $due || $start <= $dtstart) &&
                    ($end > $dtstart || $end >= $due);
            } else {
                return $start <= $dtstart && $end > $dtstart;
            }
        }
        if ($due) {
            return ($start < $due && $end >= $due);
        }
        if ($completed && $created) {
            return
                ($start <= $created || $start <= $completed) &&
                ($end >= $created || $end >= $completed);
        }
        if ($completed) {
            return ($start <= $completed && $end >= $completed);
        }
        if ($created) {
            return ($end > $created);
        }
        return true;

    }

}

?>
