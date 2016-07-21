<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\CalDAV;

use OCA\DAV\DAV\Sharing\IShareable;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\Sharing\Backend;
use OCP\IDBConnection;
use Sabre\CalDAV\Backend\AbstractBackend;
use Sabre\CalDAV\Backend\SchedulingSupport;
use Sabre\CalDAV\Backend\SubscriptionSupport;
use Sabre\CalDAV\Backend\SyncSupport;
use Sabre\CalDAV\Plugin;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\PropPatch;
use Sabre\HTTP\URLUtil;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Reader;
use Sabre\VObject\Recur\EventIterator;

/**
 * Class CalDavBackend
 *
 * Code is heavily inspired by https://github.com/fruux/sabre-dav/blob/master/lib/CalDAV/Backend/PDO.php
 *
 * @package OCA\DAV\CalDAV
 */
class CalDavBackend extends AbstractBackend implements SyncSupport, SubscriptionSupport, SchedulingSupport {

	/**
	 * We need to specify a max date, because we need to stop *somewhere*
	 *
	 * On 32 bit system the maximum for a signed integer is 2147483647, so
	 * MAX_DATE cannot be higher than date('Y-m-d', 2147483647) which results
	 * in 2038-01-19 to avoid problems when the date is converted
	 * to a unix timestamp.
	 */
	const MAX_DATE = '2038-01-01';

	const CLASSIFICATION_PUBLIC = 0;
	const CLASSIFICATION_PRIVATE = 1;
	const CLASSIFICATION_CONFIDENTIAL = 2;

	/**
	 * List of CalDAV properties, and how they map to database field names
	 * Add your own properties by simply adding on to this array.
	 *
	 * Note that only string-based properties are supported here.
	 *
	 * @var array
	 */
	public $propertyMap = [
		'{DAV:}displayname'                          => 'displayname',
		'{urn:ietf:params:xml:ns:caldav}calendar-description' => 'description',
		'{urn:ietf:params:xml:ns:caldav}calendar-timezone'    => 'timezone',
		'{http://apple.com/ns/ical/}calendar-order'  => 'calendarorder',
		'{http://apple.com/ns/ical/}calendar-color'  => 'calendarcolor',
	];

	/**
	 * List of subscription properties, and how they map to database field names.
	 *
	 * @var array
	 */
	public $subscriptionPropertyMap = [
		'{DAV:}displayname'                                           => 'displayname',
		'{http://apple.com/ns/ical/}refreshrate'                      => 'refreshrate',
		'{http://apple.com/ns/ical/}calendar-order'                   => 'calendarorder',
		'{http://apple.com/ns/ical/}calendar-color'                   => 'calendarcolor',
		'{http://calendarserver.org/ns/}subscribed-strip-todos'       => 'striptodos',
		'{http://calendarserver.org/ns/}subscribed-strip-alarms'      => 'stripalarms',
		'{http://calendarserver.org/ns/}subscribed-strip-attachments' => 'stripattachments',
	];

	/** @var IDBConnection */
	private $db;

	/** @var Backend */
	private $sharingBackend;

	/** @var Principal */
	private $principalBackend;

	/**
	 * CalDavBackend constructor.
	 *
	 * @param IDBConnection $db
	 * @param Principal $principalBackend
	 */
	public function __construct(IDBConnection $db, Principal $principalBackend) {
		$this->db = $db;
		$this->principalBackend = $principalBackend;
		$this->sharingBackend = new Backend($this->db, $principalBackend, 'calendar');
	}

	/**
	 * Returns a list of calendars for a principal.
	 *
	 * Every project is an array with the following keys:
	 *  * id, a unique id that will be used by other functions to modify the
	 *    calendar. This can be the same as the uri or a database key.
	 *  * uri, which the basename of the uri with which the calendar is
	 *    accessed.
	 *  * principaluri. The owner of the calendar. Almost always the same as
	 *    principalUri passed to this method.
	 *
	 * Furthermore it can contain webdav properties in clark notation. A very
	 * common one is '{DAV:}displayname'.
	 *
	 * Many clients also require:
	 * {urn:ietf:params:xml:ns:caldav}supported-calendar-component-set
	 * For this property, you can just return an instance of
	 * Sabre\CalDAV\Property\SupportedCalendarComponentSet.
	 *
	 * If you return {http://sabredav.org/ns}read-only and set the value to 1,
	 * ACL will automatically be put in read-only mode.
	 *
	 * @param string $principalUri
	 * @return array
	 */
	function getCalendarsForUser($principalUri) {
		$principalUriOriginal = $principalUri;
		$principalUri = $this->convertPrincipal($principalUri, true);
		$fields = array_values($this->propertyMap);
		$fields[] = 'id';
		$fields[] = 'uri';
		$fields[] = 'synctoken';
		$fields[] = 'components';
		$fields[] = 'principaluri';
		$fields[] = 'transparent';

		// Making fields a comma-delimited list
		$query = $this->db->getQueryBuilder();
		$query->select($fields)->from('calendars')
				->where($query->expr()->eq('principaluri', $query->createNamedParameter($principalUri)))
				->orderBy('calendarorder', 'ASC');
		$stmt = $query->execute();

		$calendars = [];
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

			$components = [];
			if ($row['components']) {
				$components = explode(',',$row['components']);
			}

			$calendar = [
				'id' => $row['id'],
				'uri' => $row['uri'],
				'principaluri' => $this->convertPrincipal($row['principaluri'], false),
				'{' . Plugin::NS_CALENDARSERVER . '}getctag' => 'http://sabre.io/ns/sync/' . ($row['synctoken']?$row['synctoken']:'0'),
				'{http://sabredav.org/ns}sync-token' => $row['synctoken']?$row['synctoken']:'0',
				'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet($components),
				'{' . Plugin::NS_CALDAV . '}schedule-calendar-transp' => new ScheduleCalendarTransp($row['transparent']?'transparent':'opaque'),
			];

			foreach($this->propertyMap as $xmlName=>$dbName) {
				$calendar[$xmlName] = $row[$dbName];
			}

			if (!isset($calendars[$calendar['id']])) {
				$calendars[$calendar['id']] = $calendar;
			}
		}

