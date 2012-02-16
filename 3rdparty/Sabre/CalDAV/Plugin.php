<?php

/**
 * CalDAV plugin
 *
 * This plugin provides functionality added by CalDAV (RFC 4791)
 * It implements new reports, and the MKCALENDAR method.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * This is the official CalDAV namespace
     */
    const NS_CALDAV = 'urn:ietf:params:xml:ns:caldav';
    
    /**
     * This is the namespace for the proprietary calendarserver extensions
     */
    const NS_CALENDARSERVER = 'http://calendarserver.org/ns/';

    /**
     * The following constants are used to differentiate
     * the various filters for the calendar-query report
     */
    const FILTER_COMPFILTER   = 1;
    const FILTER_TIMERANGE    = 3;
    const FILTER_PROPFILTER   = 4;
    const FILTER_PARAMFILTER  = 5;
    const FILTER_TEXTMATCH    = 6;

    /**
     * The hardcoded root for calendar objects. It is unfortunate
     * that we're stuck with it, but it will have to do for now
     */
    const CALENDAR_ROOT = 'calendars';

    /**
     * Reference to server object 
     * 
     * @var Sabre_DAV_Server 
     */
    private $server;

    /**
     * Use this method to tell the server this plugin defines additional
     * HTTP methods.
     *
     * This method is passed a uri. It should only return HTTP methods that are 
     * available for the specified uri.
     *
     * @param string $uri
     * @return array 
     */
    public function getHTTPMethods($uri) {

        // The MKCALENDAR is only available on unmapped uri's, whose
        // parents extend IExtendedCollection
        list($parent, $name) = Sabre_DAV_URLUtil::splitPath($uri);

        $node = $this->server->tree->getNodeForPath($parent);

        if ($node instanceof Sabre_DAV_IExtendedCollection) {
            try {
                $node->getChild($name);
            } catch (Sabre_DAV_Exception_FileNotFound $e) {
                return array('MKCALENDAR');
            }
        }
        return array();

    }

    /**
     * Returns a list of features for the DAV: HTTP header. 
     * 
     * @return array 
     */
    public function getFeatures() {

        return array('calendar-access', 'calendar-proxy');

    }

    /**
     * Returns a plugin name.
     * 
     * Using this name other plugins will be able to access other plugins
     * using Sabre_DAV_Server::getPlugin 
     * 
     * @return string 
     */
    public function getPluginName() {

        return 'caldav';

    }

    /**
     * Returns a list of reports this plugin supports.
     *
     * This will be used in the {DAV:}supported-report-set property.
     * Note that you still need to subscribe to the 'report' event to actually 
     * implement them 
     * 
     * @param string $uri
     * @return array 
     */
    public function getSupportedReportSet($uri) {

        $node = $this->server->tree->getNodeForPath($uri);
        if ($node instanceof Sabre_CalDAV_ICalendar || $node instanceof Sabre_CalDAV_ICalendarObject) {
            return array(
                 '{' . self::NS_CALDAV . '}calendar-multiget',
                 '{' . self::NS_CALDAV . '}calendar-query',
            );
        }
        return array();

    }

    /**
     * Initializes the plugin 
     * 
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $server->subscribeEvent('unknownMethod',array($this,'unknownMethod'));
        //$server->subscribeEvent('unknownMethod',array($this,'unknownMethod2'),1000);
        $server->subscribeEvent('report',array($this,'report'));
        $server->subscribeEvent('beforeGetProperties',array($this,'beforeGetProperties'));

        $server->xmlNamespaces[self::NS_CALDAV] = 'cal';
        $server->xmlNamespaces[self::NS_CALENDARSERVER] = 'cs';

        $server->propertyMap['{' . self::NS_CALDAV . '}supported-calendar-component-set'] = 'Sabre_CalDAV_Property_SupportedCalendarComponentSet';

        $server->resourceTypeMapping['Sabre_CalDAV_ICalendar'] = '{urn:ietf:params:xml:ns:caldav}calendar';
        $server->resourceTypeMapping['Sabre_CalDAV_Principal_ProxyRead'] = '{http://calendarserver.org/ns/}calendar-proxy-read';
        $server->resourceTypeMapping['Sabre_CalDAV_Principal_ProxyWrite'] = '{http://calendarserver.org/ns/}calendar-proxy-write';

        array_push($server->protectedProperties,

            '{' . self::NS_CALDAV . '}supported-calendar-component-set',
            '{' . self::NS_CALDAV . '}supported-calendar-data',
            '{' . self::NS_CALDAV . '}max-resource-size',
            '{' . self::NS_CALDAV . '}min-date-time',
            '{' . self::NS_CALDAV . '}max-date-time',
            '{' . self::NS_CALDAV . '}max-instances',
            '{' . self::NS_CALDAV . '}max-attendees-per-instance',
            '{' . self::NS_CALDAV . '}calendar-home-set',
            '{' . self::NS_CALDAV . '}supported-collation-set',
            '{' . self::NS_CALDAV . '}calendar-data',

            // scheduling extension
            '{' . self::NS_CALDAV . '}calendar-user-address-set',

            // CalendarServer extensions
            '{' . self::NS_CALENDARSERVER . '}getctag',
            '{' . self::NS_CALENDARSERVER . '}calendar-proxy-read-for',
            '{' . self::NS_CALENDARSERVER . '}calendar-proxy-write-for'

        );
    }

    /**
     * This function handles support for the MKCALENDAR method
     * 
     * @param string $method 
     * @return bool 
     */
    public function unknownMethod($method, $uri) {

        if ($method!=='MKCALENDAR') return;

        $this->httpMkCalendar($uri);
        // false is returned to stop the unknownMethod event
        return false;

    }

    /**
     * This functions handles REPORT requests specific to CalDAV 
     * 
     * @param string $reportName 
     * @param DOMNode $dom 
     * @return bool 
     */
    public function report($reportName,$dom) {

        switch($reportName) { 
            case '{'.self::NS_CALDAV.'}calendar-multiget' :
                $this->calendarMultiGetReport($dom);
                return false;
            case '{'.self::NS_CALDAV.'}calendar-query' :
                $this->calendarQueryReport($dom);
                return false;

        }


    }

    /**
     * This function handles the MKCALENDAR HTTP method, which creates
     * a new calendar.
     * 
     * @param string $uri
     * @return void 
     */
    public function httpMkCalendar($uri) {

        // Due to unforgivable bugs in iCal, we're completely disabling MKCALENDAR support
        // for clients matching iCal in the user agent
        //$ua = $this->server->httpRequest->getHeader('User-Agent');
        //if (strpos($ua,'iCal/')!==false) {
        //    throw new Sabre_DAV_Exception_Forbidden('iCal has major bugs in it\'s RFC3744 support. Therefore we are left with no other choice but disabling this feature.');
        //}

        $body = $this->server->httpRequest->getBody(true);
        $properties = array();

        if ($body) {

            $dom = Sabre_DAV_XMLUtil::loadDOMDocument($body);

            foreach($dom->firstChild->childNodes as $child) {

                if (Sabre_DAV_XMLUtil::toClarkNotation($child)!=='{DAV:}set') continue;
                foreach(Sabre_DAV_XMLUtil::parseProperties($child,$this->server->propertyMap) as $k=>$prop) {
                    $properties[$k] = $prop;
                }
            
            }
        }

        $resourceType = array('{DAV:}collection','{urn:ietf:params:xml:ns:caldav}calendar');

        $this->server->createCollection($uri,$resourceType,$properties);

        $this->server->httpResponse->sendStatus(201);
        $this->server->httpResponse->setHeader('Content-Length',0);
    }

    /**
     * beforeGetProperties
     *
     * This method handler is invoked before any after properties for a
     * resource are fetched. This allows us to add in any CalDAV specific 
     * properties. 
     * 
     * @param string $path
     * @param Sabre_DAV_INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @return void
     */
    public function beforeGetProperties($path, Sabre_DAV_INode $node, &$requestedProperties, &$returnedProperties) {

        if ($node instanceof Sabre_DAVACL_IPrincipal) {

            // calendar-home-set property
            $calHome = '{' . self::NS_CALDAV . '}calendar-home-set';
            if (in_array($calHome,$requestedProperties)) {
                $principalId = $node->getName(); 
                $calendarHomePath = self::CALENDAR_ROOT . '/' . $principalId . '/';
                unset($requestedProperties[$calHome]);
                $returnedProperties[200][$calHome] = new Sabre_DAV_Property_Href($calendarHomePath);
            }

            // calendar-user-address-set property
            $calProp = '{' . self::NS_CALDAV . '}calendar-user-address-set';
            if (in_array($calProp,$requestedProperties)) {

                $addresses = $node->getAlternateUriSet();
                $addresses[] = $this->server->getBaseUri() . $node->getPrincipalUrl();
                unset($requestedProperties[$calProp]);
                $returnedProperties[200][$calProp] = new Sabre_DAV_Property_HrefList($addresses, false);

            }

            // These two properties are shortcuts for ical to easily find 
            // other principals this principal has access to.
            $propRead = '{' . self::NS_CALENDARSERVER . '}calendar-proxy-read-for';
            $propWrite = '{' . self::NS_CALENDARSERVER . '}calendar-proxy-write-for';
            if (in_array($propRead,$requestedProperties) || in_array($propWrite,$requestedProperties)) {

                $membership = $node->getGroupMembership();
                $readList = array();
                $writeList = array();

                foreach($membership as $group) {

                    $groupNode = $this->server->tree->getNodeForPath($group);

                    // If the node is either ap proxy-read or proxy-write 
                    // group, we grab the parent principal and add it to the 
                    // list.
                    if ($groupNode instanceof Sabre_CalDAV_Principal_ProxyRead) {
                        list($readList[]) = Sabre_DAV_URLUtil::splitPath($group);
                    }
                    if ($groupNode instanceof Sabre_CalDAV_Principal_ProxyWrite) {
                        list($writeList[]) = Sabre_DAV_URLUtil::splitPath($group);
                    }

                }
                if (in_array($propRead,$requestedProperties)) {
                    unset($requestedProperties[$propRead]);
                    $returnedProperties[200][$propRead] = new Sabre_DAV_Property_HrefList($readList);
                }
                if (in_array($propWrite,$requestedProperties)) {
                    unset($requestedProperties[$propWrite]);
                    $returnedProperties[200][$propWrite] = new Sabre_DAV_Property_HrefList($writeList);
                }

            }

        } // instanceof IPrincipal


        if ($node instanceof Sabre_CalDAV_ICalendarObject) {
            // The calendar-data property is not supposed to be a 'real' 
            // property, but in large chunks of the spec it does act as such. 
            // Therefore we simply expose it as a property.
            $calDataProp = '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}calendar-data';
            if (in_array($calDataProp, $requestedProperties)) {
                unset($requestedProperties[$calDataProp]);
                $val = $node->get();
                if (is_resource($val))
                    $val = stream_get_contents($val);

                // Taking out \r to not screw up the xml output
                $returnedProperties[200][$calDataProp] = str_replace("\r","", $val);

            }
        }

    }

    /**
     * This function handles the calendar-multiget REPORT.
     *
     * This report is used by the client to fetch the content of a series
     * of urls. Effectively avoiding a lot of redundant requests.
     * 
     * @param DOMNode $dom 
     * @return void
     */
    public function calendarMultiGetReport($dom) {

        $properties = array_keys(Sabre_DAV_XMLUtil::parseProperties($dom->firstChild));

        $hrefElems = $dom->getElementsByTagNameNS('urn:DAV','href');
        foreach($hrefElems as $elem) {
            $uri = $this->server->calculateUri($elem->nodeValue);
            list($objProps) = $this->server->getPropertiesForPath($uri,$properties);
            $propertyList[]=$objProps;

        }

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendBody($this->server->generateMultiStatus($propertyList));

    }

    /**
     * This function handles the calendar-query REPORT
     *
     * This report is used by clients to request calendar objects based on
     * complex conditions.
     * 
     * @param DOMNode $dom 
     * @return void
     */
    public function calendarQueryReport($dom) {

        $requestedProperties = array_keys(Sabre_DAV_XMLUtil::parseProperties($dom->firstChild));

        $filterNode = $dom->getElementsByTagNameNS('urn:ietf:params:xml:ns:caldav','filter');
        if ($filterNode->length!==1) {
            throw new Sabre_DAV_Exception_BadRequest('The calendar-query report must have a filter element');
        }
        $filters = Sabre_CalDAV_XMLUtil::parseCalendarQueryFilters($filterNode->item(0));

        $requestedCalendarData = true;

        if (!in_array('{urn:ietf:params:xml:ns:caldav}calendar-data', $requestedProperties)) {
            // We always retrieve calendar-data, as we need it for filtering.
            $requestedProperties[] = '{urn:ietf:params:xml:ns:caldav}calendar-data';

            // If calendar-data wasn't explicitly requested, we need to remove 
            // it after processing.
            $requestedCalendarData = false;
        }

        // These are the list of nodes that potentially match the requirement
        $candidateNodes = $this->server->getPropertiesForPath($this->server->getRequestUri(),$requestedProperties,$this->server->getHTTPDepth(0));

        $verifiedNodes = array();

        foreach($candidateNodes as $node) {

            // If the node didn't have a calendar-data property, it must not be a calendar object
            if (!isset($node[200]['{urn:ietf:params:xml:ns:caldav}calendar-data'])) continue;

            if ($this->validateFilters($node[200]['{urn:ietf:params:xml:ns:caldav}calendar-data'],$filters)) {
                
                if (!$requestedCalendarData) {
                    unset($node[200]['{urn:ietf:params:xml:ns:caldav}calendar-data']);
                }
                $verifiedNodes[] = $node;
            } 

        }

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendBody($this->server->generateMultiStatus($verifiedNodes));

    }


    /**
     * Verify if a list of filters applies to the calendar data object 
     *
     * The calendarData object must be a valid iCalendar blob. The list of 
     * filters must be formatted as parsed by Sabre_CalDAV_Plugin::parseCalendarQueryFilters
     *
     * @param string $calendarData 
     * @param array $filters 
     * @return bool 
     */
    public function validateFilters($calendarData,$filters) {

        // We are converting the calendar object to an XML structure
        // This makes it far easier to parse
        $xCalendarData = Sabre_CalDAV_ICalendarUtil::toXCal($calendarData);
        $xml = simplexml_load_string($xCalendarData);
        $xml->registerXPathNamespace('c','urn:ietf:params:xml:ns:xcal');

        foreach($filters as $xpath=>$filter) {

            // if-not-defined comes first
            if (isset($filter['is-not-defined'])) {
                if (!$xml->xpath($xpath))
                    continue;
                else
                    return false;
                
            }

            $elem = $xml->xpath($xpath);
            
            if (!$elem) return false;
            $elem = $elem[0];

            if (isset($filter['time-range'])) {

                switch($elem->getName()) {
                    case 'vevent' :
                        $result = $this->validateTimeRangeFilterForEvent($xml,$xpath,$filter);
                        if ($result===false) return false;
                        break;
                    case 'vtodo' :
                        $result = $this->validateTimeRangeFilterForTodo($xml,$xpath,$filter);
                        if ($result===false) return false;
                        break;
                    case 'vjournal' :
                    case 'vfreebusy' :
                    case 'valarm' :
                        // TODO: not implemented
                        break;

                    /*

                    case 'vjournal' :
                        $result = $this->validateTimeRangeFilterForJournal($xml,$xpath,$filter);
                        if ($result===false) return false;
                        break;
                    case 'vfreebusy' :
                        $result = $this->validateTimeRangeFilterForFreeBusy($xml,$xpath,$filter);
                        if ($result===false) return false;
                        break;
                    case 'valarm' :
                        $result = $this->validateTimeRangeFilterForAlarm($xml,$xpath,$filter);
                        if ($result===false) return false;
                        break;

                        */

                }

            } 

            if (isset($filter['text-match'])) {
                $currentString = (string)$elem;

                $isMatching = Sabre_DAV_StringUtil::textMatch($currentString, $filter['text-match']['value'], $filter['text-match']['collation']);
                if ($filter['text-match']['negate-condition'] && $isMatching) return false;
                if (!$filter['text-match']['negate-condition'] && !$isMatching) return false;
                
            }

        }
        return true;
        
    }

    /**
     * Checks whether a time-range filter matches an event.
     * 
     * @param SimpleXMLElement $xml Event as xml object 
     * @param string $currentXPath XPath to check 
     * @param array $currentFilter Filter information 
     * @return void
     */
    private function validateTimeRangeFilterForEvent(SimpleXMLElement $xml,$currentXPath,array $currentFilter) {

        // Grabbing the DTSTART property
        $xdtstart = $xml->xpath($currentXPath.'/c:dtstart');
        if (!count($xdtstart)) {
            throw new Sabre_DAV_Exception_BadRequest('DTSTART property missing from calendar object');
        }

        // The dtstart can be both a date, or datetime property
        if ((string)$xdtstart[0]['value']==='DATE' || strlen((string)$xdtstart[0])===8) {
            $isDateTime = false;
        } else {
            $isDateTime = true;
        }

        // Determining the timezone
        if ($tzid = (string)$xdtstart[0]['tzid']) {
            $tz = new DateTimeZone($tzid);
        } else {
            $tz = null;
        }
        if ($isDateTime) {
            $dtstart = Sabre_CalDAV_XMLUtil::parseICalendarDateTime((string)$xdtstart[0],$tz);
        } else {
            $dtstart = Sabre_CalDAV_XMLUtil::parseICalendarDate((string)$xdtstart[0]);
        }


        // Grabbing the DTEND property
        $xdtend = $xml->xpath($currentXPath.'/c:dtend');
        $dtend = null;

        if (count($xdtend)) {
            // Determining the timezone
            if ($tzid = (string)$xdtend[0]['tzid']) {
                $tz = new DateTimeZone($tzid);
            } else {
                $tz = null;
            }

            // Since the VALUE prameter of both DTSTART and DTEND must be the same
            // we can assume we don't need to check the VALUE paramter of DTEND.
            if ($isDateTime) {
                $dtend = Sabre_CalDAV_XMLUtil::parseICalendarDateTime((string)$xdtend[0],$tz);
            } else {
                $dtend = Sabre_CalDAV_XMLUtil::parseICalendarDate((string)$xdtend[0],$tz);
            }

        } 
        
        if (is_null($dtend)) {
            // The DTEND property was not found. We will first see if the event has a duration
            // property

            $xduration = $xml->xpath($currentXPath.'/c:duration');
            if (count($xduration)) {
                $duration = Sabre_CalDAV_XMLUtil::parseICalendarDuration((string)$xduration[0]);

                // Making sure that the duration is bigger than 0 seconds.
                $tempDT = clone $dtstart;
                $tempDT->modify($duration);
                if ($tempDT > $dtstart) {

                    // use DTEND = DTSTART + DURATION 
                    $dtend = $tempDT;
                } else {
                    // use DTEND = DTSTART
                    $dtend = $dtstart;
                }

            }
        }

        if (is_null($dtend)) {
            if ($isDateTime) {
                // DTEND = DTSTART
                $dtend = $dtstart;
            } else {
                // DTEND = DTSTART + 1 DAY
                $dtend = clone $dtstart;
                $dtend->modify('+1 day');
            }
        }
        // TODO: we need to properly parse RRULE's, but it's very difficult.
        // For now, we're always returning events if they have an RRULE at all.
        $rrule = $xml->xpath($currentXPath.'/c:rrule');
        $hasRrule = (count($rrule))>0; 
       
        if (!is_null($currentFilter['time-range']['start']) && $currentFilter['time-range']['start'] >= $dtend)  return false;
        if (!is_null($currentFilter['time-range']['end'])   && $currentFilter['time-range']['end']   <= $dtstart && !$hasRrule) return false;
        return true;
    
    }

    private function validateTimeRangeFilterForTodo(SimpleXMLElement $xml,$currentXPath,array $filter) {

        // Gathering all relevant elements

        $dtStart = null;
        $duration = null;
        $due = null;
        $completed = null;
        $created = null;

        $xdt = $xml->xpath($currentXPath.'/c:dtstart');
        if (count($xdt)) {
            // The dtstart can be both a date, or datetime property
            if ((string)$xdt[0]['value']==='DATE') {
                $isDateTime = false;
            } else {
                $isDateTime = true;
            }

            // Determining the timezone
            if ($tzid = (string)$xdt[0]['tzid']) {
                $tz = new DateTimeZone($tzid);
            } else {
                $tz = null;
            }
            if ($isDateTime) {
                $dtStart = Sabre_CalDAV_XMLUtil::parseICalendarDateTime((string)$xdt[0],$tz);
            } else {
                $dtStart = Sabre_CalDAV_XMLUtil::parseICalendarDate((string)$xdt[0]);
            }
        }

        // Only need to grab duration if dtStart is set
        if (!is_null($dtStart)) {

            $xduration = $xml->xpath($currentXPath.'/c:duration');
            if (count($xduration)) {
                $duration = Sabre_CalDAV_XMLUtil::parseICalendarDuration((string)$xduration[0]);
            }

        }

        if (!is_null($dtStart) && !is_null($duration)) {

            // Comparision from RFC 4791:
            // (start <= DTSTART+DURATION) AND ((end > DTSTART) OR (end >= DTSTART+DURATION))

            $end = clone $dtStart;
            $end->modify($duration);

            if( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] <= $end) &&
                (is_null($filter['time-range']['end']) || $filter['time-range']['end'] > $dtStart || $filter['time-range']['end'] >= $end) ) {
                return true;
            } else {
                return false;
            }

        }

        // Need to grab the DUE property
        $xdt = $xml->xpath($currentXPath.'/c:due');
        if (count($xdt)) {
            // The due property can be both a date, or datetime property
            if ((string)$xdt[0]['value']==='DATE') {
                $isDateTime = false;
            } else {
                $isDateTime = true;
            }
            // Determining the timezone
            if ($tzid = (string)$xdt[0]['tzid']) {
                $tz = new DateTimeZone($tzid);
            } else {
                $tz = null;
            }
            if ($isDateTime) {
                $due = Sabre_CalDAV_XMLUtil::parseICalendarDateTime((string)$xdt[0],$tz);
            } else {
                $due = Sabre_CalDAV_XMLUtil::parseICalendarDate((string)$xdt[0]);
            }
        }

        if (!is_null($dtStart) && !is_null($due)) {

            // Comparision from RFC 4791:
            // ((start < DUE) OR (start <= DTSTART)) AND ((end > DTSTART) OR (end >= DUE))
            
            if( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] < $due || $filter['time-range']['start'] < $dtstart) &&
                (is_null($filter['time-range']['end'])   || $filter['time-range']['end'] >= $due) ) {
                return true;
            } else {
                return false;
            }

        }

        if (!is_null($dtStart)) {
            
            // Comparision from RFC 4791
            // (start <= DTSTART)  AND (end > DTSTART)
            if ( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] <= $dtStart) &&
                 (is_null($filter['time-range']['end'])   || $filter['time-range']['end'] > $dtStart) ) {
                 return true;
            } else {
                return false;
            }

        }

        if (!is_null($due)) {
            
            // Comparison from RFC 4791
            // (start < DUE) AND (end >= DUE)
            if ( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] < $due) &&
                 (is_null($filter['time-range']['end'])   || $filter['time-range']['end'] >= $due) ) {
                 return true;
            } else {
                return false;
            }

        }
        // Need to grab the COMPLETED property
        $xdt = $xml->xpath($currentXPath.'/c:completed');
        if (count($xdt)) {
            $completed = Sabre_CalDAV_XMLUtil::parseICalendarDateTime((string)$xdt[0]);
        }
        // Need to grab the CREATED property
        $xdt = $xml->xpath($currentXPath.'/c:created');
        if (count($xdt)) {
            $created = Sabre_CalDAV_XMLUtil::parseICalendarDateTime((string)$xdt[0]);
        }

        if (!is_null($completed) && !is_null($created)) {
            // Comparison from RFC 4791
            // ((start <= CREATED) OR (start <= COMPLETED)) AND ((end >= CREATED) OR (end >= COMPLETED))
            if( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] <= $created || $filter['time-range']['start'] <= $completed) &&
                (is_null($filter['time-range']['end'])   || $filter['time-range']['end'] >= $created   || $filter['time-range']['end'] >= $completed)) {
                return true;
            } else {
                return false;
            }
        }

        if (!is_null($completed)) {
            // Comparison from RFC 4791
            // (start <= COMPLETED) AND (end  >= COMPLETED)
            if( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] <= $completed) &&
                (is_null($filter['time-range']['end'])   || $filter['time-range']['end'] >= $completed)) {
                return true;
            } else {
                return false;
            }
        }

        if (!is_null($created)) {
            // Comparison from RFC 4791
            // (end > CREATED)
            if( (is_null($filter['time-range']['end']) || $filter['time-range']['end'] > $created) ) {
                return true;
            } else {
                return false;
            }
        }

        // Everything else is TRUE
        return true;

    }

}
