<?php

/**
 * HTTP utility methods
 *
 * @package Sabre
 * @subpackage HTTP
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @author Paul Voegler
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_HTTP_Util {

    /**
     * Parses a RFC2616-compatible date string
     *
     * This method returns false if the date is invalid
     *
     * @param string $dateHeader
     * @return bool|DateTime
     */
    static function parseHTTPDate($dateHeader) {

        //RFC 2616 section 3.3.1 Full Date
        //Only the format is checked, valid ranges are checked by strtotime below
        $month = '(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)';
        $weekday = '(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)';
        $wkday = '(Mon|Tue|Wed|Thu|Fri|Sat|Sun)';
        $time = '[0-2]\d(\:[0-5]\d){2}';
        $date3 = $month . ' ([1-3]\d| \d)';
        $date2 = '[0-3]\d\-' . $month . '\-\d\d';
        //4-digit year cannot begin with 0 - unix timestamp begins in 1970
        $date1 = '[0-3]\d ' . $month . ' [1-9]\d{3}';

        //ANSI C's asctime() format
        //4-digit year cannot begin with 0 - unix timestamp begins in 1970
        $asctime_date = $wkday . ' ' . $date3 . ' ' . $time . ' [1-9]\d{3}';
        //RFC 850, obsoleted by RFC 1036
        $rfc850_date = $weekday . ', ' . $date2 . ' ' . $time . ' GMT';
        //RFC 822, updated by RFC 1123
        $rfc1123_date = $wkday . ', ' . $date1 . ' ' . $time . ' GMT';
        //allowed date formats by RFC 2616
        $HTTP_date = "($rfc1123_date|$rfc850_date|$asctime_date)";

        //allow for space around the string and strip it
        $dateHeader = trim($dateHeader, ' ');
        if (!preg_match('/^' . $HTTP_date . '$/', $dateHeader))
            return false;

        //append implicit GMT timezone to ANSI C time format
        if (strpos($dateHeader, ' GMT') === false)
            $dateHeader .= ' GMT';


        $realDate = strtotime($dateHeader);
        //strtotime can return -1 or false in case of error
        if ($realDate !== false && $realDate >= 0)
            return new DateTime('@' . $realDate, new DateTimeZone('UTC'));

    }

    /**
     * Transforms a DateTime object to HTTP's most common date format.
     *
     * We're serializing it as the RFC 1123 date, which, for HTTP must be
     * specified as GMT.
     *
     * @param DateTime $dateTime
     * @return string
     */
    static function toHTTPDate(DateTime $dateTime) {

        // We need to clone it, as we don't want to affect the existing
        // DateTime.
        $dateTime = clone $dateTime;
        $dateTime->setTimeZone(new DateTimeZone('GMT'));
        return $dateTime->format('D, d M Y H:i:s \G\M\T');

    }

}
