<?php

/**
 * The VCalendar component
 *
 * This component adds functionality to a component, specific for a VCALENDAR.
 * 
 * @package Sabre
 * @subpackage VObject
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_VObject_Component_VCalendar extends Sabre_VObject_Component {

    /**
     * Returns a list of all 'base components'. For instance, if an Event has 
     * a recurrence rule, and one instance is overridden, the overridden event 
     * will have the same UID, but will be excluded from this list.
     *
     * VTIMEZONE components will always be excluded. 
     *
     * @param string $componentName filter by component name 
     * @return array 
     */
    public function getBaseComponents($componentName = null) {

        $components = array();
        foreach($this->children as $component) {

            if (!$component instanceof Sabre_VObject_Component)
                continue;

            if (isset($component->{'RECURRENCE-ID'})) 
                continue;

            if ($componentName && $component->name !== strtoupper($componentName)) 
                continue;

            if ($component->name === 'VTIMEZONE')
                continue;

            $components[] = $component;

        }

        return $components;

    }

    /**
     * If this calendar object, has events with recurrence rules, this method 
     * can be used to expand the event into multiple sub-events.
     *
     * Each event will be stripped from it's recurrence information, and only 
     * the instances of the event in the specified timerange will be left 
     * alone.
     *
     * In addition, this method will cause timezone information to be stripped, 
     * and normalized to UTC.
     *
     * This method will alter the VCalendar. This cannot be reversed.
     *
     * This functionality is specifically used by the CalDAV standard. It is 
     * possible for clients to request expand events, if they are rather simple 
     * clients and do not have the possibility to calculate recurrences.
     *
     * @param DateTime $start
     * @param DateTime $end 
     * @return void
     */
    public function expand(DateTime $start, DateTime $end) {

        $newEvents = array();

        foreach($this->select('VEVENT') as $key=>$vevent) {

            if (isset($vevent->{'RECURRENCE-ID'})) {
                unset($this->children[$key]);
                continue;
            } 


            if (!$vevent->rrule) {
                unset($this->children[$key]);
                if ($vevent->isInTimeRange($start, $end)) {
                    $newEvents[] = $vevent;
                }
                continue;
            }

            $uid = (string)$vevent->uid;
            if (!$uid) {
                throw new LogicException('Event did not have a UID!');
            }

            $it = new Sabre_VObject_RecurrenceIterator($this, $vevent->uid);
            $it->fastForward($start);

            while($it->valid() && $it->getDTStart() < $end) {

                if ($it->getDTEnd() > $start) {

                    $newEvents[] = $it->getEventObject();

                }
                $it->next();

            }
            unset($this->children[$key]);

        }

        foreach($newEvents as $newEvent) {

            foreach($newEvent->children as $child) {
                if ($child instanceof Sabre_VObject_Property_DateTime &&
                    $child->getDateType() == Sabre_VObject_Property_DateTime::LOCALTZ) {
                        $child->setDateTime($child->getDateTime(),Sabre_VObject_Property_DateTime::UTC);
                    }
            }

            $this->add($newEvent);

        }

        // Removing all VTIMEZONE components
        unset($this->VTIMEZONE);

    } 

}

