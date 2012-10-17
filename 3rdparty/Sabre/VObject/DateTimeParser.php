<?php

namespace Sabre\VObject;

/**
 * DateTimeParser
 *
 * This class is responsible for parsing the several different date and time
 * formats iCalendar and vCards have.
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class DateTimeParser {

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
    static public function parseDateTime($dt,\DateTimeZone $tz = null) {

        // Format is YYYYMMDD + "T" + hhmmss
        $result = preg_match('/^([1-4][0-9]{3})([0-1][0-9])([0-3][0-9])T([0-2][0-9])([0-5][0-9])([0-5][0-9])([Z]?)$/',$dt,$matches);

        if (!$result) {
            throw new \LogicException('The supplied iCalendar datetime value is incorrect: ' . $dt);
        }

        if ($matches[7]==='Z' || is_null($tz)) {
            $tz = new \DateTimeZone('UTC');
        }
        $date = new \DateTime($matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' ' . $matches[4] . ':' . $matches[5] .':' . $matches[6], $tz);

        // Still resetting the timezone, to normalize everything to UTC
        $date->setTimeZone(new \DateTimeZone('UTC'));
        return $date;

    }

    /**
     * Parses an iCalendar (rfc5545) formatted date and returns a DateTime object
     *
     * @param string $date
     * @return DateTime
     */
    static public function parseDate($date) {

        // Format is YYYYMMDD
        $result = preg_match('/^([1-4][0-9]{3})([0-1][0-9])([0-3][0-9])$/',$date,$matches);

        if (!$result) {
            throw new \LogicException('The supplied iCalendar date value is incorrect: ' . $date);
        }

        $date = new \DateTime($matches[1] . '-' . $matches[2] . '-' . $matches[3], new \DateTimeZone('UTC'));
        return $date;

    }

    /**
     * Parses an iCalendar (RFC5545) formatted duration value.
     *
     * This method will either return a DateTimeInterval object, or a string
     * suitable for strtotime or DateTime::modify.
     *
     * @param string $duration
     * @param bool $asString
     * @return DateInterval|string
     */
    static public function parseDuration($duration, $asString = false) {

        $result = preg_match('/^(?P<plusminus>\+|-)?P((?P<week>\d+)W)?((?P<day>\d+)D)?(T((?P<hour>\d+)H)?((?P<minute>\d+)M)?((?P<second>\d+)S)?)?$/', $duration, $matches);
        if (!$result) {
            throw new \LogicException('The supplied iCalendar duration value is incorrect: ' . $duration);
        }

        if (!$asString) {
            $invert = false;
            if ($matches['plusminus']==='-') {
                $invert = true;
            }


            $parts = array(
                'week',
                'day',
                'hour',
                'minute',
                'second',
            );
            foreach($parts as $part) {
                $matches[$part] = isset($matches[$part])&&$matches[$part]?(int)$matches[$part]:0;
            }


            // We need to re-construct the $duration string, because weeks and
            // days are not supported by DateInterval in the same string.
            $duration = 'P';
            $days = $matches['day'];
            if ($matches['week']) {
                $days+=$matches['week']*7;
            }
            if ($days)
                $duration.=$days . 'D';

            if ($matches['minute'] || $matches['second'] || $matches['hour']) {
                $duration.='T';

                if ($matches['hour'])
                    $duration.=$matches['hour'].'H';

                if ($matches['minute'])
                    $duration.=$matches['minute'].'M';

                if ($matches['second'])
                    $duration.=$matches['second'].'S';

            }

            if ($duration==='P') {
                $duration = 'PT0S';
            }
            $iv = new \DateInterval($duration);
            if ($invert) $iv->invert = true;

            return $iv;

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
        if ($newDur === '+') { $newDur = '+0 seconds'; };
        return $newDur;

    }

    /**
     * Parses either a Date or DateTime, or Duration value.
     *
     * @param string $date
     * @param DateTimeZone|string $referenceTZ
     * @return DateTime|DateInterval
     */
    static public function parse($date, $referenceTZ = null) {

        if ($date[0]==='P' || ($date[0]==='-' && $date[1]==='P')) {
            return self::parseDuration($date);
        } elseif (strlen($date)===8) {
            return self::parseDate($date);
        } else {
            return self::parseDateTime($date, $referenceTZ);
        }

    }


}
