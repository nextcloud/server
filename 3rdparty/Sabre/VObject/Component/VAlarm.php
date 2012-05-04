<?php

/**
 * VAlarm component
 *
 * This component contains some additional functionality specific for VALARMs.
 *
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_Component_VAlarm extends Sabre_VObject_Component {

    /**
     * Returns a DateTime object when this alarm is going to trigger.
     *
     * This ignores repeated alarm, only the first trigger is returned.
     *
     * @return DateTime
     */
    public function getEffectiveTriggerTime() {

        $trigger = $this->TRIGGER;
        if(!isset($trigger['VALUE']) || strtoupper($trigger['VALUE']) === 'DURATION') {
            $triggerDuration = Sabre_VObject_DateTimeParser::parseDuration($this->TRIGGER);
            $related = (isset($trigger['RELATED']) && strtoupper($trigger['RELATED']) == 'END') ? 'END' : 'START';

            $parentComponent = $this->parent;
            if ($related === 'START') {
                $effectiveTrigger = clone $parentComponent->DTSTART->getDateTime();
                $effectiveTrigger->add($triggerDuration);
            } else {
                if ($parentComponent->name === 'VTODO') {
                    $endProp = 'DUE';
                } elseif ($parentComponent->name === 'VEVENT') {
                    $endProp = 'DTEND';
                } else {
                    throw new Sabre_DAV_Exception('time-range filters on VALARM components are only supported when they are a child of VTODO or VEVENT');
                }

                if (isset($parentComponent->$endProp)) {
                    $effectiveTrigger = clone $parentComponent->$endProp->getDateTime();
                    $effectiveTrigger->add($triggerDuration);
                } elseif (isset($parentComponent->DURATION)) {
                    $effectiveTrigger = clone $parentComponent->DTSTART->getDateTime();
                    $duration = Sabre_VObject_DateTimeParser::parseDuration($parentComponent->DURATION);
                    $effectiveTrigger->add($duration);
                    $effectiveTrigger->add($triggerDuration);
                } else {
                    $effectiveTrigger = clone $parentComponent->DTSTART->getDateTime();
                    $effectiveTrigger->add($triggerDuration);
                }
            }
        } else {
            $effectiveTrigger = $trigger->getDateTime();
        }
        return $effectiveTrigger;

    }

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

        $effectiveTrigger = $this->getEffectiveTriggerTime();

        if (isset($this->DURATION)) {
            $duration = Sabre_VObject_DateTimeParser::parseDuration($this->DURATION);
            $repeat = (string)$this->repeat;
            if (!$repeat) {
                $repeat = 1;
            }

            $period = new DatePeriod($effectiveTrigger, $duration, (int)$repeat);

            foreach($period as $occurrence) {

                if ($start <= $occurrence && $end > $occurrence) {
                    return true;
                }
            }
            return false;
        } else {
            return ($start <= $effectiveTrigger && $end > $effectiveTrigger);
        }

    }

}

?>
