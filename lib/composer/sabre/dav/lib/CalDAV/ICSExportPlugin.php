<?php

declare(strict_types=1);

namespace Sabre\CalDAV;

use DateTime;
use DateTimeZone;
use Sabre\DAV;
use Sabre\DAV\Exception\BadRequest;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject;

/**
 * ICS Exporter.
 *
 * This plugin adds the ability to export entire calendars as .ics files.
 * This is useful for clients that don't support CalDAV yet. They often do
 * support ics files.
 *
 * To use this, point a http client to a caldav calendar, and add ?expand to
 * the url.
 *
 * Further options that can be added to the url:
 *   start=123456789 - Only return events after the given unix timestamp
 *   end=123245679   - Only return events from before the given unix timestamp
 *   expand=1        - Strip timezone information and expand recurring events.
 *                     If you'd like to expand, you _must_ also specify start
 *                     and end.
 *
 * By default this plugin returns data in the text/calendar format (iCalendar
 * 2.0). If you'd like to receive jCal data instead, you can use an Accept
 * header:
 *
 * Accept: application/calendar+json
 *
 * Alternatively, you can also specify this in the url using
 * accept=application/calendar+json, or accept=jcal for short. If the url
 * parameter and Accept header is specified, the url parameter wins.
 *
 * Note that specifying a start or end data implies that only events will be
 * returned. VTODO and VJOURNAL will be stripped.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ICSExportPlugin extends DAV\ServerPlugin
{
    /**
     * Reference to Server class.
     *
     * @var \Sabre\DAV\Server
     */
    protected $server;

    /**
     * Initializes the plugin and registers event handlers.
     *
     * @param \Sabre\DAV\Server $server
     */
    public function initialize(DAV\Server $server)
    {
        $this->server = $server;
        $server->on('method:GET', [$this, 'httpGet'], 90);
        $server->on('browserButtonActions', function ($path, $node, &$actions) {
            if ($node instanceof ICalendar) {
                $actions .= '<a href="'.htmlspecialchars($path, ENT_QUOTES, 'UTF-8').'?export"><span class="oi" data-glyph="calendar"></span></a>';
            }
        });
    }

    /**
     * Intercepts GET requests on calendar urls ending with ?export.
     *
     * @throws BadRequest
     * @throws DAV\Exception\NotFound
     * @throws VObject\InvalidDataException
     *
     * @return bool
     */
    public function httpGet(RequestInterface $request, ResponseInterface $response)
    {
        $queryParams = $request->getQueryParameters();
        if (!array_key_exists('export', $queryParams)) {
            return;
        }

        $path = $request->getPath();

        $node = $this->server->getProperties($path, [
            '{DAV:}resourcetype',
            '{DAV:}displayname',
            '{http://sabredav.org/ns}sync-token',
            '{DAV:}sync-token',
            '{http://apple.com/ns/ical/}calendar-color',
        ]);

        if (!isset($node['{DAV:}resourcetype']) || !$node['{DAV:}resourcetype']->is('{'.Plugin::NS_CALDAV.'}calendar')) {
            return;
        }
        // Marking the transactionType, for logging purposes.
        $this->server->transactionType = 'get-calendar-export';

        $properties = $node;

        $start = null;
        $end = null;
        $expand = false;
        $componentType = false;
        if (isset($queryParams['start'])) {
            if (!ctype_digit($queryParams['start'])) {
                throw new BadRequest('The start= parameter must contain a unix timestamp');
            }
            $start = DateTime::createFromFormat('U', $queryParams['start']);
        }
        if (isset($queryParams['end'])) {
            if (!ctype_digit($queryParams['end'])) {
                throw new BadRequest('The end= parameter must contain a unix timestamp');
            }
            $end = DateTime::createFromFormat('U', $queryParams['end']);
        }
        if (isset($queryParams['expand']) && (bool) $queryParams['expand']) {
            if (!$start || !$end) {
                throw new BadRequest('If you\'d like to expand recurrences, you must specify both a start= and end= parameter.');
            }
            $expand = true;
            $componentType = 'VEVENT';
        }
        if (isset($queryParams['componentType'])) {
            if (!in_array($queryParams['componentType'], ['VEVENT', 'VTODO', 'VJOURNAL'])) {
                throw new BadRequest('You are not allowed to search for components of type: '.$queryParams['componentType'].' here');
            }
            $componentType = $queryParams['componentType'];
        }

        $format = \Sabre\HTTP\negotiateContentType(
            $request->getHeader('Accept'),
            [
                'text/calendar',
                'application/calendar+json',
            ]
        );

        if (isset($queryParams['accept'])) {
            if ('application/calendar+json' === $queryParams['accept'] || 'jcal' === $queryParams['accept']) {
                $format = 'application/calendar+json';
            }
        }
        if (!$format) {
            $format = 'text/calendar';
        }

        $this->generateResponse($path, $start, $end, $expand, $componentType, $format, $properties, $response);

        // Returning false to break the event chain
        return false;
    }

    /**
     * This method is responsible for generating the actual, full response.
     *
     * @param string        $path
     * @param DateTime|null $start
     * @param DateTime|null $end
     * @param bool          $expand
     * @param string        $componentType
     * @param string        $format
     * @param array         $properties
     *
     * @throws DAV\Exception\NotFound
     * @throws VObject\InvalidDataException
     */
    protected function generateResponse($path, $start, $end, $expand, $componentType, $format, $properties, ResponseInterface $response)
    {
        $calDataProp = '{'.Plugin::NS_CALDAV.'}calendar-data';
        $calendarNode = $this->server->tree->getNodeForPath($path);

        $blobs = [];
        if ($start || $end || $componentType) {
            // If there was a start or end filter, we need to enlist
            // calendarQuery for speed.
            $queryResult = $calendarNode->calendarQuery([
                'name' => 'VCALENDAR',
                'comp-filters' => [
                    [
                        'name' => $componentType,
                        'comp-filters' => [],
                        'prop-filters' => [],
                        'is-not-defined' => false,
                        'time-range' => [
                            'start' => $start,
                            'end' => $end,
                        ],
                    ],
                ],
                'prop-filters' => [],
                'is-not-defined' => false,
                'time-range' => null,
            ]);

            // queryResult is just a list of base urls. We need to prefix the
            // calendar path.
            $queryResult = array_map(
                function ($item) use ($path) {
                    return $path.'/'.$item;
                },
                $queryResult
            );
            $nodes = $this->server->getPropertiesForMultiplePaths($queryResult, [$calDataProp]);
            unset($queryResult);
        } else {
            $nodes = $this->server->getPropertiesForPath($path, [$calDataProp], 1);
        }

        // Flattening the arrays
        foreach ($nodes as $node) {
            if (isset($node[200][$calDataProp])) {
                $blobs[$node['href']] = $node[200][$calDataProp];
            }
        }
        unset($nodes);

        $mergedCalendar = $this->mergeObjects(
            $properties,
            $blobs
        );

        if ($expand) {
            $calendarTimeZone = null;
            // We're expanding, and for that we need to figure out the
            // calendar's timezone.
            $tzProp = '{'.Plugin::NS_CALDAV.'}calendar-timezone';
            $tzResult = $this->server->getProperties($path, [$tzProp]);
            if (isset($tzResult[$tzProp])) {
                // This property contains a VCALENDAR with a single
                // VTIMEZONE.
                $vtimezoneObj = VObject\Reader::read($tzResult[$tzProp]);
                $calendarTimeZone = $vtimezoneObj->VTIMEZONE->getTimeZone();
                // Destroy circular references to PHP will GC the object.
                $vtimezoneObj->destroy();
                unset($vtimezoneObj);
            } else {
                // Defaulting to UTC.
                $calendarTimeZone = new DateTimeZone('UTC');
            }

            $mergedCalendar = $mergedCalendar->expand($start, $end, $calendarTimeZone);
        }

        $filenameExtension = '.ics';

        switch ($format) {
            case 'text/calendar':
                $mergedCalendar = $mergedCalendar->serialize();
                $filenameExtension = '.ics';
                break;
            case 'application/calendar+json':
                $mergedCalendar = json_encode($mergedCalendar->jsonSerialize());
                $filenameExtension = '.json';
                break;
        }

        $filename = preg_replace(
            '/[^a-zA-Z0-9-_ ]/um',
            '',
            $calendarNode->getName()
        );
        $filename .= '-'.date('Y-m-d').$filenameExtension;

        $response->setHeader('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->setHeader('Content-Type', $format);

        $response->setStatus(200);
        $response->setBody($mergedCalendar);
    }

    /**
     * Merges all calendar objects, and builds one big iCalendar blob.
     *
     * @param array $properties Some CalDAV properties
     *
     * @return VObject\Component\VCalendar
     */
    public function mergeObjects(array $properties, array $inputObjects)
    {
        $calendar = new VObject\Component\VCalendar();
        $calendar->VERSION = '2.0';
        if (DAV\Server::$exposeVersion) {
            $calendar->PRODID = '-//SabreDAV//SabreDAV '.DAV\Version::VERSION.'//EN';
        } else {
            $calendar->PRODID = '-//SabreDAV//SabreDAV//EN';
        }
        if (isset($properties['{DAV:}displayname'])) {
            $calendar->{'X-WR-CALNAME'} = $properties['{DAV:}displayname'];
        }
        if (isset($properties['{http://apple.com/ns/ical/}calendar-color'])) {
            $calendar->{'X-APPLE-CALENDAR-COLOR'} = $properties['{http://apple.com/ns/ical/}calendar-color'];
        }

        $collectedTimezones = [];

        $timezones = [];
        $objects = [];

        foreach ($inputObjects as $href => $inputObject) {
            $nodeComp = VObject\Reader::read($inputObject);

            foreach ($nodeComp->children() as $child) {
                switch ($child->name) {
                    case 'VEVENT':
                    case 'VTODO':
                    case 'VJOURNAL':
                        $objects[] = clone $child;
                        break;

                    // VTIMEZONE is special, because we need to filter out the duplicates
                    case 'VTIMEZONE':
                        // Naively just checking tzid.
                        if (in_array((string) $child->TZID, $collectedTimezones)) {
                            break;
                        }

                        $timezones[] = clone $child;
                        $collectedTimezones[] = $child->TZID;
                        break;
                }
            }
            // Destroy circular references to PHP will GC the object.
            $nodeComp->destroy();
            unset($nodeComp);
        }

        foreach ($timezones as $tz) {
            $calendar->add($tz);
        }
        foreach ($objects as $obj) {
            $calendar->add($obj);
        }

        return $calendar;
    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using \Sabre\DAV\Server::getPlugin
     *
     * @return string
     */
    public function getPluginName()
    {
        return 'ics-export';
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
    public function getPluginInfo()
    {
        return [
            'name' => $this->getPluginName(),
            'description' => 'Adds the ability to export CalDAV calendars as a single iCalendar file.',
            'link' => 'http://sabre.io/dav/ics-export-plugin/',
        ];
    }
}
