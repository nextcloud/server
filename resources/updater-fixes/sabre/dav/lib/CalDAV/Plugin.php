<?php

namespace Sabre\CalDAV;

use DateTimeZone;
use Sabre\DAV;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\MkCol;
use Sabre\DAV\Xml\Property\Href;
use Sabre\DAVACL;
use Sabre\VObject;
use Sabre\HTTP;
use Sabre\Uri;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * CalDAV plugin
 *
 * This plugin provides functionality added by CalDAV (RFC 4791)
 * It implements new reports, and the MKCALENDAR method.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends DAV\ServerPlugin {

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
     * @var DAV\Server
     */
    protected $server;

    /**
     * The default PDO storage uses a MySQL MEDIUMBLOB for iCalendar data,
     * which can hold up to 2^24 = 16777216 bytes. This is plenty. We're
     * capping it to 10M here.
     */
    protected $maxResourceSize = 10000000;

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
    function getHTTPMethods($uri) {

        // The MKCALENDAR is only available on unmapped uri's, whose
        // parents extend IExtendedCollection
        list($parent, $name) = Uri\split($uri);

        $node = $this->server->tree->getNodeForPath($parent);

        if ($node instanceof DAV\IExtendedCollection) {
            try {
                $node->getChild($name);
            } catch (DAV\Exception\NotFound $e) {
                return ['MKCALENDAR'];
            }
        }
        return [];

    }

    /**
     * Returns the path to a principal's calendar home.
     *
     * The return url must not end with a slash.
     *
     * @param string $principalUrl
     * @return string
     */
    function getCalendarHomeForPrincipal($principalUrl) {

        // The default is a bit naive, but it can be overwritten.
        list(, $nodeName) = Uri\split($principalUrl);

        return self::CALENDAR_ROOT . '/' . $nodeName;

    }

    /**
     * Returns a list of features for the DAV: HTTP header.
     *
     * @return array
     */
    function getFeatures() {

        return ['calendar-access', 'calendar-proxy'];

    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using DAV\Server::getPlugin
     *
     * @return string
     */
    function getPluginName() {

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
    function getSupportedReportSet($uri) {

        $node = $this->server->tree->getNodeForPath($uri);

        $reports = [];
        if ($node instanceof ICalendarObjectContainer || $node instanceof ICalendarObject) {
            $reports[] = '{' . self::NS_CALDAV . '}calendar-multiget';
            $reports[] = '{' . self::NS_CALDAV . '}calendar-query';
        }
        if ($node instanceof ICalendar) {
            $reports[] = '{' . self::NS_CALDAV . '}free-busy-query';
        }
        // iCal has a bug where it assumes that sync support is enabled, only
        // if we say we support it on the calendar-home, even though this is
        // not actually the case.
        if ($node instanceof CalendarHome && $this->server->getPlugin('sync')) {
            $reports[] = '{DAV:}sync-collection';
        }
        return $reports;

    }

    /**
     * Initializes the plugin
     *
     * @param DAV\Server $server
     * @return void
     */
    function initialize(DAV\Server $server) {

        $this->server = $server;

        $server->on('method:MKCALENDAR',   [$this, 'httpMkCalendar']);
        $server->on('report',              [$this, 'report']);
        $server->on('propFind',            [$this, 'propFind']);
        $server->on('onHTMLActionsPanel',  [$this, 'htmlActionsPanel']);
        $server->on('beforeCreateFile',    [$this, 'beforeCreateFile']);
        $server->on('beforeWriteContent',  [$this, 'beforeWriteContent']);
        $server->on('afterMethod:GET',     [$this, 'httpAfterGET']);

        $server->xml->namespaceMap[self::NS_CALDAV] = 'cal';
        $server->xml->namespaceMap[self::NS_CALENDARSERVER] = 'cs';

        $server->xml->elementMap['{' . self::NS_CALDAV . '}calendar-query'] = 'Sabre\\CalDAV\\Xml\\Request\\CalendarQueryReport';
        $server->xml->elementMap['{' . self::NS_CALDAV . '}calendar-multiget'] = 'Sabre\\CalDAV\\Xml\\Request\\CalendarMultiGetReport';
        $server->xml->elementMap['{' . self::NS_CALDAV . '}free-busy-query'] = 'Sabre\\CalDAV\\Xml\\Request\\FreeBusyQueryReport';
        $server->xml->elementMap['{' . self::NS_CALDAV . '}mkcalendar'] = 'Sabre\\CalDAV\\Xml\\Request\\MkCalendar';
        $server->xml->elementMap['{' . self::NS_CALDAV . '}schedule-calendar-transp'] = 'Sabre\\CalDAV\\Xml\\Property\\ScheduleCalendarTransp';
        $server->xml->elementMap['{' . self::NS_CALDAV . '}supported-calendar-component-set'] = 'Sabre\\CalDAV\\Xml\\Property\\SupportedCalendarComponentSet';

        $server->resourceTypeMapping['\\Sabre\\CalDAV\\ICalendar'] = '{urn:ietf:params:xml:ns:caldav}calendar';

        $server->resourceTypeMapping['\\Sabre\\CalDAV\\Principal\\IProxyRead'] = '{http://calendarserver.org/ns/}calendar-proxy-read';
        $server->resourceTypeMapping['\\Sabre\\CalDAV\\Principal\\IProxyWrite'] = '{http://calendarserver.org/ns/}calendar-proxy-write';

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

            // CalendarServer extensions
            '{' . self::NS_CALENDARSERVER . '}getctag',
            '{' . self::NS_CALENDARSERVER . '}calendar-proxy-read-for',
            '{' . self::NS_CALENDARSERVER . '}calendar-proxy-write-for'

        );

        if ($aclPlugin = $server->getPlugin('acl')) {
            $aclPlugin->principalSearchPropertySet['{' . self::NS_CALDAV . '}calendar-user-address-set'] = 'Calendar address';
        }
    }

    /**
     * This functions handles REPORT requests specific to CalDAV
     *
     * @param string $reportName
     * @param mixed $report
     * @return bool
     */
    function report($reportName, $report) {

        switch ($reportName) {
            case '{' . self::NS_CALDAV . '}calendar-multiget' :
                $this->server->transactionType = 'report-calendar-multiget';
                $this->calendarMultiGetReport($report);
                return false;
            case '{' . self::NS_CALDAV . '}calendar-query' :
                $this->server->transactionType = 'report-calendar-query';
                $this->calendarQueryReport($report);
                return false;
            case '{' . self::NS_CALDAV . '}free-busy-query' :
                $this->server->transactionType = 'report-free-busy-query';
                $this->freeBusyQueryReport($report);
                return false;

        }


    }

    /**
     * This function handles the MKCALENDAR HTTP method, which creates
     * a new calendar.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    function httpMkCalendar(RequestInterface $request, ResponseInterface $response) {

        $body = $request->getBodyAsString();
        $path = $request->getPath();

        $properties = [];

        if ($body) {

            try {
                $mkcalendar = $this->server->xml->expect(
                    '{urn:ietf:params:xml:ns:caldav}mkcalendar',
                    $body
                );
            } catch (\Sabre\Xml\ParseException $e) {
                throw new BadRequest($e->getMessage(), null, $e);
            }
            $properties = $mkcalendar->getProperties();

        }

        // iCal abuses MKCALENDAR since iCal 10.9.2 to create server-stored
        // subscriptions. Before that it used MKCOL which was the correct way
        // to do this.
        //
        // If the body had a {DAV:}resourcetype, it means we stumbled upon this
        // request, and we simply use it instead of the pre-defined list.
        if (isset($properties['{DAV:}resourcetype'])) {
            $resourceType = $properties['{DAV:}resourcetype']->getValue();
        } else {
            $resourceType = ['{DAV:}collection','{urn:ietf:params:xml:ns:caldav}calendar'];
        }

        $this->server->createCollection($path, new MkCol($resourceType, $properties));

        $this->server->httpResponse->setStatus(201);
        $this->server->httpResponse->setHeader('Content-Length', 0);

        // This breaks the method chain.
        return false;
    }

    /**
     * PropFind
     *
     * This method handler is invoked before any after properties for a
     * resource are fetched. This allows us to add in any CalDAV specific
     * properties.
     *
     * @param DAV\PropFind $propFind
     * @param DAV\INode $node
     * @return void
     */
    function propFind(DAV\PropFind $propFind, DAV\INode $node) {

        $ns = '{' . self::NS_CALDAV . '}';

        if ($node instanceof ICalendarObjectContainer) {

            $propFind->handle($ns . 'max-resource-size', $this->maxResourceSize);
            $propFind->handle($ns . 'supported-calendar-data', function() {
                return new Xml\Property\SupportedCalendarData();
            });
            $propFind->handle($ns . 'supported-collation-set', function() {
                return new Xml\Property\SupportedCollationSet();
            });

        }

        if ($node instanceof DAVACL\IPrincipal) {

            $principalUrl = $node->getPrincipalUrl();

            $propFind->handle('{' . self::NS_CALDAV . '}calendar-home-set', function() use ($principalUrl) {

                $calendarHomePath = $this->getCalendarHomeForPrincipal($principalUrl) . '/';
                return new Href($calendarHomePath);

            });
            // The calendar-user-address-set property is basically mapped to
            // the {DAV:}alternate-URI-set property.
            $propFind->handle('{' . self::NS_CALDAV . '}calendar-user-address-set', function() use ($node) {
                $addresses = $node->getAlternateUriSet();
                $addresses[] = $this->server->getBaseUri() . $node->getPrincipalUrl() . '/';
                return new Href($addresses, false);
            });
            // For some reason somebody thought it was a good idea to add
            // another one of these properties. We're supporting it too.
            $propFind->handle('{' . self::NS_CALENDARSERVER . '}email-address-set', function() use ($node) {
                $addresses = $node->getAlternateUriSet();
                $emails = [];
                foreach ($addresses as $address) {
                    if (substr($address, 0, 7) === 'mailto:') {
                        $emails[] = substr($address, 7);
                    }
                }
                return new Xml\Property\EmailAddressSet($emails);
            });

            // These two properties are shortcuts for ical to easily find
            // other principals this principal has access to.
            $propRead = '{' . self::NS_CALENDARSERVER . '}calendar-proxy-read-for';
            $propWrite = '{' . self::NS_CALENDARSERVER . '}calendar-proxy-write-for';

            if ($propFind->getStatus($propRead) === 404 || $propFind->getStatus($propWrite) === 404) {

                $aclPlugin = $this->server->getPlugin('acl');
                $membership = $aclPlugin->getPrincipalMembership($propFind->getPath());
                $readList = [];
                $writeList = [];

                foreach ($membership as $group) {

                    $groupNode = $this->server->tree->getNodeForPath($group);

                    $listItem = Uri\split($group)[0] . '/';

                    // If the node is either ap proxy-read or proxy-write
                    // group, we grab the parent principal and add it to the
                    // list.
                    if ($groupNode instanceof Principal\IProxyRead) {
                        $readList[] = $listItem;
                    }
                    if ($groupNode instanceof Principal\IProxyWrite) {
                        $writeList[] = $listItem;
                    }

                }

                $propFind->set($propRead, new Href($readList));
                $propFind->set($propWrite, new Href($writeList));

            }

        } // instanceof IPrincipal

        if ($node instanceof ICalendarObject) {

            // The calendar-data property is not supposed to be a 'real'
            // property, but in large chunks of the spec it does act as such.
            // Therefore we simply expose it as a property.
            $propFind->handle('{' . self::NS_CALDAV . '}calendar-data', function() use ($node) {
                $val = $node->get();
                if (is_resource($val))
                    $val = stream_get_contents($val);

                // Taking out \r to not screw up the xml output
                return str_replace("\r", "", $val);

            });

        }

    }

    /**
     * This function handles the calendar-multiget REPORT.
     *
     * This report is used by the client to fetch the content of a series
     * of urls. Effectively avoiding a lot of redundant requests.
     *
     * @param CalendarMultiGetReport $report
     * @return void
     */
    function calendarMultiGetReport($report) {

        $needsJson = $report->contentType === 'application/calendar+json';

        $timeZones = [];
        $propertyList = [];

        $paths = array_map(
            [$this->server, 'calculateUri'],
            $report->hrefs
        );

        foreach ($this->server->getPropertiesForMultiplePaths($paths, $report->properties) as $uri => $objProps) {

            if (($needsJson || $report->expand) && isset($objProps[200]['{' . self::NS_CALDAV . '}calendar-data'])) {
                $vObject = VObject\Reader::read($objProps[200]['{' . self::NS_CALDAV . '}calendar-data']);

                if ($report->expand) {
                    // We're expanding, and for that we need to figure out the
                    // calendar's timezone.
                    list($calendarPath) = Uri\split($uri);
                    if (!isset($timeZones[$calendarPath])) {
                        // Checking the calendar-timezone property.
                        $tzProp = '{' . self::NS_CALDAV . '}calendar-timezone';
                        $tzResult = $this->server->getProperties($calendarPath, [$tzProp]);
                        if (isset($tzResult[$tzProp])) {
                            // This property contains a VCALENDAR with a single
                            // VTIMEZONE.
                            $vtimezoneObj = VObject\Reader::read($tzResult[$tzProp]);
                            $timeZone = $vtimezoneObj->VTIMEZONE->getTimeZone();
                        } else {
                            // Defaulting to UTC.
                            $timeZone = new DateTimeZone('UTC');
                        }
                        $timeZones[$calendarPath] = $timeZone;
                    }

                    $vObject->expand($report->expand['start'], $report->expand['end'], $timeZones[$calendarPath]);
                }
                if ($needsJson) {
                    $objProps[200]['{' . self::NS_CALDAV . '}calendar-data'] = json_encode($vObject->jsonSerialize());
                } else {
                    $objProps[200]['{' . self::NS_CALDAV . '}calendar-data'] = $vObject->serialize();
                }
            }

            $propertyList[] = $objProps;

        }

        $prefer = $this->server->getHTTPPrefer();

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary', 'Brief,Prefer');
        $this->server->httpResponse->setBody($this->server->generateMultiStatus($propertyList, $prefer['return'] === 'minimal'));

    }

    /**
     * This function handles the calendar-query REPORT
     *
     * This report is used by clients to request calendar objects based on
     * complex conditions.
     *
     * @param Xml\Request\CalendarQueryReport $report
     * @return void
     */
    function calendarQueryReport($report) {

        $path = $this->server->getRequestUri();

        $needsJson = $report->contentType === 'application/calendar+json';

        $node = $this->server->tree->getNodeForPath($this->server->getRequestUri());
        $depth = $this->server->getHTTPDepth(0);

        // The default result is an empty array
        $result = [];

        $calendarTimeZone = null;
        if ($report->expand) {
            // We're expanding, and for that we need to figure out the
            // calendar's timezone.
            $tzProp = '{' . self::NS_CALDAV . '}calendar-timezone';
            $tzResult = $this->server->getProperties($path, [$tzProp]);
            if (isset($tzResult[$tzProp])) {
                // This property contains a VCALENDAR with a single
                // VTIMEZONE.
                $vtimezoneObj = VObject\Reader::read($tzResult[$tzProp]);
                $calendarTimeZone = $vtimezoneObj->VTIMEZONE->getTimeZone();
                unset($vtimezoneObj);
            } else {
                // Defaulting to UTC.
                $calendarTimeZone = new DateTimeZone('UTC');
            }
        }

        // The calendarobject was requested directly. In this case we handle
        // this locally.
        if ($depth == 0 && $node instanceof ICalendarObject) {

            $requestedCalendarData = true;
            $requestedProperties = $report->properties;

            if (!in_array('{urn:ietf:params:xml:ns:caldav}calendar-data', $requestedProperties)) {

                // We always retrieve calendar-data, as we need it for filtering.
                $requestedProperties[] = '{urn:ietf:params:xml:ns:caldav}calendar-data';

                // If calendar-data wasn't explicitly requested, we need to remove
                // it after processing.
                $requestedCalendarData = false;
            }

            $properties = $this->server->getPropertiesForPath(
                $path,
                $requestedProperties,
                0
            );

            // This array should have only 1 element, the first calendar
            // object.
            $properties = current($properties);

            // If there wasn't any calendar-data returned somehow, we ignore
            // this.
            if (isset($properties[200]['{urn:ietf:params:xml:ns:caldav}calendar-data'])) {

                $validator = new CalendarQueryValidator();

                $vObject = VObject\Reader::read($properties[200]['{urn:ietf:params:xml:ns:caldav}calendar-data']);
                if ($validator->validate($vObject, $report->filters)) {

                    // If the client didn't require the calendar-data property,
                    // we won't give it back.
                    if (!$requestedCalendarData) {
                        unset($properties[200]['{urn:ietf:params:xml:ns:caldav}calendar-data']);
                    } else {


                        if ($report->expand) {
                            $vObject->expand($report->expand['start'], $report->expand['end'], $calendarTimeZone);
                        }
                        if ($needsJson) {
                            $properties[200]['{' . self::NS_CALDAV . '}calendar-data'] = json_encode($vObject->jsonSerialize());
                        } elseif ($report->expand) {
                            $properties[200]['{' . self::NS_CALDAV . '}calendar-data'] = $vObject->serialize();
                        }
                    }

                    $result = [$properties];

                }

            }

        }

        if ($node instanceof ICalendarObjectContainer && $depth === 0) {

            if (strpos($this->server->httpRequest->getHeader('User-Agent'), 'MSFT-') === 0) {
                // Microsoft clients incorrectly supplied depth as 0, when it actually
                // should have set depth to 1. We're implementing a workaround here
                // to deal with this.
                //
                // This targets at least the following clients:
                //   Windows 10
                //   Windows Phone 8, 10
                $depth = 1;
            } else {
                throw new BadRequest('A calendar-query REPORT on a calendar with a Depth: 0 is undefined. Set Depth to 1');
            }

        }

        // If we're dealing with a calendar, the calendar itself is responsible
        // for the calendar-query.
        if ($node instanceof ICalendarObjectContainer && $depth == 1) {

            $nodePaths = $node->calendarQuery($report->filters);

            foreach ($nodePaths as $path) {

                list($properties) =
                    $this->server->getPropertiesForPath($this->server->getRequestUri() . '/' . $path, $report->properties);

                if (($needsJson || $report->expand)) {
                    $vObject = VObject\Reader::read($properties[200]['{' . self::NS_CALDAV . '}calendar-data']);

                    if ($report->expand) {
                        $vObject->expand($report->expand['start'], $report->expand['end'], $calendarTimeZone);
                    }

                    if ($needsJson) {
                        $properties[200]['{' . self::NS_CALDAV . '}calendar-data'] = json_encode($vObject->jsonSerialize());
                    } else {
                        $properties[200]['{' . self::NS_CALDAV . '}calendar-data'] = $vObject->serialize();
                    }
                }
                $result[] = $properties;

            }

        }

        $prefer = $this->server->getHTTPPrefer();

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary', 'Brief,Prefer');
        $this->server->httpResponse->setBody($this->server->generateMultiStatus($result, $prefer['return'] === 'minimal'));

    }

    /**
     * This method is responsible for parsing the request and generating the
     * response for the CALDAV:free-busy-query REPORT.
     *
     * @param Xml\Request\FreeBusyQueryReport $report
     * @return void
     */
    protected function freeBusyQueryReport(Xml\Request\FreeBusyQueryReport $report) {

        $uri = $this->server->getRequestUri();

        $acl = $this->server->getPlugin('acl');
        if ($acl) {
            $acl->checkPrivileges($uri, '{' . self::NS_CALDAV . '}read-free-busy');
        }

        $calendar = $this->server->tree->getNodeForPath($uri);
        if (!$calendar instanceof ICalendar) {
            throw new DAV\Exception\NotImplemented('The free-busy-query REPORT is only implemented on calendars');
        }

        $tzProp = '{' . self::NS_CALDAV . '}calendar-timezone';

        // Figuring out the default timezone for the calendar, for floating
        // times.
        $calendarProps = $this->server->getProperties($uri, [$tzProp]);

        if (isset($calendarProps[$tzProp])) {
            $vtimezoneObj = VObject\Reader::read($calendarProps[$tzProp]);
            $calendarTimeZone = $vtimezoneObj->VTIMEZONE->getTimeZone();
        } else {
            $calendarTimeZone = new DateTimeZone('UTC');
        }

        // Doing a calendar-query first, to make sure we get the most
        // performance.
        $urls = $calendar->calendarQuery([
            'name'         => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name'           => 'VEVENT',
                    'comp-filters'   => [],
                    'prop-filters'   => [],
                    'is-not-defined' => false,
                    'time-range'     => [
                        'start' => $report->start,
                        'end'   => $report->end,
                    ],
                ],
            ],
            'prop-filters'   => [],
            'is-not-defined' => false,
            'time-range'     => null,
        ]);

        $objects = array_map(function($url) use ($calendar) {
            $obj = $calendar->getChild($url)->get();
            return $obj;
        }, $urls);

        $generator = new VObject\FreeBusyGenerator();
        $generator->setObjects($objects);
        $generator->setTimeRange($report->start, $report->end);
        $generator->setTimeZone($calendarTimeZone);
        $result = $generator->getResult();
        $result = $result->serialize();

        $this->server->httpResponse->setStatus(200);
        $this->server->httpResponse->setHeader('Content-Type', 'text/calendar');
        $this->server->httpResponse->setHeader('Content-Length', strlen($result));
        $this->server->httpResponse->setBody($result);

    }

    /**
     * This method is triggered before a file gets updated with new content.
     *
     * This plugin uses this method to ensure that CalDAV objects receive
     * valid calendar data.
     *
     * @param string $path
     * @param DAV\IFile $node
     * @param resource $data
     * @param bool $modified Should be set to true, if this event handler
     *                       changed &$data.
     * @return void
     */
    function beforeWriteContent($path, DAV\IFile $node, &$data, &$modified) {

        if (!$node instanceof ICalendarObject)
            return;

        // We're onyl interested in ICalendarObject nodes that are inside of a
        // real calendar. This is to avoid triggering validation and scheduling
        // for non-calendars (such as an inbox).
        list($parent) = Uri\split($path);
        $parentNode = $this->server->tree->getNodeForPath($parent);

        if (!$parentNode instanceof ICalendar)
            return;

        $this->validateICalendar(
            $data,
            $path,
            $modified,
            $this->server->httpRequest,
            $this->server->httpResponse,
            false
        );

    }

    /**
     * This method is triggered before a new file is created.
     *
     * This plugin uses this method to ensure that newly created calendar
     * objects contain valid calendar data.
     *
     * @param string $path
     * @param resource $data
     * @param DAV\ICollection $parentNode
     * @param bool $modified Should be set to true, if this event handler
     *                       changed &$data.
     * @return void
     */
    function beforeCreateFile($path, &$data, DAV\ICollection $parentNode, &$modified) {

        if (!$parentNode instanceof ICalendar)
            return;

        $this->validateICalendar(
            $data,
            $path,
            $modified,
            $this->server->httpRequest,
            $this->server->httpResponse,
            true
        );

    }

    /**
     * Checks if the submitted iCalendar data is in fact, valid.
     *
     * An exception is thrown if it's not.
     *
     * @param resource|string $data
     * @param string $path
     * @param bool $modified Should be set to true, if this event handler
     *                       changed &$data.
     * @param RequestInterface $request The http request.
     * @param ResponseInterface $response The http response.
     * @param bool $isNew Is the item a new one, or an update.
     * @return void
     */
    protected function validateICalendar(&$data, $path, &$modified, RequestInterface $request, ResponseInterface $response, $isNew) {

        // If it's a stream, we convert it to a string first.
        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }

        $before = md5($data);
        // Converting the data to unicode, if needed.
        $data = DAV\StringUtil::ensureUTF8($data);

        if ($before !== md5($data)) $modified = true;

        try {

            // If the data starts with a [, we can reasonably assume we're dealing
            // with a jCal object.
            if (substr($data, 0, 1) === '[') {
                $vobj = VObject\Reader::readJson($data);

                // Converting $data back to iCalendar, as that's what we
                // technically support everywhere.
                $data = $vobj->serialize();
                $modified = true;
            } else {
                $vobj = VObject\Reader::read($data);
            }

        } catch (VObject\ParseException $e) {

            throw new DAV\Exception\UnsupportedMediaType('This resource only supports valid iCalendar 2.0 data. Parse error: ' . $e->getMessage());

        }

        if ($vobj->name !== 'VCALENDAR') {
            throw new DAV\Exception\UnsupportedMediaType('This collection can only support iCalendar objects.');
        }

        $sCCS = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';

        // Get the Supported Components for the target calendar
        list($parentPath) = Uri\split($path);
        $calendarProperties = $this->server->getProperties($parentPath, [$sCCS]);

        if (isset($calendarProperties[$sCCS])) {
            $supportedComponents = $calendarProperties[$sCCS]->getValue();
        } else {
            $supportedComponents = ['VJOURNAL', 'VTODO', 'VEVENT'];
        }

        $foundType = null;
        $foundUID = null;
        foreach ($vobj->getComponents() as $component) {
            switch ($component->name) {
                case 'VTIMEZONE' :
                    continue 2;
                case 'VEVENT' :
                case 'VTODO' :
                case 'VJOURNAL' :
                    if (is_null($foundType)) {
                        $foundType = $component->name;
                        if (!in_array($foundType, $supportedComponents)) {
                            throw new Exception\InvalidComponentType('This calendar only supports ' . implode(', ', $supportedComponents) . '. We found a ' . $foundType);
                        }
                        if (!isset($component->UID)) {
                            throw new DAV\Exception\BadRequest('Every ' . $component->name . ' component must have an UID');
                        }
                        $foundUID = (string)$component->UID;
                    } else {
                        if ($foundType !== $component->name) {
                            throw new DAV\Exception\BadRequest('A calendar object must only contain 1 component. We found a ' . $component->name . ' as well as a ' . $foundType);
                        }
                        if ($foundUID !== (string)$component->UID) {
                            throw new DAV\Exception\BadRequest('Every ' . $component->name . ' in this object must have identical UIDs');
                        }
                    }
                    break;
                default :
                    throw new DAV\Exception\BadRequest('You are not allowed to create components of type: ' . $component->name . ' here');

            }
        }
        if (!$foundType)
            throw new DAV\Exception\BadRequest('iCalendar object must contain at least 1 of VEVENT, VTODO or VJOURNAL');

        // We use an extra variable to allow event handles to tell us wether
        // the object was modified or not.
        //
        // This helps us determine if we need to re-serialize the object.
        $subModified = false;

        $this->server->emit(
            'calendarObjectChange',
            [
                $request,
                $response,
                $vobj,
                $parentPath,
                &$subModified,
                $isNew
            ]
        );

        if ($subModified) {
            // An event handler told us that it modified the object.
            $data = $vobj->serialize();

            // Using md5 to figure out if there was an *actual* change.
            if (!$modified && $before !== md5($data)) {
                $modified = true;
            }

        }

    }


    /**
     * This method is used to generate HTML output for the
     * DAV\Browser\Plugin. This allows us to generate an interface users
     * can use to create new calendars.
     *
     * @param DAV\INode $node
     * @param string $output
     * @return bool
     */
    function htmlActionsPanel(DAV\INode $node, &$output) {

        if (!$node instanceof CalendarHome)
            return;

        $output .= '<tr><td colspan="2"><form method="post" action="">
            <h3>Create new calendar</h3>
            <input type="hidden" name="sabreAction" value="mkcol" />
            <input type="hidden" name="resourceType" value="{DAV:}collection,{' . self::NS_CALDAV . '}calendar" />
            <label>Name (uri):</label> <input type="text" name="name" /><br />
            <label>Display name:</label> <input type="text" name="{DAV:}displayname" /><br />
            <input type="submit" value="create" />
            </form>
            </td></tr>';

        return false;

    }

    /**
     * This event is triggered after GET requests.
     *
     * This is used to transform data into jCal, if this was requested.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    function httpAfterGet(RequestInterface $request, ResponseInterface $response) {

        if (strpos($response->getHeader('Content-Type'), 'text/calendar') === false) {
            return;
        }

        $result = HTTP\Util::negotiate(
            $request->getHeader('Accept'),
            ['text/calendar', 'application/calendar+json']
        );

        if ($result !== 'application/calendar+json') {
            // Do nothing
            return;
        }

        // Transforming.
        $vobj = VObject\Reader::read($response->getBody());

        $jsonBody = json_encode($vobj->jsonSerialize());
        $response->setBody($jsonBody);

        $response->setHeader('Content-Type', 'application/calendar+json');
        $response->setHeader('Content-Length', strlen($jsonBody));

    }

    /**
     * Returns a bunch of meta-data about the plugin.
     *
     * Providing this information is optional, and is mainly displayed by the
     * Browser plugin.
     *
     * The description key in the returned array may contain html and will not
     * be sanitized.
     *
     * @return array
     */
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'Adds support for CalDAV (rfc4791)',
            'link'        => 'http://sabre.io/dav/caldav/',
        ];

    }

}