		$stmt->closeCursor();

		// query for shared calendars
		$principals = $this->principalBackend->getGroupMembership($principalUriOriginal, true);
		$principals[]= $principalUri;

		$fields = array_values($this->propertyMap);
		$fields[] = 'a.id';
		$fields[] = 'a.uri';
		$fields[] = 'a.synctoken';
		$fields[] = 'a.components';
		$fields[] = 'a.principaluri';
		$fields[] = 'a.transparent';
		$fields[] = 's.access';
		$query = $this->db->getQueryBuilder();
		$result = $query->select($fields)
			->from('dav_shares', 's')
			->join('s', 'calendars', 'a', $query->expr()->eq('s.resourceid', 'a.id'))
			->where($query->expr()->in('s.principaluri', $query->createParameter('principaluri')))
			->andWhere($query->expr()->eq('s.type', $query->createParameter('type')))
			->setParameter('type', 'calendar')
			->setParameter('principaluri', $principals, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
			->execute();

		while($row = $result->fetch()) {
			list(, $name) = URLUtil::splitPath($row['principaluri']);
			$uri = $row['uri'] . '_shared_by_' . $name;
			$row['displayname'] = $row['displayname'] . "($name)";
			$components = [];
			if ($row['components']) {
				$components = explode(',',$row['components']);
			}
			$calendar = [
				'id' => $row['id'],
				'uri' => $uri,
				'principaluri' => $principalUri,
				'{' . Plugin::NS_CALENDARSERVER . '}getctag' => 'http://sabre.io/ns/sync/' . ($row['synctoken']?$row['synctoken']:'0'),
				'{http://sabredav.org/ns}sync-token' => $row['synctoken']?$row['synctoken']:'0',
				'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet($components),
				'{' . Plugin::NS_CALDAV . '}schedule-calendar-transp' => new ScheduleCalendarTransp($row['transparent']?'transparent':'opaque'),
				'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal' => $row['principaluri'],
				'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}read-only' => (int)$row['access'] === Backend::ACCESS_READ,
			];

			foreach($this->propertyMap as $xmlName=>$dbName) {
				$calendar[$xmlName] = $row[$dbName];
			}

			if (!isset($calendars[$calendar['id']])) {
				$calendars[$calendar['id']] = $calendar;
			}
		}
		$result->closeCursor();

		return array_values($calendars);
	}

	/**
	 * @param string $principal
	 * @param string $uri
	 * @return array|null
	 */
	public function getCalendarByUri($principal, $uri) {
		$fields = array_values($this->propertyMap);
		$fields[] = 'id';
		$fields[] = 'uri';
		$fields[] = 'synctoken';
		$fields[] = 'components';
		$fields[] = 'principaluri';
		$fields[] = 'transparent';

		// Making fields a comma-delimited list
		$query = $this->db->getQueryBuilder();
		$query->select($fields)->from('calendars')
			->where($query->expr()->eq('uri', $query->createNamedParameter($uri)))
			->andWhere($query->expr()->eq('principaluri', $query->createNamedParameter($principal)))
			->setMaxResults(1);
		$stmt = $query->execute();

		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		$stmt->closeCursor();
		if ($row === false) {
			return null;
		}

		$components = [];
		if ($row['components']) {
			$components = explode(',',$row['components']);
		}

		$calendar = [
			'id' => $row['id'],
			'uri' => $row['uri'],
			'principaluri' => $row['principaluri'],
			'{' . Plugin::NS_CALENDARSERVER . '}getctag' => 'http://sabre.io/ns/sync/' . ($row['synctoken']?$row['synctoken']:'0'),
			'{http://sabredav.org/ns}sync-token' => $row['synctoken']?$row['synctoken']:'0',
			'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet($components),
			'{' . Plugin::NS_CALDAV . '}schedule-calendar-transp' => new ScheduleCalendarTransp($row['transparent']?'transparent':'opaque'),
		];

		foreach($this->propertyMap as $xmlName=>$dbName) {
			$calendar[$xmlName] = $row[$dbName];
		}

		return $calendar;
	}

