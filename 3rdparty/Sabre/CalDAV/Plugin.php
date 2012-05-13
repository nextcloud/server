<?php

/**
 * CalDAV plugin
 *
 * This plugin provides functionality added by CalDAV (RFC 4791)
 * It implements new reports, and the MKCALENDAR method.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
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
     * The email handler for invites and other scheduling messages.
     * 
     * @var Sabre_CalDAV_Schedule_IMip 
     */
    protected $imipHandler;

    /**
     * Sets the iMIP handler.
     *
     * iMIP = The email transport of iCalendar scheduling messages. Setting 
     * this is optional, but if you want the server to allow invites to be sent 
     * out, you must set a handler.
     *
     * Specifically iCal will plain assume that the server supports this. If 
     * the server doesn't, iCal will display errors when inviting people to 
     * events.
     *
     * @param Sabre_CalDAV_Schedule_IMip $imipHandler 
     * @return void
     */
    public function setIMipHandler(Sabre_CalDAV_Schedule_IMip $imipHandler) {

        $this->imipHandler = $imipHandler;

    }

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
            } catch (Sabre_DAV_Exception_NotFound $e) {
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

        $reports = array();
        if ($node instanceof Sabre_CalDAV_ICalendar || $node instanceof Sabre_CalDAV_ICalendarObject) {
            $reports[] = '{' . self::NS_CALDAV . '}calendar-multiget';
            $reports[] = '{' . self::NS_CALDAV . '}calendar-query';
        }
        if ($node instanceof Sabre_CalDAV_ICalendar) {
            $reports[] = '{' . self::NS_CALDAV . '}free-busy-query';
        }
        return $reports;

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
        $server->subscribeEvent('onHTMLActionsPanel', array($this,'htmlActionsPanel'));
        $server->subscribeEvent('onBrowserPostAction', array($this,'browserPostAction'));
        $server->subscribeEvent('beforeWriteContent', array($this, 'beforeWriteContent'));
        $server->subscribeEvent('beforeCreateFile', array($this, 'beforeCreateFile'));

        $server->xmlNamespaces[self::NS_CALDAV] = 'cal';
        $server->xmlNamespaces[self::NS_CALENDARSERVER] = 'cs';

        $server->propertyMap['{' . self::NS_CALDAV . '}supported-calendar-component-set'] = 'Sabre_CalDAV_Property_SupportedCalendarComponentSet';

        $server->resourceTypeMapping['Sabre_CalDAV_ICalendar'] = '{urn:ietf:params:xml:ns:caldav}calendar';
        $server->resourceTypeMapping['Sabre_CalDAV_Schedule_IOutbox'] = '{urn:ietf:params:xml:ns:caldav}schedule-outbox';
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
            '{' . self::NS_CALDAV . '}schedule-inbox-URL',
            '{' . self::NS_CALDAV . '}schedule-outbox-URL',
            '{' . self::NS_CALDAV . '}calendar-user-address-set',
            '{' . self::NS_CALDAV . '}calendar-user-type',

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
     * @param string $uri
     * @return bool
     */
    public function unknownMethod($method, $uri) {

        switch ($method) {
            case 'MKCALENDAR' :
                $this->httpMkCalendar($uri);
                // false is returned to stop the propagation of the
                // unknownMethod event.
                return false;
            case 'POST' :
                // Checking if we're talking to an outbox
                try {
                    $node = $this->server->tree->getNodeForPath($uri);
                } catch (Sabre_DAV_Exception_NotFound $e) {
                    return;
                }
                if (!$node instanceof Sabre_CalDAV_Schedule_IOutbox)
                    return;

                $this->outboxRequest($node);
                return false;

        }

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
            case '{'.self::NS_CALDAV.'}free-busy-query' :
                $this->freeBusyQueryReport($dom);
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

            // schedule-outbox-URL property
            $scheduleProp = '{' . self::NS_CALDAV . '}schedule-outbox-URL';
            if (in_array($scheduleProp,$requestedProperties)) {
                $principalId = $node->getName();
                $outboxPath = self::CALENDAR_ROOT . '/' . $principalId . '/outbox';
                unset($requestedProperties[$scheduleProp]);
                $returnedProperties[200][$scheduleProp] = new Sabre_DAV_Property_Href($outboxPath);
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

        $xpath = new DOMXPath($dom);
        $xpath->registerNameSpace('cal',Sabre_CalDAV_Plugin::NS_CALDAV);
        $xpath->registerNameSpace('dav','urn:DAV');

        $expand = $xpath->query('/cal:calendar-multiget/dav:prop/cal:calendar-data/cal:expand');
        if ($expand->length>0) {
            $expandElem = $expand->item(0);
            $start = $expandElem->getAttribute('start');
            $end = $expandElem->getAttribute('end');
            if(!$start || !$end) {
                throw new Sabre_DAV_Exception_BadRequest('The "start" and "end" attributes are required for the CALDAV:expand element');
            }
            $start = Sabre_VObject_DateTimeParser::parseDateTime($start);
            $end = Sabre_VObject_DateTimeParser::parseDateTime($end);

            if ($end <= $start) {
                throw new Sabre_DAV_Exception_BadRequest('The end-date must be larger than the start-date in the expand element.');
            }

            $expand = true;

        } else {

            $expand = false;

        }

        foreach($hrefElems as $elem) {
            $uri = $this->server->calculateUri($elem->nodeValue);
            list($objProps) = $this->server->getPropertiesForPath($uri,$properties);

            if ($expand && isset($objProps[200]['{' . self::NS_CALDAV . '}calendar-data'])) {
                $vObject = Sabre_VObject_Reader::read($objProps[200]['{' . self::NS_CALDAV . '}calendar-data']);
                $vObject->expand($start, $end);
                $objProps[200]['{' . self::NS_CALDAV . '}calendar-data'] = $vObject->serialize();
            }

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

        $parser = new Sabre_CalDAV_CalendarQueryParser($dom);
        $parser->parse();

        $requestedCalendarData = true;
        $requestedProperties = $parser->requestedProperties;

        if (!in_array('{urn:ietf:params:xml:ns:caldav}calendar-data', $requestedProperties)) {

            // We always retrieve calendar-data, as we need it for filtering.
            $requestedProperties[] = '{urn:ietf:params:xml:ns:caldav}calendar-data';

            // If calendar-data wasn't explicitly requested, we need to remove
            // it after processing.
            $requestedCalendarData = false;
        }

        // These are the list of nodes that potentially match the requirement
        $candidateNodes = $this->server->getPropertiesForPath(
            $this->server->getRequestUri(),
            $requestedProperties,
            $this->server->getHTTPDepth(0)
        );

        $verifiedNodes = array();

        $validator = new Sabre_CalDAV_CalendarQueryValidator();

        foreach($candidateNodes as $node) {

            // If the node didn't have a calendar-data property, it must not be a calendar object
            if (!isset($node[200]['{urn:ietf:params:xml:ns:caldav}calendar-data']))
                continue;

            $vObject = Sabre_VObject_Reader::read($node[200]['{urn:ietf:params:xml:ns:caldav}calendar-data']);
            if ($validator->validate($vObject,$parser->filters)) {

                if (!$requestedCalendarData) {
                    unset($node[200]['{urn:ietf:params:xml:ns:caldav}calendar-data']);
                }
                if ($parser->expand) {
                    $vObject->expand($parser->expand['start'], $parser->expand['end']);
                    $node[200]['{' . self::NS_CALDAV . '}calendar-data'] = $vObject->serialize();
                }
                $verifiedNodes[] = $node;
            }

        }

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendBody($this->server->generateMultiStatus($verifiedNodes));

    }

    /**
     * This method is responsible for parsing the request and generating the
     * response for the CALDAV:free-busy-query REPORT.
     *
     * @param DOMNode $dom
     * @return void
     */
    protected function freeBusyQueryReport(DOMNode $dom) {

        $start = null;
        $end = null;

        foreach($dom->firstChild->childNodes as $childNode) {

            $clark = Sabre_DAV_XMLUtil::toClarkNotation($childNode);
            if ($clark == '{' . self::NS_CALDAV . '}time-range') {
                $start = $childNode->getAttribute('start');
                $end = $childNode->getAttribute('end');
                break;
            }

        }
        if ($start) {
            $start = Sabre_VObject_DateTimeParser::parseDateTime($start);
        }
        if ($end) {
            $end = Sabre_VObject_DateTimeParser::parseDateTime($end);
        }

        if (!$start && !$end) {
            throw new Sabre_DAV_Exception_BadRequest('The freebusy report must have a time-range filter');
        }
        $acl = $this->server->getPlugin('acl');

        if (!$acl) {
            throw new Sabre_DAV_Exception('The ACL plugin must be loaded for free-busy queries to work');
        }
        $uri = $this->server->getRequestUri();
        $acl->checkPrivileges($uri,'{' . self::NS_CALDAV . '}read-free-busy');

        $calendar = $this->server->tree->getNodeForPath($uri);
        if (!$calendar instanceof Sabre_CalDAV_ICalendar) {
            throw new Sabre_DAV_Exception_NotImplemented('The free-busy-query REPORT is only implemented on calendars');
        }

        $objects = array_map(function($child) {
            $obj = $child->get();
            if (is_resource($obj)) {
                $obj = stream_get_contents($obj);
            }
            return $obj;
        }, $calendar->getChildren());

        $generator = new Sabre_VObject_FreeBusyGenerator();
        $generator->setObjects($objects);
        $generator->setTimeRange($start, $end);
        $result = $generator->getResult();
        $result = $result->serialize();

        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->setHeader('Content-Type', 'text/calendar');
        $this->server->httpResponse->setHeader('Content-Length', strlen($result));
        $this->server->httpResponse->sendBody($result);

    }

    /**
     * This method is triggered before a file gets updated with new content.
     *
     * This plugin uses this method to ensure that CalDAV objects receive
     * valid calendar data.
     *
     * @param string $path
     * @param Sabre_DAV_IFile $node
     * @param resource $data
     * @return void
     */
    public function beforeWriteContent($path, Sabre_DAV_IFile $node, &$data) {

        if (!$node instanceof Sabre_CalDAV_ICalendarObject)
            return;

        $this->validateICalendar($data);

    }

    /**
     * This method is triggered before a new file is created.
     *
     * This plugin uses this method to ensure that newly created calendar
     * objects contain valid calendar data.
     *
     * @param string $path
     * @param resource $data
     * @param Sabre_DAV_ICollection $parentNode
     * @return void
     */
    public function beforeCreateFile($path, &$data, Sabre_DAV_ICollection $parentNode) {

        if (!$parentNode instanceof Sabre_CalDAV_Calendar)
            return;

        $this->validateICalendar($data);

    }

    /**
     * Checks if the submitted iCalendar data is in fact, valid.
     *
     * An exception is thrown if it's not.
     *
     * @param resource|string $data
     * @return void
     */
    protected function validateICalendar(&$data) {

        // If it's a stream, we convert it to a string first.
        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }

        // Converting the data to unicode, if needed.
        $data = Sabre_DAV_StringUtil::ensureUTF8($data);

        try {

            $vobj = Sabre_VObject_Reader::read($data);

        } catch (Sabre_VObject_ParseException $e) {

            throw new Sabre_DAV_Exception_UnsupportedMediaType('This resource only supports valid iCalendar 2.0 data. Parse error: ' . $e->getMessage());

        }

    }

    /**
     * This method handles POST requests to the schedule-outbox
     *
     * @param Sabre_CalDAV_Schedule_IOutbox $outboxNode
     * @return void
     */
    public function outboxRequest(Sabre_CalDAV_Schedule_IOutbox $outboxNode) {

        $originator = $this->server->httpRequest->getHeader('Originator');
        $recipients = $this->server->httpRequest->getHeader('Recipient');

        if (!$originator) {
            throw new Sabre_DAV_Exception_BadRequest('The Originator: header must be specified when making POST requests');
        } 
        if (!$recipients) {
            throw new Sabre_DAV_Exception_BadRequest('The Recipient: header must be specified when making POST requests');
        } 

        if (!preg_match('/^mailto:(.*)@(.*)$/', $originator)) {
            throw new Sabre_DAV_Exception_BadRequest('Originator must start with mailto: and must be valid email address');
        }
        $originator = substr($originator,7);

        $recipients = explode(',',$recipients);
        foreach($recipients as $k=>$recipient) {

            $recipient = trim($recipient);
            if (!preg_match('/^mailto:(.*)@(.*)$/', $recipient)) { 
                throw new Sabre_DAV_Exception_BadRequest('Recipients must start with mailto: and must be valid email address');
            }
            $recipient = substr($recipient, 7);
            $recipients[$k] = $recipient;
        }

        // We need to make sure that 'originator' matches one of the email 
        // addresses of the selected principal.
        $principal = $outboxNode->getOwner();
        $props = $this->server->getProperties($principal,array(
            '{' . self::NS_CALDAV . '}calendar-user-address-set',
        ));

        $addresses = array();
        if (isset($props['{' . self::NS_CALDAV . '}calendar-user-address-set'])) {
            $addresses = $props['{' . self::NS_CALDAV . '}calendar-user-address-set']->getHrefs();
        }

        if (!in_array('mailto:' . $originator, $addresses)) {
            throw new Sabre_DAV_Exception_Forbidden('The addresses specified in the Originator header did not match any addresses in the owners calendar-user-address-set header');
        }

        try { 
            $vObject = Sabre_VObject_Reader::read($this->server->httpRequest->getBody(true));
        } catch (Sabre_VObject_ParseException $e) {
            throw new Sabre_DAV_Exception_BadRequest('The request body must be a valid iCalendar object. Parse error: ' . $e->getMessage());
        }

        // Checking for the object type
        $componentType = null;
        foreach($vObject->getComponents() as $component) {
            if ($component->name !== 'VTIMEZONE') {
                $componentType = $component->name;
                break;
            }
        }
        if (is_null($componentType)) {
            throw new Sabre_DAV_Exception_BadRequest('We expected at least one VTODO, VJOURNAL, VFREEBUSY or VEVENT component');
        }

        // Validating the METHOD
        $method = strtoupper((string)$vObject->METHOD);
        if (!$method) {
            throw new Sabre_DAV_Exception_BadRequest('A METHOD property must be specified in iTIP messages');
        }

        if (in_array($method, array('REQUEST','REPLY','ADD','CANCEL')) && $componentType==='VEVENT') {
            $this->iMIPMessage($originator, $recipients, $vObject);
            $this->server->httpResponse->sendStatus(200);
            $this->server->httpResponse->sendBody('Messages sent');
        } else {
            throw new Sabre_DAV_Exception_NotImplemented('This iTIP method is currently not implemented');
        }

    }

    /**
     * Sends an iMIP message by email.
     * 
     * @param string $originator 
     * @param array $recipients 
     * @param Sabre_VObject_Component $vObject 
     * @return void
     */
    protected function iMIPMessage($originator, array $recipients, Sabre_VObject_Component $vObject) {

        if (!$this->imipHandler) {
            throw new Sabre_DAV_Exception_NotImplemented('No iMIP handler is setup on this server.');
        }
        $this->imipHandler->sendMessage($originator, $recipients, $vObject); 

    }

    /**
     * This method is used to generate HTML output for the
     * Sabre_DAV_Browser_Plugin. This allows us to generate an interface users
     * can use to create new calendars.
     *
     * @param Sabre_DAV_INode $node
     * @param string $output
     * @return bool
     */
    public function htmlActionsPanel(Sabre_DAV_INode $node, &$output) {

        if (!$node instanceof Sabre_CalDAV_UserCalendars)
            return;

        $output.= '<tr><td colspan="2"><form method="post" action="">
            <h3>Create new calendar</h3>
            <input type="hidden" name="sabreAction" value="mkcalendar" />
            <label>Name (uri):</label> <input type="text" name="name" /><br />
            <label>Display name:</label> <input type="text" name="{DAV:}displayname" /><br />
            <input type="submit" value="create" />
            </form>
            </td></tr>';

        return false;

    }

    /**
     * This method allows us to intercept the 'mkcalendar' sabreAction. This
     * action enables the user to create new calendars from the browser plugin.
     *
     * @param string $uri
     * @param string $action
     * @param array $postVars
     * @return bool
     */
    public function browserPostAction($uri, $action, array $postVars) {

        if ($action!=='mkcalendar')
            return;

        $resourceType = array('{DAV:}collection','{urn:ietf:params:xml:ns:caldav}calendar');
        $properties = array();
        if (isset($postVars['{DAV:}displayname'])) {
            $properties['{DAV:}displayname'] = $postVars['{DAV:}displayname'];
        }
        $this->server->createCollection($uri . '/' . $postVars['name'],$resourceType,$properties);
        return false;

    }

}
