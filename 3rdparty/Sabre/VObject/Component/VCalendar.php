<?php

namespace Sabre\VObject\Component;

use Sabre\VObject;

/**
 * The VCalendar component
 *
 * This component adds functionality to a component, specific for a VCALENDAR.
 * 
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class VCalendar extends VObject\Component {

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

            if (!$component instanceof VObject\Component)
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
    public function expand(\DateTime $start, \DateTime $end) {

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
                throw new \LogicException('Event did not have a UID!');
            }

            $it = new VObject\RecurrenceIterator($this, $vevent->uid);
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
                if ($child instanceof VObject\Property\DateTime &&
                    $child->getDateType() == VObject\Property\DateTime::LOCALTZ) {
                        $child->setDateTime($child->getDateTime(),VObject\Property\DateTime::UTC);
                    }
            }

            $this->add($newEvent);

        }

        // Removing all VTIMEZONE components
        unset($this->VTIMEZONE);

    } 

    /**
     * Validates the node for correctness.
     * An array is returned with warnings.
     *
     * Every item in the array has the following properties:
     *    * level - (number between 1 and 3 with severity information)
     *    * message - (human readable message)
     *    * node - (reference to the offending node)
     * 
     * @return array 
     */
    /*
    public function validate() {

        $warnings = array();

        $version = $this->select('VERSION');
        if (count($version)!==1) {
            $warnings[] = array(
                'level' => 1,
                'message' => 'The VERSION property must appear in the VCALENDAR component exactly 1 time',
                'node' => $this,
            );
        } else {
            if ((string)$this->VERSION !== '2.0') {
                $warnings[] = array(
                    'level' => 1,
                    'message' => 'Only iCalendar version 2.0 as defined in rfc5545 is supported.',
                    'node' => $this,
                );
            }
        } 
        $version = $this->select('PRODID');
        if (count($version)!==1) {
            $warnings[] = array(
                'level' => 2,
                'message' => 'The PRODID property must appear in the VCALENDAR component exactly 1 time',
                'node' => $this,
            );
        }
        if (count($this->CALSCALE) > 1) {
            $warnings[] = array(
                'level' => 2,
                'message' => 'The CALSCALE property must not be specified more than once.',
                'node' => $this,
            );
        }
        if (count($this->METHOD) > 1) {
            $warnings[] = array(
                'level' => 2,
                'message' => 'The METHOD property must not be specified more than once.',
                'node' => $this,
            );
        }

        $allowedComponents = array(
            'VEVENT',
            'VTODO',
            'VJOURNAL',
            'VFREEBUSY',
            'VTIMEZONE',
        );
        $allowedProperties = array(
            'PRODID',
            'VERSION',
            'CALSCALE',
            'METHOD',
        );
        $componentsFound = 0;
        foreach($this->children as $child) {
            if($child instanceof Component) {
                $componentsFound++;
                if (!in_array($child->name, $allowedComponents)) {
                    $warnings[] = array(
                        'level' => 1,
                        'message' => 'The ' . $child->name . " component is not allowed in the VCALENDAR component",
                        'node' => $this,
                    );
                }
            }
            if ($child instanceof Property) {
                if (!in_array($child->name, $allowedProperties)) {
                    $warnings[] = array(
                        'level' => 2,
                        'message' => 'The ' . $child->name . " property is not allowed in the VCALENDAR component",
                        'node' => $this,
                    );
                }
            }
        }

        if ($componentsFound===0) {
            $warnings[] = array(
                'level' => 1,
                'message' => 'An iCalendar object must have at least 1 component.',
                'node' => $this,
            );
        }

        return array_merge(
            $warnings,
            parent::validate()
        );

    }
     */

}

