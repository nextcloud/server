<?php

/**
 * XML utilities for CalDAV 
 *
 * This class contains a few static methods used for parsing certain CalDAV 
 * requests.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_XMLUtil {

    /**
     * This function parses the calendar-query report request body
     *
     * The body is quite complicated, so we're turning it into a PHP
     * array.
     *
     * The resulting associative array has xpath expressions as keys.
     * By default the xpath expressions should simply be checked for existance
     * The xpath expressions can point to elements or attributes.
     * 
     * The array values can contain a number of items, which alters the query
     * filter. 
     *
     * * time-range. Must also check if the todo or event falls within the
     *               specified timerange. How this is interpreted depends on
     *               the type of object (VTODO, VEVENT, VJOURNAL, etc)
     * * is-not-defined
     *               Instead of checking if the attribute or element exist,
     *               we must check if it doesn't.
     * * text-match
     *               Checks if the value of the attribute or element matches
     *               the specified value. This is actually another array with
     *               the 'collation', 'value' and 'negate-condition' items.
     *
     * Refer to the CalDAV spec for more information.
     * 
     * @param DOMNode $domNode 
     * @param string $basePath used for recursive calls.
     * @param array $filters used for recursive calls.
     * @return array 
     */
    static public function parseCalendarQueryFilters($domNode,$basePath = '/c:iCalendar', &$filters = array()) {

        foreach($domNode->childNodes as $child) {

            switch(Sabre_DAV_XMLUtil::toClarkNotation($child)) {

                case '{urn:ietf:params:xml:ns:caldav}comp-filter' :
                case '{urn:ietf:params:xml:ns:caldav}prop-filter' :
                   
                    $filterName = $basePath . '/' . 'c:' . strtolower($child->getAttribute('name'));
                    $filters[$filterName] = array(); 

                    self::parseCalendarQueryFilters($child, $filterName,$filters);
                    break;

                case '{urn:ietf:params:xml:ns:caldav}time-range' :
               
                    if ($start = $child->getAttribute('start')) {
                        $start = self::parseICalendarDateTime($start);
                    } else {
                        $start = null;
                    }
                    if ($end = $child->getAttribute('end')) {
                        $end = self::parseICalendarDateTime($end);
                    } else {
                        $end = null;
                    }

                    if (!is_null($start) && !is_null($end) && $end <= $start) {
                        throw new Sabre_DAV_Exception_BadRequest('The end-date must be larger than the start-date in the time-range filter');
                    }

                    $filters[$basePath]['time-range'] = array(
                        'start' => $start,
                        'end'   => $end
                    );
                    break;

                case '{urn:ietf:params:xml:ns:caldav}is-not-defined' :
                    $filters[$basePath]['is-not-defined'] = true;
                    break;

                case '{urn:ietf:params:xml:ns:caldav}param-filter' :
               
                    $filterName = $basePath . '/@' . strtolower($child->getAttribute('name'));
                    $filters[$filterName] = array();
                    self::parseCalendarQueryFilters($child, $filterName, $filters);
                    break;

                case '{urn:ietf:params:xml:ns:caldav}text-match' :
               
                    $collation = $child->getAttribute('collation');
                    if (!$collation) $collation = 'i;ascii-casemap';

                    $filters[$basePath]['text-match'] = array(
                        'collation' => ($collation == 'default'?'i;ascii-casemap':$collation),
                        'negate-condition' => $child->getAttribute('negate-condition')==='yes',
                        'value' => $child->nodeValue,
                    );
                    break;

            }

        }

        return $filters;

    }

    /**
     * Parses an iCalendar (rfc5545) formatted datetime and returns a DateTime object
     *
     * Specifying a reference timezone is optional. It will only be used
     * if the non-UTC format is used. The argument is used as a reference, the 
     * returned DateTime object will still be in the UTC timezone.
     *
     * @param string $dt 
     * @param DateTimeZone $tz 
     * @return DateTime 
     */
    static public function parseICalendarDateTime($dt,DateTimeZone $tz = null) {

        // Format is YYYYMMDD + "T" + hhmmss 
        $result = preg_match('/^([1-3][0-9]{3})([0-1][0-9])([0-3][0-9])T([0-2][0-9])([0-5][0-9])([0-5][0-9])([Z]?)$/',$dt,$matches);

        if (!$result) {
            throw new Sabre_DAV_Exception_BadRequest('The supplied iCalendar datetime value is incorrect: ' . $dt);
        }

        if ($matches[7]==='Z' || is_null($tz)) {
            $tz = new DateTimeZone('UTC');
        } 
        $date = new DateTime($matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' ' . $matches[4] . ':' . $matches[5] .':' . $matches[6], $tz);

        // Still resetting the timezone, to normalize everything to UTC
        $date->setTimeZone(new DateTimeZone('UTC'));
        return $date;

    }

    /**
     * Parses an iCalendar (rfc5545) formatted datetime and returns a DateTime object
     *
     * @param string $date 
     * @param DateTimeZone $tz 
     * @return DateTime 
     */
    static public function parseICalendarDate($date) {

        // Format is YYYYMMDD
        $result = preg_match('/^([1-3][0-9]{3})([0-1][0-9])([0-3][0-9])$/',$date,$matches);

        if (!$result) {
            throw new Sabre_DAV_Exception_BadRequest('The supplied iCalendar date value is incorrect: ' . $date);
        }

        $date = new DateTime($matches[1] . '-' . $matches[2] . '-' . $matches[3], new DateTimeZone('UTC'));
        return $date;

    }
   
    /**
     * Parses an iCalendar (RFC5545) formatted duration and returns a string suitable
     * for strtotime or DateTime::modify.
     *
     * NOTE: When we require PHP 5.3 this can be replaced by the DateTimeInterval object, which
     * supports ISO 8601 Intervals, which is a superset of ICalendar durations.
     *
     * For now though, we're just gonna live with this messy system
     *
     * @param string $duration
     * @return string
     */
    static public function parseICalendarDuration($duration) {

        $result = preg_match('/^(?P<plusminus>\+|-)?P((?P<week>\d+)W)?((?P<day>\d+)D)?(T((?P<hour>\d+)H)?((?P<minute>\d+)M)?((?P<second>\d+)S)?)?$/', $duration, $matches);
        if (!$result) {
            throw new Sabre_DAV_Exception_BadRequest('The supplied iCalendar duration value is incorrect: ' . $duration);
        }
       
        $parts = array(
            'week',
            'day',
            'hour',
            'minute',
            'second',
        );

        $newDur = '';
        foreach($parts as $part) {
            if (isset($matches[$part]) && $matches[$part]) {
                $newDur.=' '.$matches[$part] . ' ' . $part . 's';
            }
        }

        $newDur = ($matches['plusminus']==='-'?'-':'+') . trim($newDur);
        return $newDur;

    }

}
