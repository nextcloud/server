<?php

use Sabre\VObject;

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
        $server->subscribeEvent('beforeMethod', array($this,'beforeMethod'));

        $server->xmlNamespaces[self::NS_CALDAV] = 'cal';
        $server->xmlNamespaces[self::NS_CALENDARSERVER] = 'cs';

        $server->propertyMap['{' . self::NS_CALDAV . '}supported-calendar-component-set'] = 'Sabre_CalDAV_Property_SupportedCalendarComponentSet';
        $server->propertyMap['{' . self::NS_CALDAV . '}schedule-calendar-transp'] = 'Sabre_CalDAV_Property_ScheduleCalendarTransp';

        $server->resourceTypeMapping['Sabre_CalDAV_ICalendar'] = '{urn:ietf:params:xml:ns:caldav}calendar';
        $server->resourceTypeMapping['Sabre_CalDAV_Schedule_IOutbox'] = '{urn:ietf:params:xml:ns:caldav}schedule-outbox';
        $server->resourceTypeMapping['Sabre_CalDAV_Principal_ProxyRead'] = '{http://calendarserver.org/ns/}calendar-proxy-read';
        $server->resourceTypeMapping['Sabre_CalDAV_Principal_ProxyWrite'] = '{http://calendarserver.org/ns/}calendar-proxy-write';
        $server->resourceTypeMapping['Sabre_CalDAV_Notifications_ICollection'] = '{' . self::NS_CALENDARSERVER . '}notification';

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
            '{' . self::NS_CALENDARSERVER . '}calendar-proxy-write-for',
            '{' . self::NS_CALENDARSERVER . '}notification-URL',
            '{' . self::NS_CALENDARSERVER . '}notificationtype'

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

                // Checking if this is a text/calendar content type
                $contentType = $this->server->httpRequest->getHeader('Content-Type');
                if (strpos($contentType, 'text/calendar')!==0) {
                    return;
                }

                // Checking if we're talking to an outbox
                try {
                    $node = $this->server->tree->getNodeForPath($uri);
                } catch (Sabre_DAV_Exception_NotFound $e) {
                    return;
                }
                if (!$node instanceof Sabre_CalDAV_Schedule_IOutbox)
                    return;

                $this->outboxRequest($node, $uri);
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
                $addresses[] = $this->server->getBaseUri() . $node->getPrincipalUrl() . '/';
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

            // notification-URL property
            $notificationUrl = '{' . self::NS_CALENDARSERVER . '}notification-URL';
            if (($index = array_search($notificationUrl, $requestedProperties)) !== false) {
                $principalId = $node->getName();
                $calendarHomePath = 'calendars/' . $principalId . '/notifications/';
                unset($requestedProperties[$index]);
                $returnedProperties[200][$notificationUrl] = new Sabre_DAV_Property_Href($calendarHomePath);
            }

        } // instanceof IPrincipal

        if ($node instanceof Sabre_CalDAV_Notifications_INode) {

            $propertyName = '{' . self::NS_CALENDARSERVER . '}notificationtype';
            if (($index = array_search($propertyName, $requestedProperties)) !== false) {

                $returnedProperties[200][$propertyName] =
                    $node->getNotificationType();

                unset($requestedProperties[$index]);

            }

        } // instanceof Notifications_INode


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
        $hrefElems = $dom->getElementsByTagNameNS('DAV:','href');

        $xpath = new DOMXPath($dom);
        $xpath->registerNameSpace('cal',Sabre_CalDAV_Plugin::NS_CALDAV);
        $xpath->registerNameSpace('dav','DAV:');

        $expand = $xpath->query('/cal:calendar-multiget/dav:prop/cal:calendar-data/cal:expand');
        if ($expand->length>0) {
            $expandElem = $expand->item(0);
            $start = $expandElem->getAttribute('start');
            $end = $expandElem->getAttribute('end');
            if(!$start || !$end) {
                throw new Sabre_DAV_Exception_BadRequest('The "start" and "end" attributes are required for the CALDAV:expand element');
            }
            $start = VObject\DateTimeParser::parseDateTime($start);
            $end = VObject\DateTimeParser::parseDateTime($end);

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
                $vObject = VObject\Reader::read($objProps[200]['{' . self::NS_CALDAV . '}calendar-data']);
                $vObject->expand($start, $end);
                $objProps[200]['{' . self::NS_CALDAV . '}calendar-data'] = $vObject->serialize();
            }

            $propertyList[]=$objProps;

        }

        $prefer = $this->server->getHTTPPRefer();

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary','Brief,Prefer');
        $this->server->httpResponse->sendBody($this->server->generateMultiStatus($propertyList, $prefer['return-minimal']));

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

        $node = $this->server->tree->getNodeForPath($this->server->getRequestUri());
        $depth = $this->server->getHTTPDepth(0);

        // The default result is an empty array
        $result = array();

        // The calendarobject was requested directly. In this case we handle
        // this locally.
        if ($depth == 0 && $node instanceof Sabre_CalDAV_ICalendarObject) {

            $requestedCalendarData = true;
            $requestedProperties = $parser->requestedProperties;

            if (!in_array('{urn:ietf:params:xml:ns:caldav}calendar-data', $requestedProperties)) {

                // We always retrieve calendar-data, as we need it for filtering.
                $requestedProperties[] = '{urn:ietf:params:xml:ns:caldav}calendar-data';

                // If calendar-data wasn't explicitly requested, we need to remove
                // it after processing.
                $requestedCalendarData = false;
            }

            $properties = $this->server->getPropertiesForPath(
                $this->server->getRequestUri(),
                $requestedProperties,
                0
            );

            // This array should have only 1 element, the first calendar
            // object.
            $properties = current($properties);

            // If there wasn't any calendar-data returned somehow, we ignore
            // this.
            if (isset($properties[200]['{urn:ietf:params:xml:ns:caldav}calendar-data'])) {

                $validator = new Sabre_CalDAV_CalendarQueryValidator();
                $vObject = VObject\Reader::read($properties[200]['{urn:ietf:params:xml:ns:caldav}calendar-data']);
                if ($validator->validate($vObject,$parser->filters)) {

                    // If the client didn't require the calendar-data property,
                    // we won't give it back.
                    if (!$requestedCalendarData) {
                        unset($properties[200]['{urn:ietf:params:xml:ns:caldav}calendar-data']);
                    } else {
                        if ($parser->expand) {
                            $vObject->expand($parser->expand['start'], $parser->expand['end']);
                            $properties[200]['{' . self::NS_CALDAV . '}calendar-data'] = $vObject->serialize();
                        }
                    }

                    $result = array($properties);

                }

            }

        }
        // If we're dealing with a calendar, the calendar itself is responsible
        // for the calendar-query.
        if ($node instanceof Sabre_CalDAV_ICalendar && $depth = 1) {

            $nodePaths = $node->calendarQuery($parser->filters);

            foreach($nodePaths as $path) {

                list($properties) =
                    $this->server->getPropertiesForPath($this->server->getRequestUri() . '/' . $path, $parser->requestedProperties);

                if ($parser->expand) {
                    // We need to do some post-processing
                    $vObject = VObject\Reader::read($properties[200]['{urn:ietf:params:xml:ns:caldav}calendar-data']);
                    $vObject->expand($parser->expand['start'], $parser->expand['end']);
                    $properties[200]['{' . self::NS_CALDAV . '}calendar-data'] = $vObject->serialize();
                }

                $result[] = $properties;

            }

        }

        $prefer = $this->server->getHTTPPRefer();

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary','Brief,Prefer');
        $this->server->httpResponse->sendBody($this->server->generateMultiStatus($result, $prefer['return-minimal']));

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
            $start = VObject\DateTimeParser::parseDateTime($start);
        }
        if ($end) {
            $end = VObject\DateTimeParser::parseDateTime($end);
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

        // Doing a calendar-query first, to make sure we get the most
        // performance.
        $urls = $calendar->calendarQuery(array(
            'name' => 'VCALENDAR',
            'comp-filters' => array(
                array(
                    'name' => 'VEVENT',
                    'comp-filters' => array(),
                    'prop-filters' => array(),
                    'is-not-defined' => false,
                    'time-range' => array(
                        'start' => $start,
                        'end' => $end,
                    ),
                ),
            ),
            'prop-filters' => array(),
            'is-not-defined' => false,
            'time-range' => null,
        ));

        $objects = array_map(function($url) use ($calendar) {
            $obj = $calendar->getChild($url)->get();
            return $obj;
        }, $urls);

        $generator = new VObject\FreeBusyGenerator();
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

        $this->validateICalendar($data, $path);

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

        $this->validateICalendar($data, $path);

    }

    /**
     * This event is triggered before any HTTP request is handled.
     *
     * We use this to intercept GET calls to notification nodes, and return the
     * proper response.
     *
     * @param string $method
     * @param string $path
     * @return void
     */
    public function beforeMethod($method, $path) {

        if ($method!=='GET') return;

        try {
            $node = $this->server->tree->getNodeForPath($path);
        } catch (Sabre_DAV_Exception_NotFound $e) {
            return;
        }

        if (!$node instanceof Sabre_CalDAV_Notifications_INode)
            return;

        if (!$this->server->checkPreconditions(true)) return false;

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('cs:notification');
        foreach($this->server->xmlNamespaces as $namespace => $prefix) {
            $root->setAttribute('xmlns:' . $prefix, $namespace);
        }

        $dom->appendChild($root);
        $node->getNotificationType()->serializeBody($this->server, $root);

        $this->server->httpResponse->setHeader('Content-Type','application/xml');
        $this->server->httpResponse->setHeader('ETag',$node->getETag());
        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->sendBody($dom->saveXML());

        return false;

    }

    /**
     * Checks if the submitted iCalendar data is in fact, valid.
     *
     * An exception is thrown if it's not.
     *
     * @param resource|string $data
     * @param string $path
     * @return void
     */
    protected function validateICalendar(&$data, $path) {

        // If it's a stream, we convert it to a string first.
        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }

        // Converting the data to unicode, if needed.
        $data = Sabre_DAV_StringUtil::ensureUTF8($data);

        try {

            $vobj = VObject\Reader::read($data);

        } catch (VObject\ParseException $e) {

            throw new Sabre_DAV_Exception_UnsupportedMediaType('This resource only supports valid iCalendar 2.0 data. Parse error: ' . $e->getMessage());

        }

        if ($vobj->name !== 'VCALENDAR') {
            throw new Sabre_DAV_Exception_UnsupportedMediaType('This collection can only support iCalendar objects.');
        }

        // Get the Supported Components for the target calendar
        list($parentPath,$object) = Sabre_Dav_URLUtil::splitPath($path);
        $calendarProperties = $this->server->getProperties($parentPath,array('{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set'));
        $supportedComponents = $calendarProperties['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set']->getValue();

        $foundType = null;
        $foundUID = null;
        foreach($vobj->getComponents() as $component) {
            switch($component->name) {
                case 'VTIMEZONE' :
                    continue 2;
                case 'VEVENT' :
                case 'VTODO' :
                case 'VJOURNAL' :
                    if (is_null($foundType)) {
                        $foundType = $component->name;
                        if (!in_array($foundType, $supportedComponents)) {
                            throw new Sabre_CalDAV_Exception_InvalidComponentType('This calendar only supports ' . implode(', ', $supportedComponents) . '. We found a ' . $foundType);
                        }
                        if (!isset($component->UID)) {
                            throw new Sabre_DAV_Exception_BadRequest('Every ' . $component->name . ' component must have an UID');
                        }
                        $foundUID = (string)$component->UID;
                    } else {
                        if ($foundType !== $component->name) {
                            throw new Sabre_DAV_Exception_BadRequest('A calendar object must only contain 1 component. We found a ' . $component->name . ' as well as a ' . $foundType);
                        }
                        if ($foundUID !== (string)$component->UID) {
                            throw new Sabre_DAV_Exception_BadRequest('Every ' . $component->name . ' in this object must have identical UIDs');
                        }
                    }
                    break;
                default :
                    throw new Sabre_DAV_Exception_BadRequest('You are not allowed to create components of type: ' . $component->name . ' here');

            }
        }
        if (!$foundType)
            throw new Sabre_DAV_Exception_BadRequest('iCalendar object must contain at least 1 of VEVENT, VTODO or VJOURNAL');

    }

    /**
     * This method handles POST requests to the schedule-outbox.
     *
     * Currently, two types of requests are support:
     *   * FREEBUSY requests from RFC 6638
     *   * Simple iTIP messages from draft-desruisseaux-caldav-sched-04
     *
     * The latter is from an expired early draft of the CalDAV scheduling
     * extensions, but iCal depends on a feature from that spec, so we
     * implement it.
     *
     * @param Sabre_CalDAV_Schedule_IOutbox $outboxNode
     * @param string $outboxUri
     * @return void
     */
    public function outboxRequest(Sabre_CalDAV_Schedule_IOutbox $outboxNode, $outboxUri) {

        // Parsing the request body
        try {
            $vObject = VObject\Reader::read($this->server->httpRequest->getBody(true));
        } catch (VObject\ParseException $e) {
            throw new Sabre_DAV_Exception_BadRequest('The request body must be a valid iCalendar object. Parse error: ' . $e->getMessage());
        }

        // The incoming iCalendar object must have a METHOD property, and a
        // component. The combination of both determines what type of request
        // this is.
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

        // So we support two types of requests:
        //
        // REQUEST with a VFREEBUSY component
        // REQUEST, REPLY, ADD, CANCEL on VEVENT components

        $acl = $this->server->getPlugin('acl');

        if ($componentType === 'VFREEBUSY' && $method === 'REQUEST') {

            $acl && $acl->checkPrivileges($outboxUri,'{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-query-freebusy');
            $this->handleFreeBusyRequest($outboxNode, $vObject);

        } elseif ($componentType === 'VEVENT' && in_array($method, array('REQUEST','REPLY','ADD','CANCEL'))) {

            $acl && $acl->checkPrivileges($outboxUri,'{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-post-vevent');
            $this->handleEventNotification($outboxNode, $vObject);

        } else {

            throw new Sabre_DAV_Exception_NotImplemented('SabreDAV supports only VFREEBUSY (REQUEST) and VEVENT (REQUEST, REPLY, ADD, CANCEL)');

        }

    }

    /**
     * This method handles the REQUEST, REPLY, ADD and CANCEL methods for
     * VEVENT iTip messages.
     *
     * @return void
     */
    protected function handleEventNotification(Sabre_CalDAV_Schedule_IOutbox $outboxNode, VObject\Component $vObject) {

        $originator = $this->server->httpRequest->getHeader('Originator');
        $recipients = $this->server->httpRequest->getHeader('Recipient');

        if (!$originator) {
            throw new Sabre_DAV_Exception_BadRequest('The Originator: header must be specified when making POST requests');
        }
        if (!$recipients) {
            throw new Sabre_DAV_Exception_BadRequest('The Recipient: header must be specified when making POST requests');
        }

        if (!preg_match('/^mailto:(.*)@(.*)$/i', $originator)) {
            throw new Sabre_DAV_Exception_BadRequest('Originator must start with mailto: and must be valid email address');
        }
        $originator = substr($originator,7);

        $recipients = explode(',',$recipients);
        foreach($recipients as $k=>$recipient) {

            $recipient = trim($recipient);
            if (!preg_match('/^mailto:(.*)@(.*)$/i', $recipient)) {
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

        $result = $this->iMIPMessage($originator, $recipients, $vObject, $principal);
        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->setHeader('Content-Type','application/xml');
        $this->server->httpResponse->sendBody($this->generateScheduleResponse($result));

    }

    /**
     * Sends an iMIP message by email.
     *
     * This method must return an array with status codes per recipient.
     * This should look something like:
     *
     * array(
     *    'user1@example.org' => '2.0;Success'
     * )
     *
     * Formatting for this status code can be found at:
     * https://tools.ietf.org/html/rfc5545#section-3.8.8.3
     *
     * A list of valid status codes can be found at:
     * https://tools.ietf.org/html/rfc5546#section-3.6
     *
     * @param string $originator
     * @param array $recipients
     * @param Sabre\VObject\Component $vObject
     * @return array
     */
    protected function iMIPMessage($originator, array $recipients, VObject\Component $vObject, $principal) {

        if (!$this->imipHandler) {
            $resultStatus = '5.2;This server does not support this operation';
        } else {
            $this->imipHandler->sendMessage($originator, $recipients, $vObject, $principal);
            $resultStatus = '2.0;Success';
        }

        $result = array();
        foreach($recipients as $recipient) {
            $result[$recipient] = $resultStatus;
        }

        return $result;

    }

    /**
     * Generates a schedule-response XML body
     *
     * The recipients array is a key->value list, containing email addresses
     * and iTip status codes. See the iMIPMessage method for a description of
     * the value.
     *
     * @param array $recipients
     * @return string
     */
    public function generateScheduleResponse(array $recipients) {

        $dom = new DOMDocument('1.0','utf-8');
        $dom->formatOutput = true;
        $xscheduleResponse = $dom->createElement('cal:schedule-response');
        $dom->appendChild($xscheduleResponse);

        foreach($this->server->xmlNamespaces as $namespace=>$prefix) {

            $xscheduleResponse->setAttribute('xmlns:' . $prefix, $namespace);

        }

        foreach($recipients as $recipient=>$status) {
            $xresponse = $dom->createElement('cal:response');

            $xrecipient = $dom->createElement('cal:recipient');
            $xrecipient->appendChild($dom->createTextNode($recipient));
            $xresponse->appendChild($xrecipient);

            $xrequestStatus = $dom->createElement('cal:request-status');
            $xrequestStatus->appendChild($dom->createTextNode($status));
            $xresponse->appendChild($xrequestStatus);

            $xscheduleResponse->appendChild($xresponse);

        }

        return $dom->saveXML();

    }

    /**
     * This method is responsible for parsing a free-busy query request and
     * returning it's result.
     *
     * @param Sabre_CalDAV_Schedule_IOutbox $outbox
     * @param string $request
     * @return string
     */
    protected function handleFreeBusyRequest(Sabre_CalDAV_Schedule_IOutbox $outbox, VObject\Component $vObject) {

        $vFreeBusy = $vObject->VFREEBUSY;
        $organizer = $vFreeBusy->organizer;

        $organizer = (string)$organizer;

        // Validating if the organizer matches the owner of the inbox.
        $owner = $outbox->getOwner();

        $caldavNS = '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}';

        $uas = $caldavNS . 'calendar-user-address-set';
        $props = $this->server->getProperties($owner,array($uas));

        if (empty($props[$uas]) || !in_array($organizer, $props[$uas]->getHrefs())) {
            throw new Sabre_DAV_Exception_Forbidden('The organizer in the request did not match any of the addresses for the owner of this inbox');
        }

        if (!isset($vFreeBusy->ATTENDEE)) {
            throw new Sabre_DAV_Exception_BadRequest('You must at least specify 1 attendee');
        }

        $attendees = array();
        foreach($vFreeBusy->ATTENDEE as $attendee) {
            $attendees[]= (string)$attendee;
        }


        if (!isset($vFreeBusy->DTSTART) || !isset($vFreeBusy->DTEND)) {
            throw new Sabre_DAV_Exception_BadRequest('DTSTART and DTEND must both be specified');
        }

        $startRange = $vFreeBusy->DTSTART->getDateTime();
        $endRange = $vFreeBusy->DTEND->getDateTime();

        $results = array();
        foreach($attendees as $attendee) {
            $results[] = $this->getFreeBusyForEmail($attendee, $startRange, $endRange, $vObject);
        }

        $dom = new DOMDocument('1.0','utf-8');
        $dom->formatOutput = true;
        $scheduleResponse = $dom->createElement('cal:schedule-response');
        foreach($this->server->xmlNamespaces as $namespace=>$prefix) {

            $scheduleResponse->setAttribute('xmlns:' . $prefix,$namespace);

        }
        $dom->appendChild($scheduleResponse);

        foreach($results as $result) {
            $response = $dom->createElement('cal:response');

            $recipient = $dom->createElement('cal:recipient');
            $recipientHref = $dom->createElement('d:href');

            $recipientHref->appendChild($dom->createTextNode($result['href']));
            $recipient->appendChild($recipientHref);
            $response->appendChild($recipient);

            $reqStatus = $dom->createElement('cal:request-status');
            $reqStatus->appendChild($dom->createTextNode($result['request-status']));
            $response->appendChild($reqStatus);

            if (isset($result['calendar-data'])) {

                $calendardata = $dom->createElement('cal:calendar-data');
                $calendardata->appendChild($dom->createTextNode(str_replace("\r\n","\n",$result['calendar-data']->serialize())));
                $response->appendChild($calendardata);

            }
            $scheduleResponse->appendChild($response);
        }

        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->setHeader('Content-Type','application/xml');
        $this->server->httpResponse->sendBody($dom->saveXML());

    }

    /**
     * Returns free-busy information for a specific address. The returned
     * data is an array containing the following properties:
     *
     * calendar-data : A VFREEBUSY VObject
     * request-status : an iTip status code.
     * href: The principal's email address, as requested
     *
     * The following request status codes may be returned:
     *   * 2.0;description
     *   * 3.7;description
     *
     * @param string $email address
     * @param DateTime $start
     * @param DateTime $end
     * @param Sabre_VObject_Component $request
     * @return Sabre_VObject_Component
     */
    protected function getFreeBusyForEmail($email, DateTime $start, DateTime $end, VObject\Component $request) {

        $caldavNS = '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}';

        $aclPlugin = $this->server->getPlugin('acl');
        if (substr($email,0,7)==='mailto:') $email = substr($email,7);

        $result = $aclPlugin->principalSearch(
            array('{http://sabredav.org/ns}email-address' => $email),
            array(
                '{DAV:}principal-URL', $caldavNS . 'calendar-home-set',
                '{http://sabredav.org/ns}email-address',
            )
        );

        if (!count($result)) {
            return array(
                'request-status' => '3.7;Could not find principal',
                'href' => 'mailto:' . $email,
            );
        }

        if (!isset($result[0][200][$caldavNS . 'calendar-home-set'])) {
            return array(
                'request-status' => '3.7;No calendar-home-set property found',
                'href' => 'mailto:' . $email,
            );
        }
        $homeSet = $result[0][200][$caldavNS . 'calendar-home-set']->getHref();

        // Grabbing the calendar list
        $objects = array();
        foreach($this->server->tree->getNodeForPath($homeSet)->getChildren() as $node) {
            if (!$node instanceof Sabre_CalDAV_ICalendar) {
                continue;
            }
            $aclPlugin->checkPrivileges($homeSet . $node->getName() ,$caldavNS . 'read-free-busy');

            // Getting the list of object uris within the time-range
            $urls = $node->calendarQuery(array(
                'name' => 'VCALENDAR',
                'comp-filters' => array(
                    array(
                        'name' => 'VEVENT',
                        'comp-filters' => array(),
                        'prop-filters' => array(),
                        'is-not-defined' => false,
                        'time-range' => array(
                            'start' => $start,
                            'end' => $end,
                        ),
                    ),
                ),
                'prop-filters' => array(),
                'is-not-defined' => false,
                'time-range' => null,
            ));

            $calObjects = array_map(function($url) use ($node) {
                $obj = $node->getChild($url)->get();
                return $obj;
            }, $urls);

            $objects = array_merge($objects,$calObjects);

        }

        $vcalendar = VObject\Component::create('VCALENDAR');
        $vcalendar->VERSION = '2.0';
        $vcalendar->METHOD = 'REPLY';
        $vcalendar->CALSCALE = 'GREGORIAN';
        $vcalendar->PRODID = '-//SabreDAV//SabreDAV ' . Sabre_DAV_Version::VERSION . '//EN';

        $generator = new VObject\FreeBusyGenerator();
        $generator->setObjects($objects);
        $generator->setTimeRange($start, $end);
        $generator->setBaseObject($vcalendar);

        $result = $generator->getResult();

        $vcalendar->VFREEBUSY->ATTENDEE = 'mailto:' . $email;
        $vcalendar->VFREEBUSY->UID = (string)$request->VFREEBUSY->UID;
        $vcalendar->VFREEBUSY->ORGANIZER = clone $request->VFREEBUSY->ORGANIZER;

        return array(
            'calendar-data' => $result,
            'request-status' => '2.0;Success',
            'href' => 'mailto:' . $email,
        );
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