	public function getCalendarById($calendarId) {
		$fields = array_values($this->propertyMap);
		$fields[] = 'id';
		$fields[] = 'uri';
		$fields[] = 'synctoken';
		$fields[] = 'components';
		$fields[] = 'principaluri';
		$fields[] = 'transparent';

		// Making fields a comma-delimited list
		$query = $this->db->getQueryBuilder();
		$query->select($fields)->from('calendars')
			->where($query->expr()->eq('id', $query->createNamedParameter($calendarId)))
			->setMaxResults(1);
		$stmt = $query->execute();

		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		$stmt->closeCursor();
		if ($row === false) {
			return null;
		}

		$components = [];
		if ($row['components']) {
			$components = explode(',',$row['components']);
		}

		$calendar = [
			'id' => $row['id'],
			'uri' => $row['uri'],
			'principaluri' => $row['principaluri'],
			'{' . Plugin::NS_CALENDARSERVER . '}getctag' => 'http://sabre.io/ns/sync/' . ($row['synctoken']?$row['synctoken']:'0'),
			'{http://sabredav.org/ns}sync-token' => $row['synctoken']?$row['synctoken']:'0',
			'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet($components),
			'{' . Plugin::NS_CALDAV . '}schedule-calendar-transp' => new ScheduleCalendarTransp($row['transparent']?'transparent':'opaque'),
		];

		foreach($this->propertyMap as $xmlName=>$dbName) {
			$calendar[$xmlName] = $row[$dbName];
		}

		return $calendar;
	}

