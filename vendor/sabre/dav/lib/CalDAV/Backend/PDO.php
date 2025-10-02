<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Xml\Element\Sharee;
use Sabre\VObject;

/**
 * PDO CalDAV backend.
 *
 * This backend is used to store calendar-data in a PDO database, such as
 * sqlite or MySQL
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class PDO extends AbstractBackend implements SyncSupport, SubscriptionSupport, SchedulingSupport, SharingSupport
{
    /**
     * We need to specify a max date, because we need to stop *somewhere*.
     *
     * On 32 bit system the maximum for a signed integer is 2147483647, so
     * MAX_DATE cannot be higher than date('Y-m-d', 2147483647) which results
     * in 2038-01-19 to avoid problems when the date is converted
     * to a unix timestamp.
     */
    const MAX_DATE = '2038-01-01';

    /**
     * pdo.
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * The table name that will be used for calendars.
     *
     * @var string
     */
    public $calendarTableName = 'calendars';

    /**
     * The table name that will be used for calendars instances.
     *
     * A single calendar can have multiple instances, if the calendar is
     * shared.
     *
     * @var string
     */
    public $calendarInstancesTableName = 'calendarinstances';

    /**
     * The table name that will be used for calendar objects.
     *
     * @var string
     */
    public $calendarObjectTableName = 'calendarobjects';

    /**
     * The table name that will be used for tracking changes in calendars.
     *
     * @var string
     */
    public $calendarChangesTableName = 'calendarchanges';

    /**
     * The table name that will be used inbox items.
     *
     * @var string
     */
    public $schedulingObjectTableName = 'schedulingobjects';

    /**
     * The table name that will be used for calendar subscriptions.
     *
     * @var string
     */
    public $calendarSubscriptionsTableName = 'calendarsubscriptions';

    /**
     * List of CalDAV properties, and how they map to database fieldnames
     * Add your own properties by simply adding on to this array.
     *
     * Note that only string-based properties are supported here.
     *
     * @var array
     */
    public $propertyMap = [
        '{DAV:}displayname' => 'displayname',
        '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'description',
        '{urn:ietf:params:xml:ns:caldav}calendar-timezone' => 'timezone',
        '{http://apple.com/ns/ical/}calendar-order' => 'calendarorder',
        '{http://apple.com/ns/ical/}calendar-color' => 'calendarcolor',
    ];

    /**
     * List of subscription properties, and how they map to database fieldnames.
     *
     * @var array
     */
    public $subscriptionPropertyMap = [
        '{DAV:}displayname' => 'displayname',
        '{http://apple.com/ns/ical/}refreshrate' => 'refreshrate',
        '{http://apple.com/ns/ical/}calendar-order' => 'calendarorder',
        '{http://apple.com/ns/ical/}calendar-color' => 'calendarcolor',
        '{http://calendarserver.org/ns/}subscribed-strip-todos' => 'striptodos',
        '{http://calendarserver.org/ns/}subscribed-strip-alarms' => 'stripalarms',
        '{http://calendarserver.org/ns/}subscribed-strip-attachments' => 'stripattachments',
    ];

    /**
     * Creates the backend.
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Returns a list of calendars for a principal.
     *
     * Every project is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    calendar. This can be the same as the uri or a database key.
     *  * uri. This is just the 'base uri' or 'filename' of the calendar.
     *  * principaluri. The owner of the calendar. Almost always the same as
     *    principalUri passed to this method.
     *
     * Furthermore it can contain webdav properties in clark notation. A very
     * common one is '{DAV:}displayname'.
     *
     * Many clients also require:
     * {urn:ietf:params:xml:ns:caldav}supported-calendar-component-set
     * For this property, you can just return an instance of
     * Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet.
     *
     * If you return {http://sabredav.org/ns}read-only and set the value to 1,
     * ACL will automatically be put in read-only mode.
     *
     * @param string $principalUri
     *
     * @return array
     */
    public function getCalendarsForUser($principalUri)
    {
        $fields = array_values($this->propertyMap);
        $fields[] = 'calendarid';
        $fields[] = 'uri';
        $fields[] = 'synctoken';
        $fields[] = 'components';
        $fields[] = 'principaluri';
        $fields[] = 'transparent';
        $fields[] = 'access';

        // Making fields a comma-delimited list
        $fields = implode(', ', $fields);
        $stmt = $this->pdo->prepare(<<<SQL
SELECT {$this->calendarInstancesTableName}.id as id, $fields FROM {$this->calendarInstancesTableName}
    LEFT JOIN {$this->calendarTableName} ON
        {$this->calendarInstancesTableName}.calendarid = {$this->calendarTableName}.id
WHERE principaluri = ? ORDER BY calendarorder ASC
SQL
        );
        $stmt->execute([$principalUri]);

        $calendars = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $components = [];
            if ($row['components']) {
                $components = explode(',', $row['components']);
            }

            $calendar = [
                'id' => [(int) $row['calendarid'], (int) $row['id']],
                'uri' => $row['uri'],
                'principaluri' => $row['principaluri'],
                '{'.CalDAV\Plugin::NS_CALENDARSERVER.'}getctag' => 'http://sabre.io/ns/sync/'.($row['synctoken'] ? $row['synctoken'] : '0'),
                '{http://sabredav.org/ns}sync-token' => $row['synctoken'] ? $row['synctoken'] : '0',
                '{'.CalDAV\Plugin::NS_CALDAV.'}supported-calendar-component-set' => new CalDAV\Xml\Property\SupportedCalendarComponentSet($components),
                '{'.CalDAV\Plugin::NS_CALDAV.'}schedule-calendar-transp' => new CalDAV\Xml\Property\ScheduleCalendarTransp($row['transparent'] ? 'transparent' : 'opaque'),
                'share-resource-uri' => '/ns/share/'.$row['calendarid'],
            ];

            $calendar['share-access'] = (int) $row['access'];
            // 1 = owner, 2 = readonly, 3 = readwrite
            if ($row['access'] > 1) {
                // We need to find more information about the original owner.
                //$stmt2 = $this->pdo->prepare('SELECT principaluri FROM ' . $this->calendarInstancesTableName . ' WHERE access = 1 AND id = ?');
                //$stmt2->execute([$row['id']]);

                // read-only is for backwards compatibility. Might go away in
                // the future.
                $calendar['read-only'] = \Sabre\DAV\Sharing\Plugin::ACCESS_READ === (int) $row['access'];
            }

            foreach ($this->propertyMap as $xmlName => $dbName) {
                $calendar[$xmlName] = $row[$dbName];
            }

            $calendars[] = $calendar;
        }

        return $calendars;
    }

    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used
     * to reference this calendar in other methods, such as updateCalendar.
     *
     * @param string $principalUri
     * @param string $calendarUri
     *
     * @return string
     */
    public function createCalendar($principalUri, $calendarUri, array $properties)
    {
        $fieldNames = [
            'principaluri',
            'uri',
            'transparent',
            'calendarid',
        ];
        $values = [
            ':principaluri' => $principalUri,
            ':uri' => $calendarUri,
            ':transparent' => 0,
        ];

        $sccs = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';
        if (!isset($properties[$sccs])) {
            // Default value
            $components = 'VEVENT,VTODO';
        } else {
            if (!($properties[$sccs] instanceof CalDAV\Xml\Property\SupportedCalendarComponentSet)) {
                throw new DAV\Exception('The '.$sccs.' property must be of type: \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet');
            }
            $components = implode(',', $properties[$sccs]->getValue());
        }
        $transp = '{'.CalDAV\Plugin::NS_CALDAV.'}schedule-calendar-transp';
        if (isset($properties[$transp])) {
            $values[':transparent'] = 'transparent' === $properties[$transp]->getValue() ? 1 : 0;
        }
        $stmt = $this->pdo->prepare('INSERT INTO '.$this->calendarTableName.' (synctoken, components) VALUES (1, ?)');
        $stmt->execute([$components]);

        $calendarId = $this->pdo->lastInsertId(
            $this->calendarTableName.'_id_seq'
        );

        $values[':calendarid'] = $calendarId;

        foreach ($this->propertyMap as $xmlName => $dbName) {
            if (isset($properties[$xmlName])) {
                $values[':'.$dbName] = $properties[$xmlName];
                $fieldNames[] = $dbName;
            }
        }

        $stmt = $this->pdo->prepare('INSERT INTO '.$this->calendarInstancesTableName.' ('.implode(', ', $fieldNames).') VALUES ('.implode(', ', array_keys($values)).')');

        $stmt->execute($values);

        return [
            $calendarId,
            $this->pdo->lastInsertId($this->calendarInstancesTableName.'_id_seq'),
        ];
    }

    /**
     * Updates properties for a calendar.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param mixed $calendarId
     */
    public function updateCalendar($calendarId, PropPatch $propPatch)
    {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $supportedProperties = array_keys($this->propertyMap);
        $supportedProperties[] = '{'.CalDAV\Plugin::NS_CALDAV.'}schedule-calendar-transp';

        $propPatch->handle($supportedProperties, function ($mutations) use ($calendarId, $instanceId) {
            $newValues = [];
            foreach ($mutations as $propertyName => $propertyValue) {
                switch ($propertyName) {
                    case '{'.CalDAV\Plugin::NS_CALDAV.'}schedule-calendar-transp':
                        $fieldName = 'transparent';
                        $newValues[$fieldName] = 'transparent' === $propertyValue->getValue();
                        break;
                    default:
                        $fieldName = $this->propertyMap[$propertyName];
                        $newValues[$fieldName] = $propertyValue;
                        break;
                }
            }
            $valuesSql = [];
            foreach ($newValues as $fieldName => $value) {
                $valuesSql[] = $fieldName.' = ?';
            }

            $stmt = $this->pdo->prepare('UPDATE '.$this->calendarInstancesTableName.' SET '.implode(', ', $valuesSql).' WHERE id = ?');
            $newValues['id'] = $instanceId;
            $stmt->execute(array_values($newValues));

            $this->addChange($calendarId, '', 2);

            return true;
        });
    }

    /**
     * Delete a calendar and all it's objects.
     *
     * @param mixed $calendarId
     */
    public function deleteCalendar($calendarId)
    {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $stmt = $this->pdo->prepare('SELECT access FROM '.$this->calendarInstancesTableName.' where id = ?');
        $stmt->execute([$instanceId]);
        $access = (int) $stmt->fetchColumn();

        if (\Sabre\DAV\Sharing\Plugin::ACCESS_SHAREDOWNER === $access) {
            /**
             * If the user is the owner of the calendar, we delete all data and all
             * instances.
             **/
            $stmt = $this->pdo->prepare('DELETE FROM '.$this->calendarObjectTableName.' WHERE calendarid = ?');
            $stmt->execute([$calendarId]);

            $stmt = $this->pdo->prepare('DELETE FROM '.$this->calendarChangesTableName.' WHERE calendarid = ?');
            $stmt->execute([$calendarId]);

            $stmt = $this->pdo->prepare('DELETE FROM '.$this->calendarInstancesTableName.' WHERE calendarid = ?');
            $stmt->execute([$calendarId]);

            $stmt = $this->pdo->prepare('DELETE FROM '.$this->calendarTableName.' WHERE id = ?');
            $stmt->execute([$calendarId]);
        } else {
            /**
             * If it was an instance of a shared calendar, we only delete that
             * instance.
             */
            $stmt = $this->pdo->prepare('DELETE FROM '.$this->calendarInstancesTableName.' WHERE id = ?');
            $stmt->execute([$instanceId]);
        }
    }

    /**
     * Returns all calendar objects within a calendar.
     *
     * Every item contains an array with the following keys:
     *   * calendardata - The iCalendar-compatible calendar data
     *   * uri - a unique key which will be used to construct the uri. This can
     *     be any arbitrary string, but making sure it ends with '.ics' is a
     *     good idea. This is only the basename, or filename, not the full
     *     path.
     *   * lastmodified - a timestamp of the last modification time
     *   * etag - An arbitrary string, surrounded by double-quotes. (e.g.:
     *   '  "abcdef"')
     *   * size - The size of the calendar objects, in bytes.
     *   * component - optional, a string containing the type of object, such
     *     as 'vevent' or 'vtodo'. If specified, this will be used to populate
     *     the Content-Type header.
     *
     * Note that the etag is optional, but it's highly encouraged to return for
     * speed reasons.
     *
     * The calendardata is also optional. If it's not returned
     * 'getCalendarObject' will be called later, which *is* expected to return
     * calendardata.
     *
     * If neither etag or size are specified, the calendardata will be
     * used/fetched to determine these numbers. If both are specified the
     * amount of times this is needed is reduced by a great degree.
     *
     * @param mixed $calendarId
     *
     * @return array
     */
    public function getCalendarObjects($calendarId)
    {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $stmt = $this->pdo->prepare('SELECT id, uri, lastmodified, etag, calendarid, size, componenttype FROM '.$this->calendarObjectTableName.' WHERE calendarid = ?');
        $stmt->execute([$calendarId]);

        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[] = [
                'id' => $row['id'],
                'uri' => $row['uri'],
                'lastmodified' => (int) $row['lastmodified'],
                'etag' => '"'.$row['etag'].'"',
                'size' => (int) $row['size'],
                'component' => strtolower($row['componenttype']),
            ];
        }

        return $result;
    }

    /**
     * Returns information from a single calendar object, based on it's object
     * uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * The returned array must have the same keys as getCalendarObjects. The
     * 'calendardata' object is required here though, while it's not required
     * for getCalendarObjects.
     *
     * This method must return null if the object did not exist.
     *
     * @param mixed  $calendarId
     * @param string $objectUri
     *
     * @return array|null
     */
    public function getCalendarObject($calendarId, $objectUri)
    {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $stmt = $this->pdo->prepare('SELECT id, uri, lastmodified, etag, calendarid, size, calendardata, componenttype FROM '.$this->calendarObjectTableName.' WHERE calendarid = ? AND uri = ?');
        $stmt->execute([$calendarId, $objectUri]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return [
            'id' => $row['id'],
            'uri' => $row['uri'],
            'lastmodified' => (int) $row['lastmodified'],
            'etag' => '"'.$row['etag'].'"',
            'size' => (int) $row['size'],
            'calendardata' => $row['calendardata'],
            'component' => strtolower($row['componenttype']),
         ];
    }

    /**
     * Returns a list of calendar objects.
     *
     * This method should work identical to getCalendarObject, but instead
     * return all the calendar objects in the list as an array.
     *
     * If the backend supports this, it may allow for some speed-ups.
     *
     * @param mixed $calendarId
     *
     * @return array
     */
    public function getMultipleCalendarObjects($calendarId, array $uris)
    {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $result = [];
        foreach (array_chunk($uris, 900) as $chunk) {
            $query = 'SELECT id, uri, lastmodified, etag, calendarid, size, calendardata, componenttype FROM '.$this->calendarObjectTableName.' WHERE calendarid = ? AND uri IN (';
            // Inserting a whole bunch of question marks
            $query .= implode(',', array_fill(0, count($chunk), '?'));
            $query .= ')';

            $stmt = $this->pdo->prepare($query);
            $stmt->execute(array_merge([$calendarId], $chunk));

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $result[] = [
                    'id' => $row['id'],
                    'uri' => $row['uri'],
                    'lastmodified' => (int) $row['lastmodified'],
                    'etag' => '"'.$row['etag'].'"',
                    'size' => (int) $row['size'],
                    'calendardata' => $row['calendardata'],
                    'component' => strtolower($row['componenttype']),
                ];
            }
        }

        return $result;
    }

    /**
     * Creates a new calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed  $calendarId
     * @param string $objectUri
     * @param string $calendarData
     *
     * @return string|null
     */
    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $extraData = $this->getDenormalizedData($calendarData);

        $stmt = $this->pdo->prepare('INSERT INTO '.$this->calendarObjectTableName.' (calendarid, uri, calendardata, lastmodified, etag, size, componenttype, firstoccurence, lastoccurence, uid) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $calendarId,
            $objectUri,
            $calendarData,
            time(),
            $extraData['etag'],
            $extraData['size'],
            $extraData['componentType'],
            $extraData['firstOccurence'],
            $extraData['lastOccurence'],
            $extraData['uid'],
        ]);
        $this->addChange($calendarId, $objectUri, 1);

        return '"'.$extraData['etag'].'"';
    }

    /**
     * Updates an existing calendarobject, based on it's uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed  $calendarId
     * @param string $objectUri
     * @param string $calendarData
     *
     * @return string|null
     */
    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $extraData = $this->getDenormalizedData($calendarData);

        $stmt = $this->pdo->prepare('UPDATE '.$this->calendarObjectTableName.' SET calendardata = ?, lastmodified = ?, etag = ?, size = ?, componenttype = ?, firstoccurence = ?, lastoccurence = ?, uid = ? WHERE calendarid = ? AND uri = ?');
        $stmt->execute([$calendarData, time(), $extraData['etag'], $extraData['size'], $extraData['componentType'], $extraData['firstOccurence'], $extraData['lastOccurence'], $extraData['uid'], $calendarId, $objectUri]);

        $this->addChange($calendarId, $objectUri, 2);

        return '"'.$extraData['etag'].'"';
    }

    /**
     * Parses some information from calendar objects, used for optimized
     * calendar-queries.
     *
     * Returns an array with the following keys:
     *   * etag - An md5 checksum of the object without the quotes.
     *   * size - Size of the object in bytes
     *   * componentType - VEVENT, VTODO or VJOURNAL
     *   * firstOccurence
     *   * lastOccurence
     *   * uid - value of the UID property
     *
     * @param string $calendarData
     *
     * @return array
     */
    protected function getDenormalizedData($calendarData)
    {
        $vObject = VObject\Reader::read($calendarData);
        $componentType = null;
        $component = null;
        $firstOccurence = null;
        $lastOccurence = null;
        $uid = null;
        foreach ($vObject->getComponents() as $component) {
            if ('VTIMEZONE' !== $component->name) {
                $componentType = $component->name;
                $uid = (string) $component->UID;
                break;
            }
        }
        if (!$componentType) {
            throw new \Sabre\DAV\Exception\BadRequest('Calendar objects must have a VJOURNAL, VEVENT or VTODO component');
        }
        if ('VEVENT' === $componentType) {
            $firstOccurence = $component->DTSTART->getDateTime()->getTimeStamp();
            // Finding the last occurence is a bit harder
            if (!isset($component->RRULE)) {
                if (isset($component->DTEND)) {
                    $lastOccurence = $component->DTEND->getDateTime()->getTimeStamp();
                } elseif (isset($component->DURATION)) {
                    $endDate = clone $component->DTSTART->getDateTime();
                    $endDate = $endDate->add(VObject\DateTimeParser::parse($component->DURATION->getValue()));
                    $lastOccurence = $endDate->getTimeStamp();
                } elseif (!$component->DTSTART->hasTime()) {
                    $endDate = clone $component->DTSTART->getDateTime();
                    $endDate = $endDate->modify('+1 day');
                    $lastOccurence = $endDate->getTimeStamp();
                } else {
                    $lastOccurence = $firstOccurence;
                }
            } else {
                $it = new VObject\Recur\EventIterator($vObject, (string) $component->UID);
                $maxDate = new \DateTime(self::MAX_DATE);
                if ($it->isInfinite()) {
                    $lastOccurence = $maxDate->getTimeStamp();
                } else {
                    $end = $it->getDtEnd();
                    while ($it->valid() && $end < $maxDate) {
                        $end = $it->getDtEnd();
                        $it->next();
                    }
                    $lastOccurence = $end->getTimeStamp();
                }
            }

            // Ensure Occurence values are positive
            if ($firstOccurence < 0) {
                $firstOccurence = 0;
            }
            if ($lastOccurence < 0) {
                $lastOccurence = 0;
            }
        }

        // Destroy circular references to PHP will GC the object.
        $vObject->destroy();

        return [
            'etag' => md5($calendarData),
            'size' => strlen($calendarData),
            'componentType' => $componentType,
            'firstOccurence' => $firstOccurence,
            'lastOccurence' => $lastOccurence,
            'uid' => $uid,
        ];
    }

    /**
     * Deletes an existing calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * @param mixed  $calendarId
     * @param string $objectUri
     */
    public function deleteCalendarObject($calendarId, $objectUri)
    {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $stmt = $this->pdo->prepare('DELETE FROM '.$this->calendarObjectTableName.' WHERE calendarid = ? AND uri = ?');
        $stmt->execute([$calendarId, $objectUri]);

        $this->addChange($calendarId, $objectUri, 3);
    }

    /**
     * Performs a calendar-query on the contents of this calendar.
     *
     * The calendar-query is defined in RFC4791 : CalDAV. Using the
     * calendar-query it is possible for a client to request a specific set of
     * object, based on contents of iCalendar properties, date-ranges and
     * iCalendar component types (VTODO, VEVENT).
     *
     * This method should just return a list of (relative) urls that match this
     * query.
     *
     * The list of filters are specified as an array. The exact array is
     * documented by \Sabre\CalDAV\CalendarQueryParser.
     *
     * Note that it is extremely likely that getCalendarObject for every path
     * returned from this method will be called almost immediately after. You
     * may want to anticipate this to speed up these requests.
     *
     * This method provides a default implementation, which parses *all* the
     * iCalendar objects in the specified calendar.
     *
     * This default may well be good enough for personal use, and calendars
     * that aren't very large. But if you anticipate high usage, big calendars
     * or high loads, you are strongly advised to optimize certain paths.
     *
     * The best way to do so is override this method and to optimize
     * specifically for 'common filters'.
     *
     * Requests that are extremely common are:
     *   * requests for just VEVENTS
     *   * requests for just VTODO
     *   * requests with a time-range-filter on a VEVENT.
     *
     * ..and combinations of these requests. It may not be worth it to try to
     * handle every possible situation and just rely on the (relatively
     * easy to use) CalendarQueryValidator to handle the rest.
     *
     * Note that especially time-range-filters may be difficult to parse. A
     * time-range filter specified on a VEVENT must for instance also handle
     * recurrence rules correctly.
     * A good example of how to interpret all these filters can also simply
     * be found in \Sabre\CalDAV\CalendarQueryFilter. This class is as correct
     * as possible, so it gives you a good idea on what type of stuff you need
     * to think of.
     *
     * This specific implementation (for the PDO) backend optimizes filters on
     * specific components, and VEVENT time-ranges.
     *
     * @param mixed $calendarId
     *
     * @return array
     */
    public function calendarQuery($calendarId, array $filters)
    {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $componentType = null;
        $requirePostFilter = true;
        $timeRange = null;

        // if no filters were specified, we don't need to filter after a query
        if (!$filters['prop-filters'] && !$filters['comp-filters']) {
            $requirePostFilter = false;
        }

        // Figuring out if there's a component filter
        if (count($filters['comp-filters']) > 0 && !$filters['comp-filters'][0]['is-not-defined']) {
            $componentType = $filters['comp-filters'][0]['name'];

            // Checking if we need post-filters
            $has_time_range = array_key_exists('time-range', $filters['comp-filters'][0]) && $filters['comp-filters'][0]['time-range'];
            if (!$filters['prop-filters'] && !$filters['comp-filters'][0]['comp-filters'] && !$has_time_range && !$filters['comp-filters'][0]['prop-filters']) {
                $requirePostFilter = false;
            }
            // There was a time-range filter
            if ('VEVENT' == $componentType && $has_time_range) {
                $timeRange = $filters['comp-filters'][0]['time-range'];

                // If start time OR the end time is not specified, we can do a
                // 100% accurate mysql query.
                if (!$filters['prop-filters'] && !$filters['comp-filters'][0]['comp-filters'] && !$filters['comp-filters'][0]['prop-filters'] && $timeRange) {
                    if ((array_key_exists('start', $timeRange) && !$timeRange['start']) || (array_key_exists('end', $timeRange) && !$timeRange['end'])) {
                        $requirePostFilter = false;
                    }
                }
            }
        }

        if ($requirePostFilter) {
            $query = 'SELECT uri, calendardata FROM '.$this->calendarObjectTableName.' WHERE calendarid = :calendarid';
        } else {
            $query = 'SELECT uri FROM '.$this->calendarObjectTableName.' WHERE calendarid = :calendarid';
        }

        $values = [
            'calendarid' => $calendarId,
        ];

        if ($componentType) {
            $query .= ' AND componenttype = :componenttype';
            $values['componenttype'] = $componentType;
        }

        if ($timeRange && array_key_exists('start', $timeRange) && $timeRange['start']) {
            $query .= ' AND lastoccurence > :startdate';
            $values['startdate'] = $timeRange['start']->getTimeStamp();
        }
        if ($timeRange && array_key_exists('end', $timeRange) && $timeRange['end']) {
            $query .= ' AND firstoccurence < :enddate';
            $values['enddate'] = $timeRange['end']->getTimeStamp();
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($values);

        $result = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($requirePostFilter) {
                if (!$this->validateFilterForObject($row, $filters)) {
                    continue;
                }
            }
            $result[] = $row['uri'];
        }

        return $result;
    }

    /**
     * Searches through all of a users calendars and calendar objects to find
     * an object with a specific UID.
     *
     * This method should return the path to this object, relative to the
     * calendar home, so this path usually only contains two parts:
     *
     * calendarpath/objectpath.ics
     *
     * If the uid is not found, return null.
     *
     * This method should only consider * objects that the principal owns, so
     * any calendars owned by other principals that also appear in this
     * collection should be ignored.
     *
     * @param string $principalUri
     * @param string $uid
     *
     * @return string|null
     */
    public function getCalendarObjectByUID($principalUri, $uid)
    {
        $query = <<<SQL
SELECT
    calendar_instances.uri AS calendaruri, calendarobjects.uri as objecturi
FROM
    $this->calendarObjectTableName AS calendarobjects
LEFT JOIN
    $this->calendarInstancesTableName AS calendar_instances
    ON calendarobjects.calendarid = calendar_instances.calendarid
WHERE
    calendar_instances.principaluri = ?
    AND
    calendarobjects.uid = ?
    AND
    calendar_instances.access = 1
SQL;

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$principalUri, $uid]);

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return $row['calendaruri'].'/'.$row['objecturi'];
        }
    }

    /**
     * The getChanges method returns all the changes that have happened, since
     * the specified syncToken in the specified calendar.
     *
     * This function should return an array, such as the following:
     *
     * [
     *   'syncToken' => 'The current synctoken',
     *   'added'   => [
     *      'new.txt',
     *   ],
     *   'modified'   => [
     *      'modified.txt',
     *   ],
     *   'deleted' => [
     *      'foo.php.bak',
     *      'old.txt'
     *   ]
     * ];
     *
     * The returned syncToken property should reflect the *current* syncToken
     * of the calendar, as reported in the {http://sabredav.org/ns}sync-token
     * property this is needed here too, to ensure the operation is atomic.
     *
     * If the $syncToken argument is specified as null, this is an initial
     * sync, and all members should be reported.
     *
     * The modified property is an array of nodenames that have changed since
     * the last token.
     *
     * The deleted property is an array with nodenames, that have been deleted
     * from collection.
     *
     * The $syncLevel argument is basically the 'depth' of the report. If it's
     * 1, you only have to report changes that happened only directly in
     * immediate descendants. If it's 2, it should also include changes from
     * the nodes below the child collections. (grandchildren)
     *
     * The $limit argument allows a client to specify how many results should
     * be returned at most. If the limit is not specified, it should be treated
     * as infinite.
     *
     * If the limit (infinite or not) is higher than you're willing to return,
     * you should throw a Sabre\DAV\Exception\TooMuchMatches() exception.
     *
     * If the syncToken is expired (due to data cleanup) or unknown, you must
     * return null.
     *
     * The limit is 'suggestive'. You are free to ignore it.
     *
     * @param mixed  $calendarId
     * @param string $syncToken
     * @param int    $syncLevel
     * @param int    $limit
     *
     * @return array|null
     */
    public function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null)
    {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $result = [
            'added' => [],
            'modified' => [],
            'deleted' => [],
        ];

        if ($syncToken) {
            $query = 'SELECT uri, operation, synctoken FROM '.$this->calendarChangesTableName.' WHERE synctoken >= ?  AND calendarid = ? ORDER BY synctoken';
            if ($limit > 0) {
                // Fetch one more raw to detect result truncation
                $query .= ' LIMIT '.((int) $limit + 1);
            }

            // Fetching all changes
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$syncToken, $calendarId]);

            $changes = [];

            // This loop ensures that any duplicates are overwritten, only the
            // last change on a node is relevant.
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $changes[$row['uri']] = $row;
            }
            $currentToken = null;

            $result_count = 0;
            foreach ($changes as $uri => $operation) {
                if (!is_null($limit) && $result_count >= $limit) {
                    $result['result_truncated'] = true;
                    break;
                }

                if (null === $currentToken || $currentToken < $operation['synctoken'] + 1) {
                    // SyncToken in CalDAV perspective is consistently the next number of the last synced change event in this class.
                    $currentToken = $operation['synctoken'] + 1;
                }

                ++$result_count;
                switch ($operation['operation']) {
                    case 1:
                        $result['added'][] = $uri;
                        break;
                    case 2:
                        $result['modified'][] = $uri;
                        break;
                    case 3:
                        $result['deleted'][] = $uri;
                        break;
                }
            }

            if (!is_null($currentToken)) {
                $result['syncToken'] = $currentToken;
            } else {
                // This means returned value is equivalent to syncToken
                $result['syncToken'] = $syncToken;
            }
        } else {
            // Current synctoken
            $stmt = $this->pdo->prepare('SELECT synctoken FROM '.$this->calendarTableName.' WHERE id = ?');
            $stmt->execute([$calendarId]);
            $currentToken = $stmt->fetchColumn(0);

            if (is_null($currentToken)) {
                return null;
            }
            $result['syncToken'] = $currentToken;

            // No synctoken supplied, this is the initial sync.
            $query = 'SELECT uri FROM '.$this->calendarObjectTableName.' WHERE calendarid = ?';
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$calendarId]);

            $result['added'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }

        return $result;
    }

    /**
     * Adds a change record to the calendarchanges table.
     *
     * @param mixed  $calendarId
     * @param string $objectUri
     * @param int    $operation  1 = add, 2 = modify, 3 = delete
     */
    protected function addChange($calendarId, $objectUri, $operation)
    {
        $stmt = $this->pdo->prepare('INSERT INTO '.$this->calendarChangesTableName.' (uri, synctoken, calendarid, operation) SELECT ?, synctoken, ?, ? FROM '.$this->calendarTableName.' WHERE id = ?');
        $stmt->execute([
            $objectUri,
            $calendarId,
            $operation,
            $calendarId,
        ]);
        $stmt = $this->pdo->prepare('UPDATE '.$this->calendarTableName.' SET synctoken = synctoken + 1 WHERE id = ?');
        $stmt->execute([
            $calendarId,
        ]);
    }

    /**
     * Returns a list of subscriptions for a principal.
     *
     * Every subscription is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    subscription. This can be the same as the uri or a database key.
     *  * uri. This is just the 'base uri' or 'filename' of the subscription.
     *  * principaluri. The owner of the subscription. Almost always the same as
     *    principalUri passed to this method.
     *  * source. Url to the actual feed
     *
     * Furthermore, all the subscription info must be returned too:
     *
     * 1. {DAV:}displayname
     * 2. {http://apple.com/ns/ical/}refreshrate
     * 3. {http://calendarserver.org/ns/}subscribed-strip-todos (omit if todos
     *    should not be stripped).
     * 4. {http://calendarserver.org/ns/}subscribed-strip-alarms (omit if alarms
     *    should not be stripped).
     * 5. {http://calendarserver.org/ns/}subscribed-strip-attachments (omit if
     *    attachments should not be stripped).
     * 7. {http://apple.com/ns/ical/}calendar-color
     * 8. {http://apple.com/ns/ical/}calendar-order
     * 9. {urn:ietf:params:xml:ns:caldav}supported-calendar-component-set
     *    (should just be an instance of
     *    Sabre\CalDAV\Property\SupportedCalendarComponentSet, with a bunch of
     *    default components).
     *
     * @param string $principalUri
     *
     * @return array
     */
    public function getSubscriptionsForUser($principalUri)
    {
        $fields = array_values($this->subscriptionPropertyMap);
        $fields[] = 'id';
        $fields[] = 'uri';
        $fields[] = 'source';
        $fields[] = 'principaluri';
        $fields[] = 'lastmodified';

        // Making fields a comma-delimited list
        $fields = implode(', ', $fields);
        $stmt = $this->pdo->prepare('SELECT '.$fields.' FROM '.$this->calendarSubscriptionsTableName.' WHERE principaluri = ? ORDER BY calendarorder ASC');
        $stmt->execute([$principalUri]);

        $subscriptions = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $subscription = [
                'id' => $row['id'],
                'uri' => $row['uri'],
                'principaluri' => $row['principaluri'],
                'source' => $row['source'],
                'lastmodified' => $row['lastmodified'],

                '{'.CalDAV\Plugin::NS_CALDAV.'}supported-calendar-component-set' => new CalDAV\Xml\Property\SupportedCalendarComponentSet(['VTODO', 'VEVENT']),
            ];

            foreach ($this->subscriptionPropertyMap as $xmlName => $dbName) {
                if (!is_null($row[$dbName])) {
                    $subscription[$xmlName] = $row[$dbName];
                }
            }

            $subscriptions[] = $subscription;
        }

        return $subscriptions;
    }

    /**
     * Creates a new subscription for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference
     * this subscription in other methods, such as updateSubscription.
     *
     * @param string $principalUri
     * @param string $uri
     *
     * @return mixed
     */
    public function createSubscription($principalUri, $uri, array $properties)
    {
        $fieldNames = [
            'principaluri',
            'uri',
            'source',
            'lastmodified',
        ];

        if (!isset($properties['{http://calendarserver.org/ns/}source'])) {
            throw new Forbidden('The {http://calendarserver.org/ns/}source property is required when creating subscriptions');
        }

        $values = [
            ':principaluri' => $principalUri,
            ':uri' => $uri,
            ':source' => $properties['{http://calendarserver.org/ns/}source']->getHref(),
            ':lastmodified' => time(),
        ];

        foreach ($this->subscriptionPropertyMap as $xmlName => $dbName) {
            if (isset($properties[$xmlName])) {
                $values[':'.$dbName] = $properties[$xmlName];
                $fieldNames[] = $dbName;
            }
        }

        $stmt = $this->pdo->prepare('INSERT INTO '.$this->calendarSubscriptionsTableName.' ('.implode(', ', $fieldNames).') VALUES ('.implode(', ', array_keys($values)).')');
        $stmt->execute($values);

        return $this->pdo->lastInsertId(
            $this->calendarSubscriptionsTableName.'_id_seq'
        );
    }

    /**
     * Updates a subscription.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param mixed $subscriptionId
     */
    public function updateSubscription($subscriptionId, PropPatch $propPatch)
    {
        $supportedProperties = array_keys($this->subscriptionPropertyMap);
        $supportedProperties[] = '{http://calendarserver.org/ns/}source';

        $propPatch->handle($supportedProperties, function ($mutations) use ($subscriptionId) {
            $newValues = [];

            foreach ($mutations as $propertyName => $propertyValue) {
                if ('{http://calendarserver.org/ns/}source' === $propertyName) {
                    $newValues['source'] = $propertyValue->getHref();
                } else {
                    $fieldName = $this->subscriptionPropertyMap[$propertyName];
                    $newValues[$fieldName] = $propertyValue;
                }
            }

            // Now we're generating the sql query.
            $valuesSql = [];
            foreach ($newValues as $fieldName => $value) {
                $valuesSql[] = $fieldName.' = ?';
            }

            $stmt = $this->pdo->prepare('UPDATE '.$this->calendarSubscriptionsTableName.' SET '.implode(', ', $valuesSql).', lastmodified = ? WHERE id = ?');
            $newValues['lastmodified'] = time();
            $newValues['id'] = $subscriptionId;
            $stmt->execute(array_values($newValues));

            return true;
        });
    }

    /**
     * Deletes a subscription.
     *
     * @param mixed $subscriptionId
     */
    public function deleteSubscription($subscriptionId)
    {
        $stmt = $this->pdo->prepare('DELETE FROM '.$this->calendarSubscriptionsTableName.' WHERE id = ?');
        $stmt->execute([$subscriptionId]);
    }

    /**
     * Returns a single scheduling object.
     *
     * The returned array should contain the following elements:
     *   * uri - A unique basename for the object. This will be used to
     *           construct a full uri.
     *   * calendardata - The iCalendar object
     *   * lastmodified - The last modification date. Can be an int for a unix
     *                    timestamp, or a PHP DateTime object.
     *   * etag - A unique token that must change if the object changed.
     *   * size - The size of the object, in bytes.
     *
     * @param string $principalUri
     * @param string $objectUri
     *
     * @return array
     */
    public function getSchedulingObject($principalUri, $objectUri)
    {
        $stmt = $this->pdo->prepare('SELECT uri, calendardata, lastmodified, etag, size FROM '.$this->schedulingObjectTableName.' WHERE principaluri = ? AND uri = ?');
        $stmt->execute([$principalUri, $objectUri]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return [
            'uri' => $row['uri'],
            'calendardata' => $row['calendardata'],
            'lastmodified' => $row['lastmodified'],
            'etag' => '"'.$row['etag'].'"',
            'size' => (int) $row['size'],
         ];
    }

    /**
     * Returns all scheduling objects for the inbox collection.
     *
     * These objects should be returned as an array. Every item in the array
     * should follow the same structure as returned from getSchedulingObject.
     *
     * The main difference is that 'calendardata' is optional.
     *
     * @param string $principalUri
     *
     * @return array
     */
    public function getSchedulingObjects($principalUri)
    {
        $stmt = $this->pdo->prepare('SELECT id, calendardata, uri, lastmodified, etag, size FROM '.$this->schedulingObjectTableName.' WHERE principaluri = ?');
        $stmt->execute([$principalUri]);

        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[] = [
                'calendardata' => $row['calendardata'],
                'uri' => $row['uri'],
                'lastmodified' => $row['lastmodified'],
                'etag' => '"'.$row['etag'].'"',
                'size' => (int) $row['size'],
            ];
        }

        return $result;
    }

    /**
     * Deletes a scheduling object.
     *
     * @param string $principalUri
     * @param string $objectUri
     */
    public function deleteSchedulingObject($principalUri, $objectUri)
    {
        $stmt = $this->pdo->prepare('DELETE FROM '.$this->schedulingObjectTableName.' WHERE principaluri = ? AND uri = ?');
        $stmt->execute([$principalUri, $objectUri]);
    }

    /**
     * Creates a new scheduling object. This should land in a users' inbox.
     *
     * @param string          $principalUri
     * @param string          $objectUri
     * @param string|resource $objectData
     */
    public function createSchedulingObject($principalUri, $objectUri, $objectData)
    {
        $stmt = $this->pdo->prepare('INSERT INTO '.$this->schedulingObjectTableName.' (principaluri, calendardata, uri, lastmodified, etag, size) VALUES (?, ?, ?, ?, ?, ?)');

        if (is_resource($objectData)) {
            $objectData = stream_get_contents($objectData);
        }

        $stmt->execute([$principalUri, $objectData, $objectUri, time(), md5($objectData), strlen($objectData)]);
    }

    /**
     * Updates the list of shares.
     *
     * @param mixed                           $calendarId
     * @param \Sabre\DAV\Xml\Element\Sharee[] $sharees
     */
    public function updateInvites($calendarId, array $sharees)
    {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
        }
        $currentInvites = $this->getInvites($calendarId);
        list($calendarId, $instanceId) = $calendarId;

        $removeStmt = $this->pdo->prepare('DELETE FROM '.$this->calendarInstancesTableName.' WHERE calendarid = ? AND share_href = ? AND access IN (2,3)');
        $updateStmt = $this->pdo->prepare('UPDATE '.$this->calendarInstancesTableName.' SET access = ?, share_displayname = ?, share_invitestatus = ? WHERE calendarid = ? AND share_href = ?');

        $insertStmt = $this->pdo->prepare('
INSERT INTO '.$this->calendarInstancesTableName.'
    (
        calendarid,
        principaluri,
        access,
        displayname,
        uri,
        description,
        calendarorder,
        calendarcolor,
        timezone,
        transparent,
        share_href,
        share_displayname,
        share_invitestatus
    )
    SELECT
        ?,
        ?,
        ?,
        displayname,
        ?,
        description,
        calendarorder,
        calendarcolor,
        timezone,
        1,
        ?,
        ?,
        ?
    FROM '.$this->calendarInstancesTableName.' WHERE id = ?');

        foreach ($sharees as $sharee) {
            if (\Sabre\DAV\Sharing\Plugin::ACCESS_NOACCESS === $sharee->access) {
                // if access was set no NOACCESS, it means access for an
                // existing sharee was removed.
                $removeStmt->execute([$calendarId, $sharee->href]);
                continue;
            }

            if (is_null($sharee->principal)) {
                // If the server could not determine the principal automatically,
                // we will mark the invite status as invalid.
                $sharee->inviteStatus = \Sabre\DAV\Sharing\Plugin::INVITE_INVALID;
            } else {
                // Because sabre/dav does not yet have an invitation system,
                // every invite is automatically accepted for now.
                $sharee->inviteStatus = \Sabre\DAV\Sharing\Plugin::INVITE_ACCEPTED;
            }

            foreach ($currentInvites as $oldSharee) {
                if ($oldSharee->href === $sharee->href) {
                    // This is an update
                    $sharee->properties = array_merge(
                        $oldSharee->properties,
                        $sharee->properties
                    );
                    $updateStmt->execute([
                        $sharee->access,
                        isset($sharee->properties['{DAV:}displayname']) ? $sharee->properties['{DAV:}displayname'] : null,
                        $sharee->inviteStatus ?: $oldSharee->inviteStatus,
                        $calendarId,
                        $sharee->href,
                    ]);
                    continue 2;
                }
            }
            // If we got here, it means it was a new sharee
            $insertStmt->execute([
                $calendarId,
                $sharee->principal,
                $sharee->access,
                \Sabre\DAV\UUIDUtil::getUUID(),
                $sharee->href,
                isset($sharee->properties['{DAV:}displayname']) ? $sharee->properties['{DAV:}displayname'] : null,
                $sharee->inviteStatus ?: \Sabre\DAV\Sharing\Plugin::INVITE_NORESPONSE,
                $instanceId,
            ]);
        }
    }

    /**
     * Returns the list of people whom a calendar is shared with.
     *
     * Every item in the returned list must be a Sharee object with at
     * least the following properties set:
     *   $href
     *   $shareAccess
     *   $inviteStatus
     *
     * and optionally:
     *   $properties
     *
     * @param mixed $calendarId
     *
     * @return \Sabre\DAV\Xml\Element\Sharee[]
     */
    public function getInvites($calendarId)
    {
        if (!is_array($calendarId)) {
            throw new \InvalidArgumentException('The value passed to getInvites() is expected to be an array with a calendarId and an instanceId');
        }
        list($calendarId, $instanceId) = $calendarId;

        $query = <<<SQL
SELECT
    principaluri,
    access,
    share_href,
    share_displayname,
    share_invitestatus
FROM {$this->calendarInstancesTableName}
WHERE
    calendarid = ?
SQL;

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$calendarId]);

        $result = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = new Sharee([
                'href' => isset($row['share_href']) ? $row['share_href'] : \Sabre\HTTP\encodePath($row['principaluri']),
                'access' => (int) $row['access'],
                /// Everyone is always immediately accepted, for now.
                'inviteStatus' => (int) $row['share_invitestatus'],
                'properties' => !empty($row['share_displayname'])
                    ? ['{DAV:}displayname' => $row['share_displayname']]
                    : [],
                'principal' => $row['principaluri'],
            ]);
        }

        return $result;
    }

    /**
     * Publishes a calendar.
     *
     * @param mixed $calendarId
     * @param bool  $value
     */
    public function setPublishStatus($calendarId, $value)
    {
        throw new DAV\Exception\NotImplemented('Not implemented');
    }
}
