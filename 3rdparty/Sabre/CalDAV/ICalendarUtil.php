<?php

/**
 * This class contains several utilities related to the ICalendar (rfc2445) format
 *
 * This class is now deprecated, and won't be further maintained. Please use 
 * the Sabre_VObject package for your ics parsing needs.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 * @deprecated Use Sabre_VObject instead.
 */
class Sabre_CalDAV_ICalendarUtil {

    /**
     * Validates an ICalendar object
     *
     * This method makes sure this ICalendar object is properly formatted.
     * If we can't parse it, we'll throw exceptions.
     *
     * @param string $icalData 
     * @param array $allowedComponents 
     * @return bool 
     */
    static function validateICalendarObject($icalData, array $allowedComponents = null) {

        $xcal = simplexml_load_string(self::toXCal($icalData));
        if (!$xcal) throw new Sabre_CalDAV_Exception_InvalidICalendarObject('Invalid calendarobject format');

        $xcal->registerXPathNameSpace('cal','urn:ietf:params:xml:ns:xcal');
        
        // Check if there's only 1 component
        $components = array('vevent','vtodo','vjournal','vfreebusy');
        $componentsFound = array();

        foreach($components as $component) {
            $test = $xcal->xpath('/cal:iCalendar/cal:vcalendar/cal:' . $component);
            if (is_array($test)) $componentsFound = array_merge($componentsFound, $test);
        }
        if (count($componentsFound)<1) {
            throw new Sabre_CalDAV_Exception_InvalidICalendarObject('One VEVENT, VTODO, VJOURNAL or VFREEBUSY must be specified. 0 found.');
        }
        $component = $componentsFound[0];

        if (is_null($allowedComponents)) return true;

        // Check if the component is allowed
        $name = $component->getName();
        if (!in_array(strtoupper($name),$allowedComponents)) {
            throw new Sabre_CalDAV_Exception_InvalidICalendarObject(strtoupper($name) . ' is not allowed in this calendar.');
        }

        if (count($xcal->xpath('/cal:iCalendar/cal:vcalendar/cal:method'))>0) {
            throw new Sabre_CalDAV_Exception_InvalidICalendarObject('The METHOD property is not allowed in calendar objects');
        }

        return true;

    }

    /**
     * Converts ICalendar data to XML.
     *
     * Properties are converted to lowercase xml elements. Parameters are;
     * converted to attributes. BEGIN:VEVENT is converted to <vevent> and
     * END:VEVENT </vevent> as well as other components.
     *
     * It's a very loose parser. If any line does not conform to the spec, it
     * will simply be ignored. It will try to detect if \r\n or \n line endings
     * are used.
     *
     * @todo Currently quoted attributes are not parsed correctly.
     * @see http://tools.ietf.org/html/draft-royer-calsch-xcal-03
     * @param string $icalData 
     * @return string. 
     */
    static function toXCAL($icalData) {

        // Detecting line endings
        $lb="\r\n";
        if (strpos($icalData,"\r\n")!==false) $lb = "\r\n";
        elseif (strpos($icalData,"\n")!==false) $lb = "\n";

        // Splitting up items per line
        $lines = explode($lb,$icalData);

        // Properties can be folded over 2 lines. In this case the second
        // line will be preceeded by a space or tab.
        $lines2 = array();
        foreach($lines as $line) {

            if (!$line) continue;
            if ($line[0]===" " || $line[0]==="\t") {
                $lines2[count($lines2)-1].=substr($line,1);
                continue;
            }

            $lines2[]=$line;

        }

        $xml = '<?xml version="1.0"?>' . "\n";
        $xml.= "<iCalendar xmlns=\"urn:ietf:params:xml:ns:xcal\">\n";

        $spaces = 2;
        foreach($lines2 as $line) {

            $matches = array();
            // This matches PROPERTYNAME;ATTRIBUTES:VALUE
            if (!preg_match('/^([^:^;]*)(?:;([^:]*))?:(.*)$/',$line,$matches))
                continue;

            $propertyName = strtolower($matches[1]);
            $attributes = $matches[2];
            $value = $matches[3];

            // If the line was in the format BEGIN:COMPONENT or END:COMPONENT, we need to special case it.
            if ($propertyName === 'begin') {
                $xml.=str_repeat(" ",$spaces);
                $xml.='<' . strtolower($value) . ">\n";
                $spaces+=2;
                continue;
            } elseif ($propertyName === 'end') {
                $spaces-=2;
                $xml.=str_repeat(" ",$spaces);
                $xml.='</' . strtolower($value) . ">\n";
                continue;
            }

            $xml.=str_repeat(" ",$spaces);
            $xml.='<' . $propertyName;
            if ($attributes) {
                // There can be multiple attributes
                $attributes = explode(';',$attributes);
                foreach($attributes as $att) {
  
                    list($attName,$attValue) = explode('=',$att,2);
                    $attName = strtolower($attName);
                    if ($attName === 'language') $attName='xml:lang';
                    $xml.=' ' . $attName . '="' . htmlspecialchars($attValue) . '"';

                }
            }

            $xml.='>'. htmlspecialchars(trim($value)) . '</' . $propertyName . ">\n";
          
        }
        $xml.="</iCalendar>";
        return $xml;

    }

}