	/**
	 * Creates a new calendar for a principal.
	 *
	 * If the creation was a success, an id must be returned that can be used to reference
	 * this calendar in other methods, such as updateCalendar.
	 *
	 * @param string $principalUri
	 * @param string $calendarUri
	 * @param array $properties
	 * @return int
	 */
	function createCalendar($principalUri, $calendarUri, array $properties) {
		$values = [
			'principaluri' => $principalUri,
			'uri'          => $calendarUri,
			'synctoken'    => 1,
			'transparent'  => 0,
			'components'   => 'VEVENT,VTODO',
			'displayname'  => $calendarUri
		];

		// Default value
		$sccs = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';
		if (isset($properties[$sccs])) {
			if (!($properties[$sccs] instanceof SupportedCalendarComponentSet)) {
				throw new DAV\Exception('The ' . $sccs . ' property must be of type: \Sabre\CalDAV\Property\SupportedCalendarComponentSet');
			}
			$values['components'] = implode(',',$properties[$sccs]->getValue());
		}
		$transp = '{' . Plugin::NS_CALDAV . '}schedule-calendar-transp';
		if (isset($properties[$transp])) {
			$values['transparent'] = $properties[$transp]->getValue()==='transparent';
		}

		foreach($this->propertyMap as $xmlName=>$dbName) {
			if (isset($properties[$xmlName])) {
				$values[$dbName] = $properties[$xmlName];
			}
		}

		$query = $this->db->getQueryBuilder();
		$query->insert('calendars');
		foreach($values as $column => $value) {
			$query->setValue($column, $query->createNamedParameter($value));
		}
		$query->execute();
		return $query->getLastInsertId();
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
	 * @param PropPatch $propPatch
	 * @return void
	 */
	function updateCalendar($calendarId, PropPatch $propPatch) {
		$supportedProperties = array_keys($this->propertyMap);
		$supportedProperties[] = '{' . Plugin::NS_CALDAV . '}schedule-calendar-transp';

		$propPatch->handle($supportedProperties, function($mutations) use ($calendarId) {
			$newValues = [];
			foreach ($mutations as $propertyName => $propertyValue) {

				switch ($propertyName) {
					case '{' . Plugin::NS_CALDAV . '}schedule-calendar-transp' :
						$fieldName = 'transparent';
						$newValues[$fieldName] = $propertyValue->getValue() === 'transparent';
						break;
					default :
						$fieldName = $this->propertyMap[$propertyName];
						$newValues[$fieldName] = $propertyValue;
						break;
				}

			}
			$query = $this->db->getQueryBuilder();
			$query->update('calendars');
			foreach ($newValues as $fieldName => $value) {
				$query->set($fieldName, $query->createNamedParameter($value));
			}
			$query->where($query->expr()->eq('id', $query->createNamedParameter($calendarId)));
			$query->execute();

			$this->addChange($calendarId, "", 2);

			return true;
		});
	}

	/**
	 * Delete a calendar and all it's objects
	 *
	 * @param mixed $calendarId
	 * @return void
	 */
	function deleteCalendar($calendarId) {
		$stmt = $this->db->prepare('DELETE FROM `*PREFIX*calendarobjects` WHERE `calendarid` = ?');
		$stmt->execute([$calendarId]);

		$stmt = $this->db->prepare('DELETE FROM `*PREFIX*calendars` WHERE `id` = ?');
		$stmt->execute([$calendarId]);

		$stmt = $this->db->prepare('DELETE FROM `*PREFIX*calendarchanges` WHERE `calendarid` = ?');
		$stmt->execute([$calendarId]);

		$this->sharingBackend->deleteAllShares($calendarId);
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
	 *   '"abcdef"')
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
	 * @return array
	 */
	function getCalendarObjects($calendarId) {
		$query = $this->db->getQueryBuilder();
		$query->select(['id', 'uri', 'lastmodified', 'etag', 'calendarid', 'size', 'componenttype', 'classification'])
			->from('calendarobjects')
			->where($query->expr()->eq('calendarid', $query->createNamedParameter($calendarId)));
		$stmt = $query->execute();

		$result = [];
		foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
			$result[] = [
					'id'           => $row['id'],
					'uri'          => $row['uri'],
					'lastmodified' => $row['lastmodified'],
					'etag'         => '"' . $row['etag'] . '"',
					'calendarid'   => $row['calendarid'],
					'size'         => (int)$row['size'],
					'component'    => strtolower($row['componenttype']),
					'classification'=> (int)$row['classification']
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
	 * @param mixed $calendarId
	 * @param string $objectUri
	 * @return array|null
	 */
	function getCalendarObject($calendarId, $objectUri) {

		$query = $this->db->getQueryBuilder();
		$query->select(['id', 'uri', 'lastmodified', 'etag', 'calendarid', 'size', 'calendardata', 'componenttype', 'classification'])
				->from('calendarobjects')
				->where($query->expr()->eq('calendarid', $query->createNamedParameter($calendarId)))
				->andWhere($query->expr()->eq('uri', $query->createNamedParameter($objectUri)));
		$stmt = $query->execute();
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);

		if(!$row) return null;

		return [
				'id'            => $row['id'],
				'uri'           => $row['uri'],
				'lastmodified'  => $row['lastmodified'],
				'etag'          => '"' . $row['etag'] . '"',
				'calendarid'    => $row['calendarid'],
				'size'          => (int)$row['size'],
				'calendardata'  => $this->readBlob($row['calendardata']),
				'component'     => strtolower($row['componenttype']),
				'classification'=> (int)$row['classification']
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
	 * @param string[] $uris
	 * @return array
	 */
	function getMultipleCalendarObjects($calendarId, array $uris) {
		$query = $this->db->getQueryBuilder();
		$query->select(['id', 'uri', 'lastmodified', 'etag', 'calendarid', 'size', 'calendardata', 'componenttype', 'classification'])
				->from('calendarobjects')
				->where($query->expr()->eq('calendarid', $query->createNamedParameter($calendarId)))
				->andWhere($query->expr()->in('uri', $query->createParameter('uri')))
				->setParameter('uri', $uris, IQueryBuilder::PARAM_STR_ARRAY);

		$stmt = $query->execute();

		$result = [];
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

			$result[] = [
					'id'           => $row['id'],
					'uri'          => $row['uri'],
					'lastmodified' => $row['lastmodified'],
					'etag'         => '"' . $row['etag'] . '"',
					'calendarid'   => $row['calendarid'],
					'size'         => (int)$row['size'],
					'calendardata' => $this->readBlob($row['calendardata']),
					'component'    => strtolower($row['componenttype']),
					'classification' => (int)$row['classification']
			];

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
	 * @param mixed $calendarId
	 * @param string $objectUri
	 * @param string $calendarData
	 * @return string
	 */
	function createCalendarObject($calendarId, $objectUri, $calendarData) {
		$extraData = $this->getDenormalizedData($calendarData);

		$query = $this->db->getQueryBuilder();
		$query->insert('calendarobjects')
			->values([
				'calendarid' => $query->createNamedParameter($calendarId),
				'uri' => $query->createNamedParameter($objectUri),
				'calendardata' => $query->createNamedParameter($calendarData, IQueryBuilder::PARAM_LOB),
				'lastmodified' => $query->createNamedParameter(time()),
				'etag' => $query->createNamedParameter($extraData['etag']),
				'size' => $query->createNamedParameter($extraData['size']),
				'componenttype' => $query->createNamedParameter($extraData['componentType']),
				'firstoccurence' => $query->createNamedParameter($extraData['firstOccurence']),
				'lastoccurence' => $query->createNamedParameter($extraData['lastOccurence']),
				'classification' => $query->createNamedParameter($extraData['classification']),
				'uid' => $query->createNamedParameter($extraData['uid']),
			])
			->execute();

		$this->addChange($calendarId, $objectUri, 1);

		return '"' . $extraData['etag'] . '"';
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
	 * @param mixed $calendarId
	 * @param string $objectUri
	 * @param string $calendarData
	 * @return string
	 */
	function updateCalendarObject($calendarId, $objectUri, $calendarData) {
		$extraData = $this->getDenormalizedData($calendarData);

		$query = $this->db->getQueryBuilder();
		$query->update('calendarobjects')
				->set('calendardata', $query->createNamedParameter($calendarData, IQueryBuilder::PARAM_LOB))
				->set('lastmodified', $query->createNamedParameter(time()))
				->set('etag', $query->createNamedParameter($extraData['etag']))
				->set('size', $query->createNamedParameter($extraData['size']))
				->set('componenttype', $query->createNamedParameter($extraData['componentType']))
				->set('firstoccurence', $query->createNamedParameter($extraData['firstOccurence']))
				->set('lastoccurence', $query->createNamedParameter($extraData['lastOccurence']))
				->set('classification', $query->createNamedParameter($extraData['classification']))
				->set('uid', $query->createNamedParameter($extraData['uid']))
			->where($query->expr()->eq('calendarid', $query->createNamedParameter($calendarId)))
			->andWhere($query->expr()->eq('uri', $query->createNamedParameter($objectUri)))
			->execute();

		$this->addChange($calendarId, $objectUri, 2);

		return '"' . $extraData['etag'] . '"';
	}

	/**
	 * @param int $calendarObjectId
	 * @param int $classification
	 */
	public function setClassification($calendarObjectId, $classification) {
		if (!in_array($classification, [
			self::CLASSIFICATION_PUBLIC, self::CLASSIFICATION_PRIVATE, self::CLASSIFICATION_CONFIDENTIAL
		])) {
			throw new \InvalidArgumentException();
		}
		$query = $this->db->getQueryBuilder();
		$query->update('calendarobjects')
			->set('classification', $query->createNamedParameter($classification))
			->where($query->expr()->eq('id', $query->createNamedParameter($calendarObjectId)))
			->execute();
	}

	/**
	 * Deletes an existing calendar object.
	 *
	 * The object uri is only the basename, or filename and not a full path.
	 *
	 * @param mixed $calendarId
	 * @param string $objectUri
	 * @return void
	 */
	function deleteCalendarObject($calendarId, $objectUri) {
		$stmt = $this->db->prepare('DELETE FROM `*PREFIX*calendarobjects` WHERE `calendarid` = ? AND `uri` = ?');
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
	 * documented by Sabre\CalDAV\CalendarQueryParser.
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
	 *   * requests with a time-range-filter on either VEVENT or VTODO.
	 *
	 * ..and combinations of these requests. It may not be worth it to try to
	 * handle every possible situation and just rely on the (relatively
	 * easy to use) CalendarQueryValidator to handle the rest.
	 *
	 * Note that especially time-range-filters may be difficult to parse. A
	 * time-range filter specified on a VEVENT must for instance also handle
	 * recurrence rules correctly.
	 * A good example of how to interprete all these filters can also simply
	 * be found in Sabre\CalDAV\CalendarQueryFilter. This class is as correct
	 * as possible, so it gives you a good idea on what type of stuff you need
	 * to think of.
	 *
	 * @param mixed $calendarId
	 * @param array $filters
	 * @return array
	 */
	function calendarQuery($calendarId, array $filters) {
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
			if (!$filters['prop-filters'] && !$filters['comp-filters'][0]['comp-filters'] && !$filters['comp-filters'][0]['time-range'] && !$filters['comp-filters'][0]['prop-filters']) {
				$requirePostFilter = false;
			}
			// There was a time-range filter
			if ($componentType == 'VEVENT' && isset($filters['comp-filters'][0]['time-range'])) {
				$timeRange = $filters['comp-filters'][0]['time-range'];

				// If start time OR the end time is not specified, we can do a
				// 100% accurate mysql query.
				if (!$filters['prop-filters'] && !$filters['comp-filters'][0]['comp-filters'] && !$filters['comp-filters'][0]['prop-filters'] && (!$timeRange['start'] || !$timeRange['end'])) {
					$requirePostFilter = false;
				}
			}

		}
		$columns = ['uri'];
		if ($requirePostFilter) {
			$columns = ['uri', 'calendardata'];
		}
		$query = $this->db->getQueryBuilder();
		$query->select($columns)
			->from('calendarobjects')
			->where($query->expr()->eq('calendarid', $query->createNamedParameter($calendarId)));

		if ($componentType) {
			$query->andWhere($query->expr()->eq('componenttype', $query->createNamedParameter($componentType)));
		}

		if ($timeRange && $timeRange['start']) {
			$query->andWhere($query->expr()->gt('lastoccurence', $query->createNamedParameter($timeRange['start']->getTimeStamp())));
		}
		if ($timeRange && $timeRange['end']) {
			$query->andWhere($query->expr()->lt('firstoccurence', $query->createNamedParameter($timeRange['end']->getTimeStamp())));
		}

		$stmt = $query->execute();

		$result = [];
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
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
	 * @return string|null
	 */
	function getCalendarObjectByUID($principalUri, $uid) {

		$query = $this->db->getQueryBuilder();
		$query->selectAlias('c.uri', 'calendaruri')->selectAlias('co.uri', 'objecturi')
			->from('calendarobjects', 'co')
			->leftJoin('co', 'calendars', 'c', $query->expr()->eq('co.calendarid', 'c.id'))
			->where($query->expr()->eq('c.principaluri', $query->createNamedParameter($principalUri)))
			->andWhere($query->expr()->eq('co.uid', $query->createNamedParameter($uid)));

		$stmt = $query->execute();

		if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			return $row['calendaruri'] . '/' . $row['objecturi'];
		}

		return null;
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
	 * );
	 *
	 * The returned syncToken property should reflect the *current* syncToken
	 * of the calendar, as reported in the {http://sabredav.org/ns}sync-token
	 * property This is * needed here too, to ensure the operation is atomic.
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
	 * @param string $calendarId
	 * @param string $syncToken
	 * @param int $syncLevel
	 * @param int $limit
	 * @return array
	 */
	function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null) {
		// Current synctoken
		$stmt = $this->db->prepare('SELECT `synctoken` FROM `*PREFIX*calendars` WHERE `id` = ?');
		$stmt->execute([ $calendarId ]);
		$currentToken = $stmt->fetchColumn(0);

		if (is_null($currentToken)) {
			return null;
		}

		$result = [
			'syncToken' => $currentToken,
			'added'     => [],
			'modified'  => [],
			'deleted'   => [],
		];

		if ($syncToken) {

			$query = "SELECT `uri`, `operation` FROM `*PREFIX*calendarchanges` WHERE `synctoken` >= ? AND `synctoken` < ? AND `calendarid` = ? ORDER BY `synctoken`";
			if ($limit>0) {
				$query.= " `LIMIT` " . (int)$limit;
			}

			// Fetching all changes
			$stmt = $this->db->prepare($query);
			$stmt->execute([$syncToken, $currentToken, $calendarId]);

			$changes = [];

			// This loop ensures that any duplicates are overwritten, only the
			// last change on a node is relevant.
			while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

				$changes[$row['uri']] = $row['operation'];

			}

			foreach($changes as $uri => $operation) {

				switch($operation) {
					case 1 :
						$result['added'][] = $uri;
						break;
					case 2 :
						$result['modified'][] = $uri;
						break;
					case 3 :
						$result['deleted'][] = $uri;
						break;
				}

			}
		} else {
			// No synctoken supplied, this is the initial sync.
			$query = "SELECT `uri` FROM `*PREFIX*calendarobjects` WHERE `calendarid` = ?";
			$stmt = $this->db->prepare($query);
			$stmt->execute([$calendarId]);

			$result['added'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
		}
		return $result;

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
	 * 6. {http://calendarserver.org/ns/}source (Must be a
	 *     Sabre\DAV\Property\Href).
	 * 7. {http://apple.com/ns/ical/}calendar-color
	 * 8. {http://apple.com/ns/ical/}calendar-order
	 * 9. {urn:ietf:params:xml:ns:caldav}supported-calendar-component-set
	 *    (should just be an instance of
	 *    Sabre\CalDAV\Property\SupportedCalendarComponentSet, with a bunch of
	 *    default components).
	 *
	 * @param string $principalUri
	 * @return array
	 */
	function getSubscriptionsForUser($principalUri) {
		$fields = array_values($this->subscriptionPropertyMap);
		$fields[] = 'id';
		$fields[] = 'uri';
		$fields[] = 'source';
		$fields[] = 'principaluri';
		$fields[] = 'lastmodified';

		$query = $this->db->getQueryBuilder();
		$query->select($fields)
			->from('calendarsubscriptions')
			->where($query->expr()->eq('principaluri', $query->createNamedParameter($principalUri)))
			->orderBy('calendarorder', 'asc');
		$stmt =$query->execute();

		$subscriptions = [];
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

			$subscription = [
				'id'           => $row['id'],
				'uri'          => $row['uri'],
				'principaluri' => $row['principaluri'],
				'source'       => $row['source'],
				'lastmodified' => $row['lastmodified'],

				'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VTODO', 'VEVENT']),
			];

			foreach($this->subscriptionPropertyMap as $xmlName=>$dbName) {
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
	 * @param array $properties
	 * @return mixed
	 */
	function createSubscription($principalUri, $uri, array $properties) {

		if (!isset($properties['{http://calendarserver.org/ns/}source'])) {
			throw new Forbidden('The {http://calendarserver.org/ns/}source property is required when creating subscriptions');
		}

		$values = [
			'principaluri' => $principalUri,
			'uri'          => $uri,
			'source'       => $properties['{http://calendarserver.org/ns/}source']->getHref(),
			'lastmodified' => time(),
		];

		$propertiesBoolean = ['striptodos', 'stripalarms', 'stripattachments'];

		foreach($this->subscriptionPropertyMap as $xmlName=>$dbName) {
			if (array_key_exists($xmlName, $properties)) {
				$values[$dbName] = $properties[$xmlName];
				if (in_array($dbName, $propertiesBoolean)) {
					$values[$dbName] = true;
				}
			}
		}

		$valuesToInsert = array();

		$query = $this->db->getQueryBuilder();

		foreach (array_keys($values) as $name) {
			$valuesToInsert[$name] = $query->createNamedParameter($values[$name]);
		}

		$query->insert('calendarsubscriptions')
			->values($valuesToInsert)
			->execute();

		return $this->db->lastInsertId('*PREFIX*calendarsubscriptions');
	}

	/**
	 * Updates a subscription
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
	 * @param PropPatch $propPatch
	 * @return void
	 */
	function updateSubscription($subscriptionId, PropPatch $propPatch) {
		$supportedProperties = array_keys($this->subscriptionPropertyMap);
		$supportedProperties[] = '{http://calendarserver.org/ns/}source';

		$propPatch->handle($supportedProperties, function($mutations) use ($subscriptionId) {

			$newValues = [];

			foreach($mutations as $propertyName=>$propertyValue) {
				if ($propertyName === '{http://calendarserver.org/ns/}source') {
					$newValues['source'] = $propertyValue->getHref();
				} else {
					$fieldName = $this->subscriptionPropertyMap[$propertyName];
					$newValues[$fieldName] = $propertyValue;
				}
			}

			$query = $this->db->getQueryBuilder();
			$query->update('calendarsubscriptions')
				->set('lastmodified', $query->createNamedParameter(time()));
			foreach($newValues as $fieldName=>$value) {
				$query->set($fieldName, $query->createNamedParameter($value));
			}
			$query->where($query->expr()->eq('id', $query->createNamedParameter($subscriptionId)))
				->execute();

			return true;

		});
	}

	/**
	 * Deletes a subscription.
	 *
	 * @param mixed $subscriptionId
	 * @return void
	 */
	function deleteSubscription($subscriptionId) {
		$query = $this->db->getQueryBuilder();
		$query->delete('calendarsubscriptions')
			->where($query->expr()->eq('id', $query->createNamedParameter($subscriptionId)))
			->execute();
	}

	/**
	 * Returns a single scheduling object for the inbox collection.
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
	 * @return array
	 */
	function getSchedulingObject($principalUri, $objectUri) {
		$query = $this->db->getQueryBuilder();
		$stmt = $query->select(['uri', 'calendardata', 'lastmodified', 'etag', 'size'])
			->from('schedulingobjects')
			->where($query->expr()->eq('principaluri', $query->createNamedParameter($principalUri)))
			->andWhere($query->expr()->eq('uri', $query->createNamedParameter($objectUri)))
			->execute();

		$row = $stmt->fetch(\PDO::FETCH_ASSOC);

		if(!$row) {
			return null;
		}

		return [
				'uri'          => $row['uri'],
				'calendardata' => $row['calendardata'],
				'lastmodified' => $row['lastmodified'],
				'etag'         => '"' . $row['etag'] . '"',
				'size'         => (int)$row['size'],
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
	 * @return array
	 */
	function getSchedulingObjects($principalUri) {
		$query = $this->db->getQueryBuilder();
		$stmt = $query->select(['uri', 'calendardata', 'lastmodified', 'etag', 'size'])
				->from('schedulingobjects')
				->where($query->expr()->eq('principaluri', $query->createNamedParameter($principalUri)))
				->execute();

		$result = [];
		foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
			$result[] = [
					'calendardata' => $row['calendardata'],
					'uri'          => $row['uri'],
					'lastmodified' => $row['lastmodified'],
					'etag'         => '"' . $row['etag'] . '"',
					'size'         => (int)$row['size'],
			];
		}

		return $result;
	}

	/**
	 * Deletes a scheduling object from the inbox collection.
	 *
	 * @param string $principalUri
	 * @param string $objectUri
	 * @return void
	 */
	function deleteSchedulingObject($principalUri, $objectUri) {
		$query = $this->db->getQueryBuilder();
		$query->delete('schedulingobjects')
				->where($query->expr()->eq('principaluri', $query->createNamedParameter($principalUri)))
				->andWhere($query->expr()->eq('uri', $query->createNamedParameter($objectUri)))
				->execute();
	}

	/**
	 * Creates a new scheduling object. This should land in a users' inbox.
	 *
	 * @param string $principalUri
	 * @param string $objectUri
	 * @param string $objectData
	 * @return void
	 */
	function createSchedulingObject($principalUri, $objectUri, $objectData) {
		$query = $this->db->getQueryBuilder();
		$query->insert('schedulingobjects')
			->values([
				'principaluri' => $query->createNamedParameter($principalUri),
				'calendardata' => $query->createNamedParameter($objectData),
				'uri' => $query->createNamedParameter($objectUri),
				'lastmodified' => $query->createNamedParameter(time()),
				'etag' => $query->createNamedParameter(md5($objectData)),
				'size' => $query->createNamedParameter(strlen($objectData))
			])
			->execute();
	}

	/**
	 * Adds a change record to the calendarchanges table.
	 *
	 * @param mixed $calendarId
	 * @param string $objectUri
	 * @param int $operation 1 = add, 2 = modify, 3 = delete.
	 * @return void
	 */
	protected function addChange($calendarId, $objectUri, $operation) {

		$stmt = $this->db->prepare('INSERT INTO `*PREFIX*calendarchanges` (`uri`, `synctoken`, `calendarid`, `operation`) SELECT ?, `synctoken`, ?, ? FROM `*PREFIX*calendars` WHERE `id` = ?');
		$stmt->execute([
			$objectUri,
			$calendarId,
			$operation,
			$calendarId
		]);
		$stmt = $this->db->prepare('UPDATE `*PREFIX*calendars` SET `synctoken` = `synctoken` + 1 WHERE `id` = ?');
		$stmt->execute([
			$calendarId
		]);

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
	 * @return array
	 */
	public function getDenormalizedData($calendarData) {

		$vObject = Reader::read($calendarData);
		$componentType = null;
		$component = null;
		$firstOccurrence = null;
		$lastOccurrence = null;
		$uid = null;
		$classification = self::CLASSIFICATION_PUBLIC;
		foreach($vObject->getComponents() as $component) {
			if ($component->name!=='VTIMEZONE') {
				$componentType = $component->name;
				$uid = (string)$component->UID;
				break;
			}
		}
		if (!$componentType) {
			throw new \Sabre\DAV\Exception\BadRequest('Calendar objects must have a VJOURNAL, VEVENT or VTODO component');
		}
		if ($componentType === 'VEVENT' && $component->DTSTART) {
			$firstOccurrence = $component->DTSTART->getDateTime()->getTimeStamp();
			// Finding the last occurrence is a bit harder
			if (!isset($component->RRULE)) {
				if (isset($component->DTEND)) {
					$lastOccurrence = $component->DTEND->getDateTime()->getTimeStamp();
				} elseif (isset($component->DURATION)) {
					$endDate = clone $component->DTSTART->getDateTime();
					$endDate->add(DateTimeParser::parse($component->DURATION->getValue()));
					$lastOccurrence = $endDate->getTimeStamp();
				} elseif (!$component->DTSTART->hasTime()) {
					$endDate = clone $component->DTSTART->getDateTime();
					$endDate->modify('+1 day');
					$lastOccurrence = $endDate->getTimeStamp();
				} else {
					$lastOccurrence = $firstOccurrence;
				}
			} else {
				$it = new EventIterator($vObject, (string)$component->UID);
				$maxDate = new \DateTime(self::MAX_DATE);
				if ($it->isInfinite()) {
					$lastOccurrence = $maxDate->getTimeStamp();
				} else {
					$end = $it->getDtEnd();
					while($it->valid() && $end < $maxDate) {
						$end = $it->getDtEnd();
						$it->next();

					}
					$lastOccurrence = $end->getTimeStamp();
				}

			}
		}

		if ($component->CLASS) {
			$classification = CalDavBackend::CLASSIFICATION_PRIVATE;
			switch ($component->CLASS->getValue()) {
				case 'PUBLIC':
					$classification = CalDavBackend::CLASSIFICATION_PUBLIC;
					break;
				case 'CONFIDENTIAL':
					$classification = CalDavBackend::CLASSIFICATION_CONFIDENTIAL;
					break;
			}
		}
		return [
			'etag' => md5($calendarData),
			'size' => strlen($calendarData),
			'componentType' => $componentType,
			'firstOccurence' => is_null($firstOccurrence) ? null : max(0, $firstOccurrence),
			'lastOccurence'  => $lastOccurrence,
			'uid' => $uid,
			'classification' => $classification
		];

	}

	private function readBlob($cardData) {
		if (is_resource($cardData)) {
			return stream_get_contents($cardData);
		}

		return $cardData;
	}

	/**
	 * @param IShareable $shareable
	 * @param array $add
	 * @param array $remove
	 */
	public function updateShares($shareable, $add, $remove) {
		$this->sharingBackend->updateShares($shareable, $add, $remove);
	}

	/**
	 * @param int $resourceId
	 * @return array
	 */
	public function getShares($resourceId) {
		return $this->sharingBackend->getShares($resourceId);
	}

	/**
	 * @param int $resourceId
	 * @param array $acl
	 * @return array
	 */
	public function applyShareAcl($resourceId, $acl) {
		return $this->sharingBackend->applyShareAcl($resourceId, $acl);
	}

	private function convertPrincipal($principalUri, $toV2) {
		if ($this->principalBackend->getPrincipalPrefix() === 'principals') {
			list(, $name) = URLUtil::splitPath($principalUri);
			if ($toV2 === true) {
				return "principals/users/$name";
			}
			return "principals/$name";
		}
		return $principalUri;
	}
}
