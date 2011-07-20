<?php

/**
 * String utility
 *
 * This class is mainly used to implement the 'text-match' filter, used by both 
 * the CalDAV calendar-query REPORT, and CardDAV addressbook-query REPORT. 
 * Because they both need it, it was decided to put it in Sabre_DAV instead.
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_StringUtil {

    /**
     * Checks if a needle occurs in a haystack ;)
     *
     * @param string $haystack 
     * @param string $needle 
     * @param string $collation 
     * @param string $matchType 
     * @return bool 
     */
    static public function textMatch($haystack, $needle, $collation, $matchType = 'contains') {

        switch($collation) {

            case 'i;ascii-casemap' :
                // default strtolower takes locale into consideration
                // we don't want this.
                $haystack = str_replace(range('a','z'), range('A','Z'), $haystack);
                $needle = str_replace(range('a','z'), range('A','Z'), $needle);
                break;

            case 'i;octet' :
                // Do nothing
                break; 

            case 'i;unicode-casemap' :
                $haystack = mb_strtoupper($haystack, 'UTF-8');
                $needle = mb_strtoupper($needle, 'UTF-8');
                break;

            default :
                throw new Sabre_DAV_Exception_BadRequest('Collation type: ' . $collation . ' is not supported');

        }

        switch($matchType) {

            case 'contains' :
                return strpos($haystack, $needle)!==false;
            case 'equals' :
                return $haystack === $needle;
            case 'starts-with' :
                return strpos($haystack, $needle)===0;
            case 'ends-with' :
                return strrpos($haystack, $needle)===strlen($haystack)-strlen($needle);
            default :
                throw new Sabre_DAV_Exception_BadRequest('Match-type: ' . $matchType . ' is not supported');

        }
        

    }

}
