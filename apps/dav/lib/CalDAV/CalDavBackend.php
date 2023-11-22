<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2018 Georg Ehrke
 * @copyright Copyright (c) 2020, leith abdulla (<online-nextcloud@eleith.com>)
 *
 * @author Chih-Hsuan Yen <yan12125@gmail.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author dartcafe <github@dartcafe.de>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author leith abdulla <online-nextcloud@eleith.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Simon Spannagel <simonspa@kth.se>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vinicius Cubas Brand <vinicius@eita.org.br>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CalDAV;

use DateTime;
use DateTimeInterface;
use OCA\DAV\AppInfo\Application;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\Sharing\Backend;
use OCA\DAV\DAV\Sharing\IShareable;
use OCA\DAV\Events\CachedCalendarObjectCreatedEvent;
use OCA\DAV\Events\CachedCalendarObjectDeletedEvent;
use OCA\DAV\Events\CachedCalendarObjectUpdatedEvent;
use OCA\DAV\Events\CalendarCreatedEvent;
use OCA\DAV\Events\CalendarDeletedEvent;
use OCA\DAV\Events\CalendarMovedToTrashEvent;
use OCA\DAV\Events\CalendarObjectCreatedEvent;
use OCA\DAV\Events\CalendarObjectDeletedEvent;
use OCA\DAV\Events\CalendarObjectMovedEvent;
use OCA\DAV\Events\CalendarObjectMovedToTrashEvent;
use OCA\DAV\Events\CalendarObjectRestoredEvent;
use OCA\DAV\Events\CalendarObjectUpdatedEvent;
use OCA\DAV\Events\CalendarPublishedEvent;
use OCA\DAV\Events\CalendarRestoredEvent;
use OCA\DAV\Events\CalendarShareUpdatedEvent;
use OCA\DAV\Events\CalendarUnpublishedEvent;
use OCA\DAV\Events\CalendarUpdatedEvent;
use OCA\DAV\Events\SubscriptionCreatedEvent;
use OCA\DAV\Events\SubscriptionDeletedEvent;
use OCA\DAV\Events\SubscriptionUpdatedEvent;
use OCP\AppFramework\Db\TTransactional;
use OCP\Calendar\Exceptions\CalendarException;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Sabre\CalDAV\Backend\AbstractBackend;
use Sabre\CalDAV\Backend\SchedulingSupport;
use Sabre\CalDAV\Backend\SubscriptionSupport;
use Sabre\CalDAV\Backend\SyncSupport;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\PropPatch;
use Sabre\Uri;
use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VTimeZone;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\ParseException;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;
use Sabre\VObject\Recur\EventIterator;
use function array_column;
use function array_merge;
use function array_values;
use function explode;
use function is_array;
use function is_resource;
use function pathinfo;
use function rewind;
use function settype;
use function sprintf;
use function str_replace;
use function strtolower;
use function time;

/**
 * Class CalDavBackend
 *
 * Code is heavily inspired by https://github.com/fruux/sabre-dav/blob/master/lib/CalDAV/Backend/PDO.php
 *
 * @package OCA\DAV\CalDAV
 */
class CalDavBackend extends AbstractBackend implements SyncSupport, SubscriptionSupport, SchedulingSupport {
	use TTransactional;

	public const CALENDAR_TYPE_CALENDAR = 0;
	public const CALENDAR_TYPE_SUBSCRIPTION = 1;

	public const PERSONAL_CALENDAR_URI = 'personal';
	public const PERSONAL_CALENDAR_NAME = 'Personal';

	public const RESOURCE_BOOKING_CALENDAR_URI = 'calendar';
	public const RESOURCE_BOOKING_CALENDAR_NAME = 'Calendar';

	/**
	 * We need to specify a max date, because we need to stop *somewhere*
	 *
	 * On 32 bit system the maximum for a signed integer is 2147483647, so
	 * MAX_DATE cannot be higher than date('Y-m-d', 2147483647) which results
	 * in 2038-01-19 to avoid problems when the date is converted
	 * to a unix timestamp.
	 */
	public const MAX_DATE = '2038-01-01';

	public const ACCESS_PUBLIC = 4;
	public const CLASSIFICATION_PUBLIC = 0;
	public const CLASSIFICATION_PRIVATE = 1;
	public const CLASSIFICATION_CONFIDENTIAL = 2;

	/**
	 * List of CalDAV properties, and how they map to database field names and their type
	 * Add your own properties by simply adding on to this array.
	 *
	 * @var array
	 * @psalm-var array<string, string[]>
	 */
	public array $propertyMap = [
		'{DAV:}displayname' => ['displayname', 'string'],
		'{urn:ietf:params:xml:ns:caldav}calendar-description' => ['description', 'string'],
		'{urn:ietf:params:xml:ns:caldav}calendar-timezone' => ['timezone', 'string'],
		'{http://apple.com/ns/ical/}calendar-order' => ['calendarorder', 'int'],
		'{http://apple.com/ns/ical/}calendar-color' => ['calendarcolor', 'string'],
		'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_NEXTCLOUD . '}deleted-at' => ['deleted_at', 'int'],
	];

	/**
	 * List of subscription properties, and how they map to database field names.
	 *
	 * @var array
	 */
	public array $subscriptionPropertyMap = [
		'{DAV:}displayname' => ['displayname', 'string'],
		'{http://apple.com/ns/ical/}refreshrate' => ['refreshrate', 'string'],
		'{http://apple.com/ns/ical/}calendar-order' => ['calendarorder', 'int'],
		'{http://apple.com/ns/ical/}calendar-color' => ['calendarcolor', 'string'],
		'{http://calendarserver.org/ns/}subscribed-strip-todos' => ['striptodos', 'bool'],
		'{http://calendarserver.org/ns/}subscribed-strip-alarms' => ['stripalarms', 'string'],
		'{http://calendarserver.org/ns/}subscribed-strip-attachments' => ['stripattachments', 'string'],
	];

	/**
	 * properties to index
	 *
	 * This list has to be kept in sync with ICalendarQuery::SEARCH_PROPERTY_*
	 *
	 * @see \OCP\Calendar\ICalendarQuery
	 */
	private const INDEXED_PROPERTIES = [
		'CATEGORIES',
		'COMMENT',
		'DESCRIPTION',
		'LOCATION',
		'RESOURCES',
		'STATUS',
		'SUMMARY',
		'ATTENDEE',
		'CONTACT',
		'ORGANIZER'
	];

	/** @var array parameters to index */
	public static array $indexParameters = [
		'ATTENDEE' => ['CN'],
		'ORGANIZER' => ['CN'],
	];

	/**
	 * @var string[] Map of uid => display name
	 */
	protected array $userDisplayNames;

	private Backend $calendarSharingBackend;
	private string $dbObjectPropertiesTable = 'calendarobjects_props';
	private array $cachedObjects = [];

	public function __construct(
		private IDBConnection $db,
		private Principal $principalBackend,
		private IUserManager $userManager,
		IGroupManager $groupManager,
		private ISecureRandom $random,
		private LoggerInterface $logger,
		private IEventDispatcher $dispatcher,
		private IConfig $config,
		private bool $legacyEndpoint = false,
	) {
		$this->calendarSharingBackend = new Backend($this->db, $this->userManager, $groupManager, $principalBackend, 'calendar');
	}

	/**
	 * Return the number of calendars for a principal
	 *
	 * By default this excludes the automatically generated birthday calendar
	 *
	 * @param $principalUri
	 * @param bool $excludeBirthday
	 * @return int
	 */
	public function getCalendarsForUserCount($principalUri, $excludeBirthday = true) {
		$principalUri = $this->convertPrincipal($principalUri, true);
		$query = $this->db->getQueryBuilder();
		$query->select($query->func()->count('*'))
			->from('calendars');

		if ($principalUri === '') {
			$query->where($query->expr()->emptyString('principaluri'));
		} else {
			$query->where($query->expr()->eq('principaluri', $query->createNamedParameter($principalUri)));
		}

		if ($excludeBirthday) {
			$query->andWhere($query->expr()->neq('uri', $query->createNamedParameter(BirthdayService::BIRTHDAY_CALENDAR_URI)));
		}

		$result = $query->executeQuery();
		$column = (int)$result->fetchOne();
		$result->closeCursor();
		return $column;
	}

	/**
	 * @return array{id: int, deleted_at: int}[]
	 */
	public function getDeletedCalendars(int $deletedBefore): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select(['id', 'deleted_at'])
			->from('calendars')
			->where($qb->expr()->isNotNull('deleted_at'))
			->andWhere($qb->expr()->lt('deleted_at', $qb->createNamedParameter($deletedBefore)));
		$result = $qb->executeQuery();
		$calendars = [];
		while (($row = $result->fetch()) !== false) {
			$calendars[] = [
				'id' => (int) $row['id'],
				'deleted_at' => (int) $row['deleted_at'],
			];
		}
		$result->closeCursor();
		return $calendars;
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
	public function getCalendarsForUser($principalUri) {
		return $this->atomic(function () use ($principalUri) {
			$principalUriOriginal = $principalUri;
			$principalUri = $this->convertPrincipal($principalUri, true);
			$fields = array_column($this->propertyMap, 0);
			$fields[] = 'id';
			$fields[] = 'uri';
			$fields[] = 'synctoken';
			$fields[] = 'components';
			$fields[] = 'principaluri';
			$fields[] = 'transparent';

			// Making fields a comma-delimited list
			$query = $this->db->getQueryBuilder();
			$query->select($fields)
				->from('calendars')
				->orderBy('calendarorder', 'ASC');

			if ($principalUri === '') {
				$query->where($query->expr()->emptyString('principaluri'));
			} else {
				$query->where($query->expr()->eq('principaluri', $query->createNamedParameter($principalUri)));
			}

			$result = $query->executeQuery();

			$calendars = [];
			while ($row = $result->fetch()) {
				$row['principaluri'] = (string) $row['principaluri'];
				$components = [];
				if ($row['components']) {
					$components = explode(',', $row['components']);
				}

				$calendar = [
					'id' => $row['id'],
					'uri' => $row['uri'],
					'principaluri' => $this->convertPrincipal($row['principaluri'], !$this->legacyEndpoint),
					'{' . Plugin::NS_CALENDARSERVER . '}getctag' => 'http://sabre.io/ns/sync/' . ($row['synctoken']?$row['synctoken']:'0'),
					'{http://sabredav.org/ns}sync-token' => $row['synctoken']?$row['synctoken']:'0',
					'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet($components),
					'{' . Plugin::NS_CALDAV . '}schedule-calendar-transp' => new ScheduleCalendarTransp($row['transparent']?'transparent':'opaque'),
					'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal' => $this->convertPrincipal($principalUri, !$this->legacyEndpoint),
				];

				$calendar = $this->rowToCalendar($row, $calendar);
				$calendar = $this->addOwnerPrincipalToCalendar($calendar);
				$calendar = $this->addResourceTypeToCalendar($row, $calendar);

				if (!isset($calendars[$calendar['id']])) {
					$calendars[$calendar['id']] = $calendar;
				}
			}
			$result->closeCursor();

			// query for shared calendars
			$principals = $this->principalBackend->getGroupMembership($principalUriOriginal, true);
			$principals = array_merge($principals, $this->principalBackend->getCircleMembership($principalUriOriginal));

			$principals[] = $principalUri;

			$fields = array_column($this->propertyMap, 0);
			$fields[] = 'a.id';
			$fields[] = 'a.uri';
			$fields[] = 'a.synctoken';
			$fields[] = 'a.components';
			$fields[] = 'a.principaluri';
			$fields[] = 'a.transparent';
			$fields[] = 's.access';
			$query = $this->db->getQueryBuilder();
			$query->select($fields)
				->from('dav_shares', 's')
				->join('s', 'calendars', 'a', $query->expr()->eq('s.resourceid', 'a.id'))
				->where($query->expr()->in('s.principaluri', $query->createParameter('principaluri')))
				->andWhere($query->expr()->eq('s.type', $query->createParameter('type')))
				->setParameter('type', 'calendar')
				->setParameter('principaluri', $principals, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);

			$result = $query->executeQuery();

			$readOnlyPropertyName = '{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}read-only';
			while ($row = $result->fetch()) {
				$row['principaluri'] = (string) $row['principaluri'];
				if ($row['principaluri'] === $principalUri) {
					continue;
				}

				$readOnly = (int) $row['access'] === Backend::ACCESS_READ;
				if (isset($calendars[$row['id']])) {
					if ($readOnly) {
						// New share can not have more permissions then the old one.
						continue;
					}
					if (isset($calendars[$row['id']][$readOnlyPropertyName]) &&
						$calendars[$row['id']][$readOnlyPropertyName] === 0) {
						// Old share is already read-write, no more permissions can be gained
						continue;
					}
				}

				[, $name] = Uri\split($row['principaluri']);
				$uri = $row['uri'] . '_shared_by_' . $name;
				$row['displayname'] = $row['displayname'] . ' (' . ($this->userManager->getDisplayName($name) ?? ($name ?? '')) . ')';
				$components = [];
				if ($row['components']) {
					$components = explode(',', $row['components']);
				}
				$calendar = [
					'id' => $row['id'],
					'uri' => $uri,
					'principaluri' => $this->convertPrincipal($principalUri, !$this->legacyEndpoint),
					'{' . Plugin::NS_CALENDARSERVER . '}getctag' => 'http://sabre.io/ns/sync/' . ($row['synctoken']?$row['synctoken']:'0'),
					'{http://sabredav.org/ns}sync-token' => $row['synctoken']?$row['synctoken']:'0',
					'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet($components),
					'{' . Plugin::NS_CALDAV . '}schedule-calendar-transp' => new ScheduleCalendarTransp('transparent'),
					'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal' => $this->convertPrincipal($row['principaluri'], !$this->legacyEndpoint),
					$readOnlyPropertyName => $readOnly,
				];

				$calendar = $this->rowToCalendar($row, $calendar);
				$calendar = $this->addOwnerPrincipalToCalendar($calendar);
				$calendar = $this->addResourceTypeToCalendar($row, $calendar);

				$calendars[$calendar['id']] = $calendar;
			}
			$result->closeCursor();

			return array_values($calendars);
		}, $this->db);
	}

	/**
	 * @param $principalUri
	 * @return array
	 */
	public function getUsersOwnCalendars($principalUri) {
		$principalUri = $this->convertPrincipal($principalUri, true);
		$fields = array_column($this->propertyMap, 0);
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
		$stmt = $query->executeQuery();
		$calendars = [];
		while ($row = $stmt->fetch()) {
			$row['principaluri'] = (string) $row['principaluri'];
			$components = [];
			if ($row['components']) {
				$components = explode(',', $row['components']);
			}
			$calendar = [
				'id' => $row['id'],
				'uri' => $row['uri'],
				'principaluri' => $this->convertPrincipal($row['principaluri'], !$this->legacyEndpoint),
				'{' . Plugin::NS_CALENDARSERVER . '}getctag' => 'http://sabre.io/ns/sync/' . ($row['synctoken']?$row['synctoken']:'0'),
				'{http://sabredav.org/ns}sync-token' => $row['synctoken']?$row['synctoken']:'0',
				'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet($components),
				'{' . Plugin::NS_CALDAV . '}schedule-calendar-transp' => new ScheduleCalendarTransp($row['transparent']?'transparent':'opaque'),
			];

			$calendar = $this->rowToCalendar($row, $calendar);
			$calendar = $this->addOwnerPrincipalToCalendar($calendar);
			$calendar = $this->addResourceTypeToCalendar($row, $calendar);

			if (!isset($calendars[$calendar['id']])) {
				$calendars[$calendar['id']] = $calendar;
			}
		}
		$stmt->closeCursor();
		return array_values($calendars);
	}

	/**
	 * @return array
	 */
	public function getPublicCalendars() {
		$fields = array_column($this->propertyMap, 0);
		$fields[] = 'a.id';
		$fields[] = 'a.uri';
		$fields[] = 'a.synctoken';
		$fields[] = 'a.components';
		$fields[] = 'a.principaluri';
		$fields[] = 'a.transparent';
		$fields[] = 's.access';
		$fields[] = 's.publicuri';
		$calendars = [];
		$query = $this->db->getQueryBuilder();
		$result = $query->select($fields)
			->from('dav_shares', 's')
			->join('s', 'calendars', 'a', $query->expr()->eq('s.resourceid', 'a.id'))
			->where($query->expr()->in('s.access', $query->createNamedParameter(self::ACCESS_PUBLIC)))
			->andWhere($query->expr()->eq('s.type', $query->createNamedParameter('calendar')))
			->executeQuery();

		while ($row = $result->fetch()) {
			$row['principaluri'] = (string) $row['principaluri'];
			[, $name] = Uri\split($row['principaluri']);
			$row['displayname'] = $row['displayname'] . "($name)";
			$components = [];
			if ($row['components']) {
				$components = explode(',', $row['components']);
			}
			$calendar = [
				'id' => $row['id'],
				'uri' => $row['publicuri'],
				'principaluri' => $this->convertPrincipal($row['principaluri'], !$this->legacyEndpoint),
				'{' . Plugin::NS_CALENDARSERVER . '}getctag' => 'http://sabre.io/ns/sync/' . ($row['synctoken']?$row['synctoken']:'0'),
				'{http://sabredav.org/ns}sync-token' => $row['synctoken']?$row['synctoken']:'0',
				'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet($components),
				'{' . Plugin::NS_CALDAV . '}schedule-calendar-transp' => new ScheduleCalendarTransp($row['transparent']?'transparent':'opaque'),
				'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal' => $this->convertPrincipal($row['principaluri'], $this->legacyEndpoint),
				'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}read-only' => (int)$row['access'] === Backend::ACCESS_READ,
				'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}public' => (int)$row['access'] === self::ACCESS_PUBLIC,
			];

			$calendar = $this->rowToCalendar($row, $calendar);
			$calendar = $this->addOwnerPrincipalToCalendar($calendar);
			$calendar = $this->addResourceTypeToCalendar($row, $calendar);

			if (!isset($calendars[$calendar['id']])) {
				$calendars[$calendar['id']] = $calendar;
			}
		}
		$result->closeCursor();

		return array_values($calendars);
	}

	/**
	 * @param string $uri
	 * @return array
	 * @throws NotFound
	 */
	public function getPublicCalendar($uri) {
		$fields = array_column($this->propertyMap, 0);
		$fields[] = 'a.id';
		$fields[] = 'a.uri';
		$fields[] = 'a.synctoken';
		$fields[] = 'a.components';
		$fields[] = 'a.principaluri';
		$fields[] = 'a.transparent';
		$fields[] = 's.access';
		$fields[] = 's.publicuri';
		$query = $this->db->getQueryBuilder();
		$result = $query->select($fields)
			->from('dav_shares', 's')
			->join('s', 'calendars', 'a', $query->expr()->eq('s.resourceid', 'a.id'))
			->where($query->expr()->in('s.access', $query->createNamedParameter(self::ACCESS_PUBLIC)))
			->andWhere($query->expr()->eq('s.type', $query->createNamedParameter('calendar')))
			->andWhere($query->expr()->eq('s.publicuri', $query->createNamedParameter($uri)))
			->executeQuery();

		$row = $result->fetch();

		$result->closeCursor();

		if ($row === false) {
			throw new NotFound('Node with name \'' . $uri . '\' could not be found');
		}

		$row['principaluri'] = (string) $row['principaluri'];
		[, $name] = Uri\split($row['principaluri']);
		$row['displayname'] = $row['displayname'] . ' ' . "($name)";
		$components = [];
		if ($row['components']) {
			$components = explode(',', $row['components']);
		}
		$calendar = [
			'id' => $row['id'],
			'uri' => $row['publicuri'],
			'principaluri' => $this->convertPrincipal($row['principaluri'], !$this->legacyEndpoint),
			'{' . Plugin::NS_CALENDARSERVER . '}getctag' => 'http://sabre.io/ns/sync/' . ($row['synctoken']?$row['synctoken']:'0'),
			'{http://sabredav.org/ns}sync-token' => $row['synctoken']?$row['synctoken']:'0',
			'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet($components),
			'{' . Plugin::NS_CALDAV . '}schedule-calendar-transp' => new ScheduleCalendarTransp($row['transparent']?'transparent':'opaque'),
			'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal' => $this->convertPrincipal($row['principaluri'], !$this->legacyEndpoint),
			'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}read-only' => (int)$row['access'] === Backend::ACCESS_READ,
			'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}public' => (int)$row['access'] === self::ACCESS_PUBLIC,
		];

		$calendar = $this->rowToCalendar($row, $calendar);
		$calendar = $this->addOwnerPrincipalToCalendar($calendar);
		$calendar = $this->addResourceTypeToCalendar($row, $calendar);

		return $calendar;
	}

	/**
	 * @param string $principal
	 * @param string $uri
	 * @return array|null
	 */
	public function getCalendarByUri($principal, $uri) {
		$fields = array_column($this->propertyMap, 0);
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
		$stmt = $query->executeQuery();

		$row = $stmt->fetch();
		$stmt->closeCursor();
		if ($row === false) {
			return null;
		}

		$row['principaluri'] = (string) $row['principaluri'];
		$components = [];
		if ($row['components']) {
			$components = explode(',', $row['components']);
		}

		$calendar = [
			'id' => $row['id'],
			'uri' => $row['uri'],
			'principaluri' => $this->convertPrincipal($row['principaluri'], !$this->legacyEndpoint),
			'{' . Plugin::NS_CALENDARSERVER . '}getctag' => 'http://sabre.io/ns/sync/' . ($row['synctoken']?$row['synctoken']:'0'),
			'{http://sabredav.org/ns}sync-token' => $row['synctoken']?$row['synctoken']:'0',
			'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet($components),
			'{' . Plugin::NS_CALDAV . '}schedule-calendar-transp' => new ScheduleCalendarTransp($row['transparent']?'transparent':'opaque'),
		];

		$calendar = $this->rowToCalendar($row, $calendar);
		$calendar = $this->addOwnerPrincipalToCalendar($calendar);
		$calendar = $this->addResourceTypeToCalendar($row, $calendar);

		return $calendar;
	}

	/**
	 * @return array{id: int, uri: string, '{http://calendarserver.org/ns/}getctag': string, '{http://sabredav.org/ns}sync-token': int, '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set': SupportedCalendarComponentSet, '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp': ScheduleCalendarTransp, '{urn:ietf:params:xml:ns:caldav}calendar-timezone': ?string }|null
	 */
	public function getCalendarById(int $calendarId): ?array {
		$fields = array_column($this->propertyMap, 0);
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
		$stmt = $query->executeQuery();

		$row = $stmt->fetch();
		$stmt->closeCursor();
		if ($row === false) {
			return null;
		}

		$row['principaluri'] = (string) $row['principaluri'];
		$components = [];
		if ($row['components']) {
			$components = explode(',', $row['components']);
		}

		$calendar = [
			'id' => $row['id'],
			'uri' => $row['uri'],
			'principaluri' => $this->convertPrincipal($row['principaluri'], !$this->legacyEndpoint),
			'{' . Plugin::NS_CALENDARSERVER . '}getctag' => 'http://sabre.io/ns/sync/' . ($row['synctoken']?$row['synctoken']:'0'),
			'{http://sabredav.org/ns}sync-token' => $row['synctoken'] ?? 0,
			'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet($components),
			'{' . Plugin::NS_CALDAV . '}schedule-calendar-transp' => new ScheduleCalendarTransp($row['transparent']?'transparent':'opaque'),
		];

		$calendar = $this->rowToCalendar($row, $calendar);
		$calendar = $this->addOwnerPrincipalToCalendar($calendar);
		$calendar = $this->addResourceTypeToCalendar($row, $calendar);

		return $calendar;
	}

	/**
	 * @param $subscriptionId
	 */
	public function getSubscriptionById($subscriptionId) {
		$fields = array_column($this->subscriptionPropertyMap, 0);
		$fields[] = 'id';
		$fields[] = 'uri';
		$fields[] = 'source';
		$fields[] = 'synctoken';
		$fields[] = 'principaluri';
		$fields[] = 'lastmodified';

		$query = $this->db->getQueryBuilder();
		$query->select($fields)
			->from('calendarsubscriptions')
			->where($query->expr()->eq('id', $query->createNamedParameter($subscriptionId)))
			->orderBy('calendarorder', 'asc');
		$stmt = $query->executeQuery();

		$row = $stmt->fetch();
		$stmt->closeCursor();
		if ($row === false) {
			return null;
		}

		$row['principaluri'] = (string) $row['principaluri'];
		$subscription = [
			'id' => $row['id'],
			'uri' => $row['uri'],
			'principaluri' => $row['principaluri'],
			'source' => $row['source'],
			'lastmodified' => $row['lastmodified'],
			'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VTODO', 'VEVENT']),
			'{http://sabredav.org/ns}sync-token' => $row['synctoken']?$row['synctoken']:'0',
		];

		return $this->rowToSubscription($row, $subscription);
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
	 *
	 * @throws CalendarException
	 */
	public function createCalendar($principalUri, $calendarUri, array $properties) {
		if (strlen($calendarUri) > 255) {
			throw new CalendarException('URI too long. Calendar not created');
		}

		$values = [
			'principaluri' => $this->convertPrincipal($principalUri, true),
			'uri' => $calendarUri,
			'synctoken' => 1,
			'transparent' => 0,
			'components' => 'VEVENT,VTODO',
			'displayname' => $calendarUri
		];

		// Default value
		$sccs = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';
		if (isset($properties[$sccs])) {
			if (!($properties[$sccs] instanceof SupportedCalendarComponentSet)) {
				throw new DAV\Exception('The ' . $sccs . ' property must be of type: \Sabre\CalDAV\Property\SupportedCalendarComponentSet');
			}
			$values['components'] = implode(',', $properties[$sccs]->getValue());
		} elseif (isset($properties['components'])) {
			// Allow to provide components internally without having
			// to create a SupportedCalendarComponentSet object
			$values['components'] = $properties['components'];
		}

		$transp = '{' . Plugin::NS_CALDAV . '}schedule-calendar-transp';
		if (isset($properties[$transp])) {
			$values['transparent'] = (int) ($properties[$transp]->getValue() === 'transparent');
		}

		foreach ($this->propertyMap as $xmlName => [$dbName, $type]) {
			if (isset($properties[$xmlName])) {
				$values[$dbName] = $properties[$xmlName];
			}
		}

		[$calendarId, $calendarData] = $this->atomic(function () use ($values) {
			$query = $this->db->getQueryBuilder();
			$query->insert('calendars');
			foreach ($values as $column => $value) {
				$query->setValue($column, $query->createNamedParameter($value));
			}
			$query->executeStatement();
			$calendarId = $query->getLastInsertId();

			$calendarData = $this->getCalendarById($calendarId);
			return [$calendarId, $calendarData];
		}, $this->db);

		$this->dispatcher->dispatchTyped(new CalendarCreatedEvent((int)$calendarId, $calendarData));

		return $calendarId;
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
	 * @param PropPatch $propPatch
	 * @return void
	 */
	public function updateCalendar($calendarId, PropPatch $propPatch) {
		$this->atomic(function () use ($calendarId, $propPatch) {
			$supportedProperties = array_keys($this->propertyMap);
			$supportedProperties[] = '{' . Plugin::NS_CALDAV . '}schedule-calendar-transp';

			$propPatch->handle($supportedProperties, function ($mutations) use ($calendarId) {
				$newValues = [];
				foreach ($mutations as $propertyName => $propertyValue) {
					switch ($propertyName) {
						case '{' . Plugin::NS_CALDAV . '}schedule-calendar-transp':
							$fieldName = 'transparent';
							$newValues[$fieldName] = (int) ($propertyValue->getValue() === 'transparent');
							break;
						default:
							$fieldName = $this->propertyMap[$propertyName][0];
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
				$query->executeStatement();

				$this->addChange($calendarId, "", 2);

				$calendarData = $this->getCalendarById($calendarId);
				$shares = $this->getShares($calendarId);
				$this->dispatcher->dispatchTyped(new CalendarUpdatedEvent($calendarId, $calendarData, $shares, $mutations));

				return true;
			});
		}, $this->db);
	}

	/**
	 * Delete a calendar and all it's objects
	 *
	 * @param mixed $calendarId
	 * @return void
	 */
	public function deleteCalendar($calendarId, bool $forceDeletePermanently = false) {
		$this->atomic(function () use ($calendarId, $forceDeletePermanently) {
			// The calendar is deleted right away if this is either enforced by the caller
			// or the special contacts birthday calendar or when the preference of an empty
			// retention (0 seconds) is set, which signals a disabled trashbin.
			$calendarData = $this->getCalendarById($calendarId);
			$isBirthdayCalendar = isset($calendarData['uri']) && $calendarData['uri'] === BirthdayService::BIRTHDAY_CALENDAR_URI;
			$trashbinDisabled = $this->config->getAppValue(Application::APP_ID, RetentionService::RETENTION_CONFIG_KEY) === '0';
			if ($forceDeletePermanently || $isBirthdayCalendar || $trashbinDisabled) {
				$calendarData = $this->getCalendarById($calendarId);
				$shares = $this->getShares($calendarId);

				$qbDeleteCalendarObjectProps = $this->db->getQueryBuilder();
				$qbDeleteCalendarObjectProps->delete($this->dbObjectPropertiesTable)
					->where($qbDeleteCalendarObjectProps->expr()->eq('calendarid', $qbDeleteCalendarObjectProps->createNamedParameter($calendarId)))
					->andWhere($qbDeleteCalendarObjectProps->expr()->eq('calendartype', $qbDeleteCalendarObjectProps->createNamedParameter(self::CALENDAR_TYPE_CALENDAR)))
					->executeStatement();

				$qbDeleteCalendarObjects = $this->db->getQueryBuilder();
				$qbDeleteCalendarObjects->delete('calendarobjects')
					->where($qbDeleteCalendarObjects->expr()->eq('calendarid', $qbDeleteCalendarObjects->createNamedParameter($calendarId)))
					->andWhere($qbDeleteCalendarObjects->expr()->eq('calendartype', $qbDeleteCalendarObjects->createNamedParameter(self::CALENDAR_TYPE_CALENDAR)))
					->executeStatement();

				$qbDeleteCalendarChanges = $this->db->getQueryBuilder();
				$qbDeleteCalendarChanges->delete('calendarchanges')
					->where($qbDeleteCalendarChanges->expr()->eq('calendarid', $qbDeleteCalendarChanges->createNamedParameter($calendarId)))
					->andWhere($qbDeleteCalendarChanges->expr()->eq('calendartype', $qbDeleteCalendarChanges->createNamedParameter(self::CALENDAR_TYPE_CALENDAR)))
					->executeStatement();

				$this->calendarSharingBackend->deleteAllShares($calendarId);

				$qbDeleteCalendar = $this->db->getQueryBuilder();
				$qbDeleteCalendar->delete('calendars')
					->where($qbDeleteCalendar->expr()->eq('id', $qbDeleteCalendar->createNamedParameter($calendarId)))
					->executeStatement();

				// Only dispatch if we actually deleted anything
				if ($calendarData) {
					$this->dispatcher->dispatchTyped(new CalendarDeletedEvent($calendarId, $calendarData, $shares));
				}
			} else {
				$qbMarkCalendarDeleted = $this->db->getQueryBuilder();
				$qbMarkCalendarDeleted->update('calendars')
					->set('deleted_at', $qbMarkCalendarDeleted->createNamedParameter(time()))
					->where($qbMarkCalendarDeleted->expr()->eq('id', $qbMarkCalendarDeleted->createNamedParameter($calendarId)))
					->executeStatement();

				$calendarData = $this->getCalendarById($calendarId);
				$shares = $this->getShares($calendarId);
				if ($calendarData) {
					$this->dispatcher->dispatchTyped(new CalendarMovedToTrashEvent(
						$calendarId,
						$calendarData,
						$shares
					));
				}
			}
		}, $this->db);
	}

	public function restoreCalendar(int $id): void {
		$this->atomic(function () use ($id) {
			$qb = $this->db->getQueryBuilder();
			$update = $qb->update('calendars')
				->set('deleted_at', $qb->createNamedParameter(null))
				->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT));
			$update->executeStatement();

			$calendarData = $this->getCalendarById($id);
			$shares = $this->getShares($id);
			if ($calendarData === null) {
				throw new RuntimeException('Calendar data that was just written can\'t be read back. Check your database configuration.');
			}
			$this->dispatcher->dispatchTyped(new CalendarRestoredEvent(
				$id,
				$calendarData,
				$shares
			));
		}, $this->db);
	}

	/**
	 * Delete all of an user's shares
	 *
	 * @param string $principaluri
	 * @return void
	 */
	public function deleteAllSharesByUser($principaluri) {
		$this->calendarSharingBackend->deleteAllSharesByUser($principaluri);
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
	 * @param int $calendarType
	 * @return array
	 */
	public function getCalendarObjects($calendarId, $calendarType = self::CALENDAR_TYPE_CALENDAR):array {
		$query = $this->db->getQueryBuilder();
		$query->select(['id', 'uri', 'lastmodified', 'etag', 'calendarid', 'size', 'componenttype', 'classification'])
			->from('calendarobjects')
			->where($query->expr()->eq('calendarid', $query->createNamedParameter($calendarId)))
			->andWhere($query->expr()->eq('calendartype', $query->createNamedParameter($calendarType)))
			->andWhere($query->expr()->isNull('deleted_at'));
		$stmt = $query->executeQuery();

		$result = [];
		while (($row = $stmt->fetch()) !== false) {
			$result[] = [
				'id' => $row['id'],
				'uri' => $row['uri'],
				'lastmodified' => $row['lastmodified'],
				'etag' => '"' . $row['etag'] . '"',
				'calendarid' => $row['calendarid'],
				'size' => (int)$row['size'],
				'component' => strtolower($row['componenttype']),
				'classification' => (int)$row['classification']
			];
		}
		$stmt->closeCursor();

		return $result;
	}

	public function getDeletedCalendarObjects(int $deletedBefore): array {
		$query = $this->db->getQueryBuilder();
		$query->select(['co.id', 'co.uri', 'co.lastmodified', 'co.etag', 'co.calendarid', 'co.calendartype', 'co.size', 'co.componenttype', 'co.classification', 'co.deleted_at'])
			->from('calendarobjects', 'co')
			->join('co', 'calendars', 'c', $query->expr()->eq('c.id', 'co.calendarid', IQueryBuilder::PARAM_INT))
			->where($query->expr()->isNotNull('co.deleted_at'))
			->andWhere($query->expr()->lt('co.deleted_at', $query->createNamedParameter($deletedBefore)));
		$stmt = $query->executeQuery();

		$result = [];
		while (($row = $stmt->fetch()) !== false) {
			$result[] = [
				'id' => $row['id'],
				'uri' => $row['uri'],
				'lastmodified' => $row['lastmodified'],
				'etag' => '"' . $row['etag'] . '"',
				'calendarid' => (int) $row['calendarid'],
				'calendartype' => (int) $row['calendartype'],
				'size' => (int) $row['size'],
				'component' => strtolower($row['componenttype']),
				'classification' => (int) $row['classification'],
				'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_NEXTCLOUD . '}deleted-at' => $row['deleted_at'] === null ? $row['deleted_at'] : (int) $row['deleted_at'],
			];
		}
		$stmt->closeCursor();

		return $result;
	}

	/**
	 * Return all deleted calendar objects by the given principal that are not
	 * in deleted calendars.
	 *
	 * @param string $principalUri
	 * @return array
	 * @throws Exception
	 */
	public function getDeletedCalendarObjectsByPrincipal(string $principalUri): array {
		$query = $this->db->getQueryBuilder();
		$query->select(['co.id', 'co.uri', 'co.lastmodified', 'co.etag', 'co.calendarid', 'co.size', 'co.componenttype', 'co.classification', 'co.deleted_at'])
			->selectAlias('c.uri', 'calendaruri')
			->from('calendarobjects', 'co')
			->join('co', 'calendars', 'c', $query->expr()->eq('c.id', 'co.calendarid', IQueryBuilder::PARAM_INT))
			->where($query->expr()->eq('principaluri', $query->createNamedParameter($principalUri)))
			->andWhere($query->expr()->isNotNull('co.deleted_at'))
			->andWhere($query->expr()->isNull('c.deleted_at'));
		$stmt = $query->executeQuery();

		$result = [];
		while ($row = $stmt->fetch()) {
			$result[] = [
				'id' => $row['id'],
				'uri' => $row['uri'],
				'lastmodified' => $row['lastmodified'],
				'etag' => '"' . $row['etag'] . '"',
				'calendarid' => $row['calendarid'],
				'calendaruri' => $row['calendaruri'],
				'size' => (int)$row['size'],
				'component' => strtolower($row['componenttype']),
				'classification' => (int)$row['classification'],
				'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_NEXTCLOUD . '}deleted-at' => $row['deleted_at'] === null ? $row['deleted_at'] : (int) $row['deleted_at'],
			];
		}
		$stmt->closeCursor();

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
	 * @param int $calendarType
	 * @return array|null
	 */
	public function getCalendarObject($calendarId, $objectUri, int $calendarType = self::CALENDAR_TYPE_CALENDAR) {
		$key = $calendarId . '::' . $objectUri . '::' . $calendarType;
		if (isset($this->cachedObjects[$key])) {
			return $this->cachedObjects[$key];
		}
		$query = $this->db->getQueryBuilder();
		$query->select(['id', 'uri', 'lastmodified', 'etag', 'calendarid', 'size', 'calendardata', 'componenttype', 'classification', 'deleted_at'])
			->from('calendarobjects')
			->where($query->expr()->eq('calendarid', $query->createNamedParameter($calendarId)))
			->andWhere($query->expr()->eq('uri', $query->createNamedParameter($objectUri)))
			->andWhere($query->expr()->eq('calendartype', $query->createNamedParameter($calendarType)));
		$stmt = $query->executeQuery();
		$row = $stmt->fetch();
		$stmt->closeCursor();

		if (!$row) {
			return null;
		}

		$object = $this->rowToCalendarObject($row);
		$this->cachedObjects[$key] = $object;
		return $object;
	}

	private function rowToCalendarObject(array $row): array {
		return [
			'id' => $row['id'],
			'uri' => $row['uri'],
			'lastmodified' => $row['lastmodified'],
			'etag' => '"' . $row['etag'] . '"',
			'calendarid' => $row['calendarid'],
			'size' => (int)$row['size'],
			'calendardata' => $this->readBlob($row['calendardata']),
			'component' => strtolower($row['componenttype']),
			'classification' => (int)$row['classification'],
			'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_NEXTCLOUD . '}deleted-at' => $row['deleted_at'] === null ? $row['deleted_at'] : (int) $row['deleted_at'],
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
	 * @param int $calendarType
	 * @return array
	 */
	public function getMultipleCalendarObjects($calendarId, array $uris, $calendarType = self::CALENDAR_TYPE_CALENDAR):array {
		if (empty($uris)) {
			return [];
		}

		$chunks = array_chunk($uris, 100);
		$objects = [];

		$query = $this->db->getQueryBuilder();
		$query->select(['id', 'uri', 'lastmodified', 'etag', 'calendarid', 'size', 'calendardata', 'componenttype', 'classification'])
			->from('calendarobjects')
			->where($query->expr()->eq('calendarid', $query->createNamedParameter($calendarId)))
			->andWhere($query->expr()->in('uri', $query->createParameter('uri')))
			->andWhere($query->expr()->eq('calendartype', $query->createNamedParameter($calendarType)))
			->andWhere($query->expr()->isNull('deleted_at'));

		foreach ($chunks as $uris) {
			$query->setParameter('uri', $uris, IQueryBuilder::PARAM_STR_ARRAY);
			$result = $query->executeQuery();

			while ($row = $result->fetch()) {
				$objects[] = [
					'id' => $row['id'],
					'uri' => $row['uri'],
					'lastmodified' => $row['lastmodified'],
					'etag' => '"' . $row['etag'] . '"',
					'calendarid' => $row['calendarid'],
					'size' => (int)$row['size'],
					'calendardata' => $this->readBlob($row['calendardata']),
					'component' => strtolower($row['componenttype']),
					'classification' => (int)$row['classification']
				];
			}
			$result->closeCursor();
		}

		return $objects;
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
	 * @param int $calendarType
	 * @return string
	 */
	public function createCalendarObject($calendarId, $objectUri, $calendarData, $calendarType = self::CALENDAR_TYPE_CALENDAR) {
		$this->cachedObjects = [];
		$extraData = $this->getDenormalizedData($calendarData);

		return $this->atomic(function () use ($calendarId, $objectUri, $calendarData, $extraData, $calendarType) {
			// Try to detect duplicates
			$qb = $this->db->getQueryBuilder();
			$qb->select($qb->func()->count('*'))
				->from('calendarobjects')
				->where($qb->expr()->eq('calendarid', $qb->createNamedParameter($calendarId)))
				->andWhere($qb->expr()->eq('uid', $qb->createNamedParameter($extraData['uid'])))
				->andWhere($qb->expr()->eq('calendartype', $qb->createNamedParameter($calendarType)))
				->andWhere($qb->expr()->isNull('deleted_at'));
			$result = $qb->executeQuery();
			$count = (int) $result->fetchOne();
			$result->closeCursor();

			if ($count !== 0) {
				throw new BadRequest('Calendar object with uid already exists in this calendar collection.');
			}
			// For a more specific error message we also try to explicitly look up the UID but as a deleted entry
			$qbDel = $this->db->getQueryBuilder();
			$qbDel->select('*')
				->from('calendarobjects')
				->where($qbDel->expr()->eq('calendarid', $qbDel->createNamedParameter($calendarId)))
				->andWhere($qbDel->expr()->eq('uid', $qbDel->createNamedParameter($extraData['uid'])))
				->andWhere($qbDel->expr()->eq('calendartype', $qbDel->createNamedParameter($calendarType)))
				->andWhere($qbDel->expr()->isNotNull('deleted_at'));
			$result = $qbDel->executeQuery();
			$found = $result->fetch();
			$result->closeCursor();
			if ($found !== false) {
				// the object existed previously but has been deleted
				// remove the trashbin entry and continue as if it was a new object
				$this->deleteCalendarObject($calendarId, $found['uri']);
			}

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
					'calendartype' => $query->createNamedParameter($calendarType),
				])
				->executeStatement();

			$this->updateProperties($calendarId, $objectUri, $calendarData, $calendarType);
			$this->addChange($calendarId, $objectUri, 1, $calendarType);

			$objectRow = $this->getCalendarObject($calendarId, $objectUri, $calendarType);
			assert($objectRow !== null);

			if ($calendarType === self::CALENDAR_TYPE_CALENDAR) {
				$calendarRow = $this->getCalendarById($calendarId);
				$shares = $this->getShares($calendarId);

				$this->dispatcher->dispatchTyped(new CalendarObjectCreatedEvent($calendarId, $calendarRow, $shares, $objectRow));
			} else {
				$subscriptionRow = $this->getSubscriptionById($calendarId);

				$this->dispatcher->dispatchTyped(new CachedCalendarObjectCreatedEvent($calendarId, $subscriptionRow, [], $objectRow));
			}

			return '"' . $extraData['etag'] . '"';
		}, $this->db);
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
	 * @param int $calendarType
	 * @return string
	 */
	public function updateCalendarObject($calendarId, $objectUri, $calendarData, $calendarType = self::CALENDAR_TYPE_CALENDAR) {
		$this->cachedObjects = [];
		$extraData = $this->getDenormalizedData($calendarData);

		return $this->atomic(function () use ($calendarId, $objectUri, $calendarData, $extraData, $calendarType) {
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
				->andWhere($query->expr()->eq('calendartype', $query->createNamedParameter($calendarType)))
				->executeStatement();

			$this->updateProperties($calendarId, $objectUri, $calendarData, $calendarType);
			$this->addChange($calendarId, $objectUri, 2, $calendarType);

			$objectRow = $this->getCalendarObject($calendarId, $objectUri, $calendarType);
			if (is_array($objectRow)) {
				if ($calendarType === self::CALENDAR_TYPE_CALENDAR) {
					$calendarRow = $this->getCalendarById($calendarId);
					$shares = $this->getShares($calendarId);

					$this->dispatcher->dispatchTyped(new CalendarObjectUpdatedEvent($calendarId, $calendarRow, $shares, $objectRow));
				} else {
					$subscriptionRow = $this->getSubscriptionById($calendarId);

					$this->dispatcher->dispatchTyped(new CachedCalendarObjectUpdatedEvent($calendarId, $subscriptionRow, [], $objectRow));
				}
			}

			return '"' . $extraData['etag'] . '"';
		}, $this->db);
	}

	/**
	 * Moves a calendar object from calendar to calendar.
	 *
	 * @param int $sourceCalendarId
	 * @param int $targetCalendarId
	 * @param int $objectId
	 * @param string $oldPrincipalUri
	 * @param string $newPrincipalUri
	 * @param int $calendarType
	 * @return bool
	 * @throws Exception
	 */
	public function moveCalendarObject(int $sourceCalendarId, int $targetCalendarId, int $objectId, string $oldPrincipalUri, string $newPrincipalUri, int $calendarType = self::CALENDAR_TYPE_CALENDAR): bool {
		$this->cachedObjects = [];
		return $this->atomic(function () use ($sourceCalendarId, $targetCalendarId, $objectId, $oldPrincipalUri, $newPrincipalUri, $calendarType) {
			$object = $this->getCalendarObjectById($oldPrincipalUri, $objectId);
			if (empty($object)) {
				return false;
			}

			$query = $this->db->getQueryBuilder();
			$query->update('calendarobjects')
				->set('calendarid', $query->createNamedParameter($targetCalendarId, IQueryBuilder::PARAM_INT))
				->where($query->expr()->eq('id', $query->createNamedParameter($objectId, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
				->andWhere($query->expr()->eq('calendartype', $query->createNamedParameter($calendarType, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
				->executeStatement();

			$this->purgeProperties($sourceCalendarId, $objectId);
			$this->updateProperties($targetCalendarId, $object['uri'], $object['calendardata'], $calendarType);

			$this->addChange($sourceCalendarId, $object['uri'], 3, $calendarType);
			$this->addChange($targetCalendarId, $object['uri'], 1, $calendarType);

			$object = $this->getCalendarObjectById($newPrincipalUri, $objectId);
			// Calendar Object wasn't found - possibly because it was deleted in the meantime by a different client
			if (empty($object)) {
				return false;
			}

			$targetCalendarRow = $this->getCalendarById($targetCalendarId);
			// the calendar this event is being moved to does not exist any longer
			if (empty($targetCalendarRow)) {
				return false;
			}

			if ($calendarType === self::CALENDAR_TYPE_CALENDAR) {
				$sourceShares = $this->getShares($sourceCalendarId);
				$targetShares = $this->getShares($targetCalendarId);
				$sourceCalendarRow = $this->getCalendarById($sourceCalendarId);
				$this->dispatcher->dispatchTyped(new CalendarObjectMovedEvent($sourceCalendarId, $sourceCalendarRow, $targetCalendarId, $targetCalendarRow, $sourceShares, $targetShares, $object));
			}
			return true;
		}, $this->db);
	}


	/**
	 * @param int $calendarObjectId
	 * @param int $classification
	 */
	public function setClassification($calendarObjectId, $classification) {
		$this->cachedObjects = [];
		if (!in_array($classification, [
			self::CLASSIFICATION_PUBLIC, self::CLASSIFICATION_PRIVATE, self::CLASSIFICATION_CONFIDENTIAL
		])) {
			throw new \InvalidArgumentException();
		}
		$query = $this->db->getQueryBuilder();
		$query->update('calendarobjects')
			->set('classification', $query->createNamedParameter($classification))
			->where($query->expr()->eq('id', $query->createNamedParameter($calendarObjectId)))
			->executeStatement();
	}

	/**
	 * Deletes an existing calendar object.
	 *
	 * The object uri is only the basename, or filename and not a full path.
	 *
	 * @param mixed $calendarId
	 * @param string $objectUri
	 * @param int $calendarType
	 * @param bool $forceDeletePermanently
	 * @return void
	 */
	public function deleteCalendarObject($calendarId, $objectUri, $calendarType = self::CALENDAR_TYPE_CALENDAR, bool $forceDeletePermanently = false) {
		$this->cachedObjects = [];
		$this->atomic(function () use ($calendarId, $objectUri, $calendarType, $forceDeletePermanently) {
			$data = $this->getCalendarObject($calendarId, $objectUri, $calendarType);

			if ($data === null) {
				// Nothing to delete
				return;
			}

			if ($forceDeletePermanently || $this->config->getAppValue(Application::APP_ID, RetentionService::RETENTION_CONFIG_KEY) === '0') {
				$stmt = $this->db->prepare('DELETE FROM `*PREFIX*calendarobjects` WHERE `calendarid` = ? AND `uri` = ? AND `calendartype` = ?');
				$stmt->execute([$calendarId, $objectUri, $calendarType]);

				$this->purgeProperties($calendarId, $data['id']);

				if ($calendarType === self::CALENDAR_TYPE_CALENDAR) {
					$calendarRow = $this->getCalendarById($calendarId);
					$shares = $this->getShares($calendarId);

					$this->dispatcher->dispatchTyped(new CalendarObjectDeletedEvent($calendarId, $calendarRow, $shares, $data));
				} else {
					$subscriptionRow = $this->getSubscriptionById($calendarId);

					$this->dispatcher->dispatchTyped(new CachedCalendarObjectDeletedEvent($calendarId, $subscriptionRow, [], $data));
				}
			} else {
				$pathInfo = pathinfo($data['uri']);
				if (!empty($pathInfo['extension'])) {
					// Append a suffix to "free" the old URI for recreation
					$newUri = sprintf(
						"%s-deleted.%s",
						$pathInfo['filename'],
						$pathInfo['extension']
					);
				} else {
					$newUri = sprintf(
						"%s-deleted",
						$pathInfo['filename']
					);
				}

				// Try to detect conflicts before the DB does
				// As unlikely as it seems, this can happen when the user imports, then deletes, imports and deletes again
				$newObject = $this->getCalendarObject($calendarId, $newUri, $calendarType);
				if ($newObject !== null) {
					throw new Forbidden("A calendar object with URI $newUri already exists in calendar $calendarId, therefore this object can't be moved into the trashbin");
				}

				$qb = $this->db->getQueryBuilder();
				$markObjectDeletedQuery = $qb->update('calendarobjects')
					->set('deleted_at', $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT))
					->set('uri', $qb->createNamedParameter($newUri))
					->where(
						$qb->expr()->eq('calendarid', $qb->createNamedParameter($calendarId)),
						$qb->expr()->eq('calendartype', $qb->createNamedParameter($calendarType, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT),
						$qb->expr()->eq('uri', $qb->createNamedParameter($objectUri))
					);
				$markObjectDeletedQuery->executeStatement();

				$calendarData = $this->getCalendarById($calendarId);
				if ($calendarData !== null) {
					$this->dispatcher->dispatchTyped(
						new CalendarObjectMovedToTrashEvent(
							$calendarId,
							$calendarData,
							$this->getShares($calendarId),
							$data
						)
					);
				}
			}

			$this->addChange($calendarId, $objectUri, 3, $calendarType);
		}, $this->db);
	}

	/**
	 * @param mixed $objectData
	 *
	 * @throws Forbidden
	 */
	public function restoreCalendarObject(array $objectData): void {
		$this->cachedObjects = [];
		$this->atomic(function () use ($objectData) {
			$id = (int) $objectData['id'];
			$restoreUri = str_replace("-deleted.ics", ".ics", $objectData['uri']);
			$targetObject = $this->getCalendarObject(
				$objectData['calendarid'],
				$restoreUri
			);
			if ($targetObject !== null) {
				throw new Forbidden("Can not restore calendar $id because a calendar object with the URI $restoreUri already exists");
			}

			$qb = $this->db->getQueryBuilder();
			$update = $qb->update('calendarobjects')
				->set('uri', $qb->createNamedParameter($restoreUri))
				->set('deleted_at', $qb->createNamedParameter(null))
				->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT));
			$update->executeStatement();

			// Make sure this change is tracked in the changes table
			$qb2 = $this->db->getQueryBuilder();
			$selectObject = $qb2->select('calendardata', 'uri', 'calendarid', 'calendartype')
				->selectAlias('componenttype', 'component')
				->from('calendarobjects')
				->where($qb2->expr()->eq('id', $qb2->createNamedParameter($id, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT));
			$result = $selectObject->executeQuery();
			$row = $result->fetch();
			$result->closeCursor();
			if ($row === false) {
				// Welp, this should possibly not have happened, but let's ignore
				return;
			}
			$this->addChange($row['calendarid'], $row['uri'], 1, (int) $row['calendartype']);

			$calendarRow = $this->getCalendarById((int) $row['calendarid']);
			if ($calendarRow === null) {
				throw new RuntimeException('Calendar object data that was just written can\'t be read back. Check your database configuration.');
			}
			$this->dispatcher->dispatchTyped(
				new CalendarObjectRestoredEvent(
					(int) $objectData['calendarid'],
					$calendarRow,
					$this->getShares((int) $row['calendarid']),
					$row
				)
			);
		}, $this->db);
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
	 * A good example of how to interpret all these filters can also simply
	 * be found in Sabre\CalDAV\CalendarQueryFilter. This class is as correct
	 * as possible, so it gives you a good idea on what type of stuff you need
	 * to think of.
	 *
	 * @param mixed $calendarId
	 * @param array $filters
	 * @param int $calendarType
	 * @return array
	 */
	public function calendarQuery($calendarId, array $filters, $calendarType = self::CALENDAR_TYPE_CALENDAR):array {
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
			if ($componentType === 'VEVENT' && isset($filters['comp-filters'][0]['time-range']) && is_array($filters['comp-filters'][0]['time-range'])) {
				$timeRange = $filters['comp-filters'][0]['time-range'];

				// If start time OR the end time is not specified, we can do a
				// 100% accurate mysql query.
				if (!$filters['prop-filters'] && !$filters['comp-filters'][0]['comp-filters'] && !$filters['comp-filters'][0]['prop-filters'] && (!$timeRange['start'] || !$timeRange['end'])) {
					$requirePostFilter = false;
				}
			}
		}
		$query = $this->db->getQueryBuilder();
		$query->select(['id', 'uri', 'lastmodified', 'etag', 'calendarid', 'size', 'calendardata', 'componenttype', 'classification', 'deleted_at'])
			->from('calendarobjects')
			->where($query->expr()->eq('calendarid', $query->createNamedParameter($calendarId)))
			->andWhere($query->expr()->eq('calendartype', $query->createNamedParameter($calendarType)))
			->andWhere($query->expr()->isNull('deleted_at'));

		if ($componentType) {
			$query->andWhere($query->expr()->eq('componenttype', $query->createNamedParameter($componentType)));
		}

		if ($timeRange && $timeRange['start']) {
			$query->andWhere($query->expr()->gt('lastoccurence', $query->createNamedParameter($timeRange['start']->getTimeStamp())));
		}
		if ($timeRange && $timeRange['end']) {
			$query->andWhere($query->expr()->lt('firstoccurence', $query->createNamedParameter($timeRange['end']->getTimeStamp())));
		}

		$stmt = $query->executeQuery();

		$result = [];
		while ($row = $stmt->fetch()) {
			// if we leave it as a blob we can't read it both from the post filter and the rowToCalendarObject
			if (isset($row['calendardata'])) {
				$row['calendardata'] = $this->readBlob($row['calendardata']);
			}

			if ($requirePostFilter) {
				// validateFilterForObject will parse the calendar data
				// catch parsing errors
				try {
					$matches = $this->validateFilterForObject($row, $filters);
				} catch (ParseException $ex) {
					$this->logger->error('Caught parsing exception for calendar data. This usually indicates invalid calendar data. calendar-id:'.$calendarId.' uri:'.$row['uri'], [
						'app' => 'dav',
						'exception' => $ex,
					]);
					continue;
				} catch (InvalidDataException $ex) {
					$this->logger->error('Caught invalid data exception for calendar data. This usually indicates invalid calendar data. calendar-id:'.$calendarId.' uri:'.$row['uri'], [
						'app' => 'dav',
						'exception' => $ex,
					]);
					continue;
				}

				if (!$matches) {
					continue;
				}
			}
			$result[] = $row['uri'];
			$key = $calendarId . '::' . $row['uri'] . '::' . $calendarType;
			$this->cachedObjects[$key] = $this->rowToCalendarObject($row);
		}

		return $result;
	}

	/**
	 * custom Nextcloud search extension for CalDAV
	 *
	 * TODO - this should optionally cover cached calendar objects as well
	 *
	 * @param string $principalUri
	 * @param array $filters
	 * @param integer|null $limit
	 * @param integer|null $offset
	 * @return array
	 */
	public function calendarSearch($principalUri, array $filters, $limit = null, $offset = null) {
		return $this->atomic(function () use ($principalUri, $filters, $limit, $offset) {
			$calendars = $this->getCalendarsForUser($principalUri);
			$ownCalendars = [];
			$sharedCalendars = [];

			$uriMapper = [];

			foreach ($calendars as $calendar) {
				if ($calendar['{http://owncloud.org/ns}owner-principal'] === $principalUri) {
					$ownCalendars[] = $calendar['id'];
				} else {
					$sharedCalendars[] = $calendar['id'];
				}
				$uriMapper[$calendar['id']] = $calendar['uri'];
			}
			if (count($ownCalendars) === 0 && count($sharedCalendars) === 0) {
				return [];
			}

			$query = $this->db->getQueryBuilder();
			// Calendar id expressions
			$calendarExpressions = [];
			foreach ($ownCalendars as $id) {
				$calendarExpressions[] = $query->expr()->andX(
					$query->expr()->eq('c.calendarid',
						$query->createNamedParameter($id)),
					$query->expr()->eq('c.calendartype',
						$query->createNamedParameter(self::CALENDAR_TYPE_CALENDAR)));
			}
			foreach ($sharedCalendars as $id) {
				$calendarExpressions[] = $query->expr()->andX(
					$query->expr()->eq('c.calendarid',
						$query->createNamedParameter($id)),
					$query->expr()->eq('c.classification',
						$query->createNamedParameter(self::CLASSIFICATION_PUBLIC)),
					$query->expr()->eq('c.calendartype',
						$query->createNamedParameter(self::CALENDAR_TYPE_CALENDAR)));
			}

			if (count($calendarExpressions) === 1) {
				$calExpr = $calendarExpressions[0];
			} else {
				$calExpr = call_user_func_array([$query->expr(), 'orX'], $calendarExpressions);
			}

			// Component expressions
			$compExpressions = [];
			foreach ($filters['comps'] as $comp) {
				$compExpressions[] = $query->expr()
					->eq('c.componenttype', $query->createNamedParameter($comp));
			}

			if (count($compExpressions) === 1) {
				$compExpr = $compExpressions[0];
			} else {
				$compExpr = call_user_func_array([$query->expr(), 'orX'], $compExpressions);
			}

			if (!isset($filters['props'])) {
				$filters['props'] = [];
			}
			if (!isset($filters['params'])) {
				$filters['params'] = [];
			}

			$propParamExpressions = [];
			foreach ($filters['props'] as $prop) {
				$propParamExpressions[] = $query->expr()->andX(
					$query->expr()->eq('i.name', $query->createNamedParameter($prop)),
					$query->expr()->isNull('i.parameter')
				);
			}
			foreach ($filters['params'] as $param) {
				$propParamExpressions[] = $query->expr()->andX(
					$query->expr()->eq('i.name', $query->createNamedParameter($param['property'])),
					$query->expr()->eq('i.parameter', $query->createNamedParameter($param['parameter']))
				);
			}

			if (count($propParamExpressions) === 1) {
				$propParamExpr = $propParamExpressions[0];
			} else {
				$propParamExpr = call_user_func_array([$query->expr(), 'orX'], $propParamExpressions);
			}

			$query->select(['c.calendarid', 'c.uri'])
				->from($this->dbObjectPropertiesTable, 'i')
				->join('i', 'calendarobjects', 'c', $query->expr()->eq('i.objectid', 'c.id'))
				->where($calExpr)
				->andWhere($compExpr)
				->andWhere($propParamExpr)
				->andWhere($query->expr()->iLike('i.value',
					$query->createNamedParameter('%'.$this->db->escapeLikeParameter($filters['search-term']).'%')))
				->andWhere($query->expr()->isNull('deleted_at'));

			if ($offset) {
				$query->setFirstResult($offset);
			}
			if ($limit) {
				$query->setMaxResults($limit);
			}

			$stmt = $query->executeQuery();

			$result = [];
			while ($row = $stmt->fetch()) {
				$path = $uriMapper[$row['calendarid']] . '/' . $row['uri'];
				if (!in_array($path, $result)) {
					$result[] = $path;
				}
			}

			return $result;
		}, $this->db);
	}

	/**
	 * used for Nextcloud's calendar API
	 *
	 * @param array $calendarInfo
	 * @param string $pattern
	 * @param array $searchProperties
	 * @param array $options
	 * @param integer|null $limit
	 * @param integer|null $offset
	 *
	 * @return array
	 */
	public function search(
		array $calendarInfo,
		$pattern,
		array $searchProperties,
		array $options,
		$limit,
		$offset
	) {
		$outerQuery = $this->db->getQueryBuilder();
		$innerQuery = $this->db->getQueryBuilder();

		$innerQuery->selectDistinct('op.objectid')
			->from($this->dbObjectPropertiesTable, 'op')
			->andWhere($innerQuery->expr()->eq('op.calendarid',
				$outerQuery->createNamedParameter($calendarInfo['id'])))
			->andWhere($innerQuery->expr()->eq('op.calendartype',
				$outerQuery->createNamedParameter(self::CALENDAR_TYPE_CALENDAR)));

		$outerQuery->select('c.id', 'c.calendardata', 'c.componenttype', 'c.uid', 'c.uri')
			->from('calendarobjects', 'c')
			->where($outerQuery->expr()->isNull('deleted_at'));

		// only return public items for shared calendars for now
		if (isset($calendarInfo['{http://owncloud.org/ns}owner-principal']) === false || $calendarInfo['principaluri'] !== $calendarInfo['{http://owncloud.org/ns}owner-principal']) {
			$outerQuery->andWhere($outerQuery->expr()->eq('c.classification',
				$outerQuery->createNamedParameter(self::CLASSIFICATION_PUBLIC)));
		}

		if (!empty($searchProperties)) {
			$or = $innerQuery->expr()->orX();
			foreach ($searchProperties as $searchProperty) {
				$or->add($innerQuery->expr()->eq('op.name',
					$outerQuery->createNamedParameter($searchProperty)));
			}
			$innerQuery->andWhere($or);
		}

		if ($pattern !== '') {
			$innerQuery->andWhere($innerQuery->expr()->iLike('op.value',
				$outerQuery->createNamedParameter('%' .
					$this->db->escapeLikeParameter($pattern) . '%')));
		}

		if (isset($options['timerange'])) {
			if (isset($options['timerange']['start']) && $options['timerange']['start'] instanceof DateTimeInterface) {
				$outerQuery->andWhere($outerQuery->expr()->gt('lastoccurence',
					$outerQuery->createNamedParameter($options['timerange']['start']->getTimeStamp())));
			}
			if (isset($options['timerange']['end']) && $options['timerange']['end'] instanceof DateTimeInterface) {
				$outerQuery->andWhere($outerQuery->expr()->lt('firstoccurence',
					$outerQuery->createNamedParameter($options['timerange']['end']->getTimeStamp())));
			}
		}

		if (isset($options['uid'])) {
			$outerQuery->andWhere($outerQuery->expr()->eq('uid', $outerQuery->createNamedParameter($options['uid'])));
		}

		if (!empty($options['types'])) {
			$or = $outerQuery->expr()->orX();
			foreach ($options['types'] as $type) {
				$or->add($outerQuery->expr()->eq('componenttype',
					$outerQuery->createNamedParameter($type)));
			}
			$outerQuery->andWhere($or);
		}

		$outerQuery->andWhere($outerQuery->expr()->in('c.id', $outerQuery->createFunction($innerQuery->getSQL())));

		if ($offset) {
			$outerQuery->setFirstResult($offset);
		}
		if ($limit) {
			$outerQuery->setMaxResults($limit);
		}

		$result = $outerQuery->executeQuery();
		$calendarObjects = [];
		while (($row = $result->fetch()) !== false) {
			$start = $options['timerange']['start'] ?? null;
			$end = $options['timerange']['end'] ?? null;

			if ($start === null || !($start instanceof DateTimeInterface) || $end === null || !($end instanceof DateTimeInterface)) {
				// No filter required
				$calendarObjects[] = $row;
				continue;
			}

			$isValid = $this->validateFilterForObject($row, [
				'name' => 'VCALENDAR',
				'comp-filters' => [
					[
						'name' => 'VEVENT',
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
			if (is_resource($row['calendardata'])) {
				// Put the stream back to the beginning so it can be read another time
				rewind($row['calendardata']);
			}
			if ($isValid) {
				$calendarObjects[] = $row;
			}
		}
		$result->closeCursor();

		return array_map(function ($o) use ($options) {
			$calendarData = Reader::read($o['calendardata']);

			// Expand recurrences if an explicit time range is requested
			if ($calendarData instanceof VCalendar
				&& isset($options['timerange']['start'], $options['timerange']['end'])) {
				$calendarData = $calendarData->expand(
					$options['timerange']['start'],
					$options['timerange']['end'],
				);
			}

			$comps = $calendarData->getComponents();
			$objects = [];
			$timezones = [];
			foreach ($comps as $comp) {
				if ($comp instanceof VTimeZone) {
					$timezones[] = $comp;
				} else {
					$objects[] = $comp;
				}
			}

			return [
				'id' => $o['id'],
				'type' => $o['componenttype'],
				'uid' => $o['uid'],
				'uri' => $o['uri'],
				'objects' => array_map(function ($c) {
					return $this->transformSearchData($c);
				}, $objects),
				'timezones' => array_map(function ($c) {
					return $this->transformSearchData($c);
				}, $timezones),
			];
		}, $calendarObjects);
	}

	/**
	 * @param Component $comp
	 * @return array
	 */
	private function transformSearchData(Component $comp) {
		$data = [];
		/** @var Component[] $subComponents */
		$subComponents = $comp->getComponents();
		/** @var Property[] $properties */
		$properties = array_filter($comp->children(), function ($c) {
			return $c instanceof Property;
		});
		$validationRules = $comp->getValidationRules();

		foreach ($subComponents as $subComponent) {
			$name = $subComponent->name;
			if (!isset($data[$name])) {
				$data[$name] = [];
			}
			$data[$name][] = $this->transformSearchData($subComponent);
		}

		foreach ($properties as $property) {
			$name = $property->name;
			if (!isset($validationRules[$name])) {
				$validationRules[$name] = '*';
			}

			$rule = $validationRules[$property->name];
			if ($rule === '+' || $rule === '*') { // multiple
				if (!isset($data[$name])) {
					$data[$name] = [];
				}

				$data[$name][] = $this->transformSearchProperty($property);
			} else { // once
				$data[$name] = $this->transformSearchProperty($property);
			}
		}

		return $data;
	}

	/**
	 * @param Property $prop
	 * @return array
	 */
	private function transformSearchProperty(Property $prop) {
		// No need to check Date, as it extends DateTime
		if ($prop instanceof Property\ICalendar\DateTime) {
			$value = $prop->getDateTime();
		} else {
			$value = $prop->getValue();
		}

		return [
			$value,
			$prop->parameters()
		];
	}

	/**
	 * @param string $principalUri
	 * @param string $pattern
	 * @param array $componentTypes
	 * @param array $searchProperties
	 * @param array $searchParameters
	 * @param array $options
	 * @return array
	 */
	public function searchPrincipalUri(string $principalUri,
		string $pattern,
		array $componentTypes,
		array $searchProperties,
		array $searchParameters,
		array $options = []
	): array {
		return $this->atomic(function () use ($principalUri, $pattern, $componentTypes, $searchProperties, $searchParameters, $options) {
			$escapePattern = !\array_key_exists('escape_like_param', $options) || $options['escape_like_param'] !== false;

			$calendarObjectIdQuery = $this->db->getQueryBuilder();
			$calendarOr = $calendarObjectIdQuery->expr()->orX();
			$searchOr = $calendarObjectIdQuery->expr()->orX();

			// Fetch calendars and subscription
			$calendars = $this->getCalendarsForUser($principalUri);
			$subscriptions = $this->getSubscriptionsForUser($principalUri);
			foreach ($calendars as $calendar) {
				$calendarAnd = $calendarObjectIdQuery->expr()->andX();
				$calendarAnd->add($calendarObjectIdQuery->expr()->eq('cob.calendarid', $calendarObjectIdQuery->createNamedParameter((int)$calendar['id'])));
				$calendarAnd->add($calendarObjectIdQuery->expr()->eq('cob.calendartype', $calendarObjectIdQuery->createNamedParameter(self::CALENDAR_TYPE_CALENDAR)));

				// If it's shared, limit search to public events
				if (isset($calendar['{http://owncloud.org/ns}owner-principal'])
					&& $calendar['principaluri'] !== $calendar['{http://owncloud.org/ns}owner-principal']) {
					$calendarAnd->add($calendarObjectIdQuery->expr()->eq('co.classification', $calendarObjectIdQuery->createNamedParameter(self::CLASSIFICATION_PUBLIC)));
				}

				$calendarOr->add($calendarAnd);
			}
			foreach ($subscriptions as $subscription) {
				$subscriptionAnd = $calendarObjectIdQuery->expr()->andX();
				$subscriptionAnd->add($calendarObjectIdQuery->expr()->eq('cob.calendarid', $calendarObjectIdQuery->createNamedParameter((int)$subscription['id'])));
				$subscriptionAnd->add($calendarObjectIdQuery->expr()->eq('cob.calendartype', $calendarObjectIdQuery->createNamedParameter(self::CALENDAR_TYPE_SUBSCRIPTION)));

				// If it's shared, limit search to public events
				if (isset($subscription['{http://owncloud.org/ns}owner-principal'])
					&& $subscription['principaluri'] !== $subscription['{http://owncloud.org/ns}owner-principal']) {
					$subscriptionAnd->add($calendarObjectIdQuery->expr()->eq('co.classification', $calendarObjectIdQuery->createNamedParameter(self::CLASSIFICATION_PUBLIC)));
				}

				$calendarOr->add($subscriptionAnd);
			}

			foreach ($searchProperties as $property) {
				$propertyAnd = $calendarObjectIdQuery->expr()->andX();
				$propertyAnd->add($calendarObjectIdQuery->expr()->eq('cob.name', $calendarObjectIdQuery->createNamedParameter($property, IQueryBuilder::PARAM_STR)));
				$propertyAnd->add($calendarObjectIdQuery->expr()->isNull('cob.parameter'));

				$searchOr->add($propertyAnd);
			}
			foreach ($searchParameters as $property => $parameter) {
				$parameterAnd = $calendarObjectIdQuery->expr()->andX();
				$parameterAnd->add($calendarObjectIdQuery->expr()->eq('cob.name', $calendarObjectIdQuery->createNamedParameter($property, IQueryBuilder::PARAM_STR)));
				$parameterAnd->add($calendarObjectIdQuery->expr()->eq('cob.parameter', $calendarObjectIdQuery->createNamedParameter($parameter, IQueryBuilder::PARAM_STR_ARRAY)));

				$searchOr->add($parameterAnd);
			}

			if ($calendarOr->count() === 0) {
				return [];
			}
			if ($searchOr->count() === 0) {
				return [];
			}

			$calendarObjectIdQuery->selectDistinct('cob.objectid')
				->from($this->dbObjectPropertiesTable, 'cob')
				->leftJoin('cob', 'calendarobjects', 'co', $calendarObjectIdQuery->expr()->eq('co.id', 'cob.objectid'))
				->andWhere($calendarObjectIdQuery->expr()->in('co.componenttype', $calendarObjectIdQuery->createNamedParameter($componentTypes, IQueryBuilder::PARAM_STR_ARRAY)))
				->andWhere($calendarOr)
				->andWhere($searchOr)
				->andWhere($calendarObjectIdQuery->expr()->isNull('deleted_at'));

			if ('' !== $pattern) {
				if (!$escapePattern) {
					$calendarObjectIdQuery->andWhere($calendarObjectIdQuery->expr()->ilike('cob.value', $calendarObjectIdQuery->createNamedParameter($pattern)));
				} else {
					$calendarObjectIdQuery->andWhere($calendarObjectIdQuery->expr()->ilike('cob.value', $calendarObjectIdQuery->createNamedParameter('%' . $this->db->escapeLikeParameter($pattern) . '%')));
				}
			}

			if (isset($options['limit'])) {
				$calendarObjectIdQuery->setMaxResults($options['limit']);
			}
			if (isset($options['offset'])) {
				$calendarObjectIdQuery->setFirstResult($options['offset']);
			}
			if (isset($options['timerange'])) {
				if (isset($options['timerange']['start']) && $options['timerange']['start'] instanceof DateTimeInterface) {
					$calendarObjectIdQuery->andWhere($calendarObjectIdQuery->expr()->gt(
						'lastoccurence',
						$calendarObjectIdQuery->createNamedParameter($options['timerange']['start']->getTimeStamp()),
					));
				}
				if (isset($options['timerange']['end']) && $options['timerange']['end'] instanceof DateTimeInterface) {
					$calendarObjectIdQuery->andWhere($calendarObjectIdQuery->expr()->lt(
						'firstoccurence',
						$calendarObjectIdQuery->createNamedParameter($options['timerange']['end']->getTimeStamp()),
					));
				}
			}

			$result = $calendarObjectIdQuery->executeQuery();
			$matches = [];
			while (($row = $result->fetch()) !== false) {
				$matches[] = (int) $row['objectid'];
			}
			$result->closeCursor();

			$query = $this->db->getQueryBuilder();
			$query->select('calendardata', 'uri', 'calendarid', 'calendartype')
				->from('calendarobjects')
				->where($query->expr()->in('id', $query->createNamedParameter($matches, IQueryBuilder::PARAM_INT_ARRAY)));

			$result = $query->executeQuery();
			$calendarObjects = [];
			while (($array = $result->fetch()) !== false) {
				$array['calendarid'] = (int)$array['calendarid'];
				$array['calendartype'] = (int)$array['calendartype'];
				$array['calendardata'] = $this->readBlob($array['calendardata']);

				$calendarObjects[] = $array;
			}
			$result->closeCursor();
			return $calendarObjects;
		}, $this->db);
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
	public function getCalendarObjectByUID($principalUri, $uid) {
		$query = $this->db->getQueryBuilder();
		$query->selectAlias('c.uri', 'calendaruri')->selectAlias('co.uri', 'objecturi')
			->from('calendarobjects', 'co')
			->leftJoin('co', 'calendars', 'c', $query->expr()->eq('co.calendarid', 'c.id'))
			->where($query->expr()->eq('c.principaluri', $query->createNamedParameter($principalUri)))
			->andWhere($query->expr()->eq('co.uid', $query->createNamedParameter($uid)))
			->andWhere($query->expr()->isNull('co.deleted_at'));
		$stmt = $query->executeQuery();
		$row = $stmt->fetch();
		$stmt->closeCursor();
		if ($row) {
			return $row['calendaruri'] . '/' . $row['objecturi'];
		}

		return null;
	}

	public function getCalendarObjectById(string $principalUri, int $id): ?array {
		$query = $this->db->getQueryBuilder();
		$query->select(['co.id', 'co.uri', 'co.lastmodified', 'co.etag', 'co.calendarid', 'co.size', 'co.calendardata', 'co.componenttype', 'co.classification', 'co.deleted_at'])
			->selectAlias('c.uri', 'calendaruri')
			->from('calendarobjects', 'co')
			->join('co', 'calendars', 'c', $query->expr()->eq('c.id', 'co.calendarid', IQueryBuilder::PARAM_INT))
			->where($query->expr()->eq('c.principaluri', $query->createNamedParameter($principalUri)))
			->andWhere($query->expr()->eq('co.id', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT));
		$stmt = $query->executeQuery();
		$row = $stmt->fetch();
		$stmt->closeCursor();

		if (!$row) {
			return null;
		}

		return [
			'id' => $row['id'],
			'uri' => $row['uri'],
			'lastmodified' => $row['lastmodified'],
			'etag' => '"' . $row['etag'] . '"',
			'calendarid' => $row['calendarid'],
			'calendaruri' => $row['calendaruri'],
			'size' => (int)$row['size'],
			'calendardata' => $this->readBlob($row['calendardata']),
			'component' => strtolower($row['componenttype']),
			'classification' => (int)$row['classification'],
			'deleted_at' => isset($row['deleted_at']) ? ((int) $row['deleted_at']) : null,
		];
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
	 * @param int|null $limit
	 * @param int $calendarType
	 * @return array
	 */
	public function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null, $calendarType = self::CALENDAR_TYPE_CALENDAR) {
		return $this->atomic(function () use ($calendarId, $syncToken, $syncLevel, $limit, $calendarType) {
			// Current synctoken
			$qb = $this->db->getQueryBuilder();
			$qb->select('synctoken')
				->from('calendars')
				->where(
					$qb->expr()->eq('id', $qb->createNamedParameter($calendarId))
				);
			$stmt = $qb->executeQuery();
			$currentToken = $stmt->fetchOne();

			if ($currentToken === false) {
				return null;
			}

			$result = [
				'syncToken' => $currentToken,
				'added' => [],
				'modified' => [],
				'deleted' => [],
			];

			if ($syncToken) {
				$qb = $this->db->getQueryBuilder();

				$qb->select('uri', 'operation')
					->from('calendarchanges')
					->where(
						$qb->expr()->andX(
							$qb->expr()->gte('synctoken', $qb->createNamedParameter($syncToken)),
							$qb->expr()->lt('synctoken', $qb->createNamedParameter($currentToken)),
							$qb->expr()->eq('calendarid', $qb->createNamedParameter($calendarId)),
							$qb->expr()->eq('calendartype', $qb->createNamedParameter($calendarType))
						)
					)->orderBy('synctoken');
				if (is_int($limit) && $limit > 0) {
					$qb->setMaxResults($limit);
				}

				// Fetching all changes
				$stmt = $qb->executeQuery();
				$changes = [];

				// This loop ensures that any duplicates are overwritten, only the
				// last change on a node is relevant.
				while ($row = $stmt->fetch()) {
					$changes[$row['uri']] = $row['operation'];
				}
				$stmt->closeCursor();

				foreach ($changes as $uri => $operation) {
					switch ($operation) {
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
			} else {
				// No synctoken supplied, this is the initial sync.
				$qb = $this->db->getQueryBuilder();
				$qb->select('uri')
					->from('calendarobjects')
					->where(
						$qb->expr()->andX(
							$qb->expr()->eq('calendarid', $qb->createNamedParameter($calendarId)),
							$qb->expr()->eq('calendartype', $qb->createNamedParameter($calendarType))
						)
					);
				$stmt = $qb->executeQuery();
				$result['added'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
				$stmt->closeCursor();
			}
			return $result;
		}, $this->db);
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
	public function getSubscriptionsForUser($principalUri) {
		$fields = array_column($this->subscriptionPropertyMap, 0);
		$fields[] = 'id';
		$fields[] = 'uri';
		$fields[] = 'source';
		$fields[] = 'principaluri';
		$fields[] = 'lastmodified';
		$fields[] = 'synctoken';

		$query = $this->db->getQueryBuilder();
		$query->select($fields)
			->from('calendarsubscriptions')
			->where($query->expr()->eq('principaluri', $query->createNamedParameter($principalUri)))
			->orderBy('calendarorder', 'asc');
		$stmt = $query->executeQuery();

		$subscriptions = [];
		while ($row = $stmt->fetch()) {
			$subscription = [
				'id' => $row['id'],
				'uri' => $row['uri'],
				'principaluri' => $row['principaluri'],
				'source' => $row['source'],
				'lastmodified' => $row['lastmodified'],

				'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VTODO', 'VEVENT']),
				'{http://sabredav.org/ns}sync-token' => $row['synctoken']?$row['synctoken']:'0',
			];

			$subscriptions[] = $this->rowToSubscription($row, $subscription);
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
	public function createSubscription($principalUri, $uri, array $properties) {
		if (!isset($properties['{http://calendarserver.org/ns/}source'])) {
			throw new Forbidden('The {http://calendarserver.org/ns/}source property is required when creating subscriptions');
		}

		$values = [
			'principaluri' => $principalUri,
			'uri' => $uri,
			'source' => $properties['{http://calendarserver.org/ns/}source']->getHref(),
			'lastmodified' => time(),
		];

		$propertiesBoolean = ['striptodos', 'stripalarms', 'stripattachments'];

		foreach ($this->subscriptionPropertyMap as $xmlName => [$dbName, $type]) {
			if (array_key_exists($xmlName, $properties)) {
				$values[$dbName] = $properties[$xmlName];
				if (in_array($dbName, $propertiesBoolean)) {
					$values[$dbName] = true;
				}
			}
		}

		[$subscriptionId, $subscriptionRow] = $this->atomic(function () use ($values) {
			$valuesToInsert = [];
			$query = $this->db->getQueryBuilder();
			foreach (array_keys($values) as $name) {
				$valuesToInsert[$name] = $query->createNamedParameter($values[$name]);
			}
			$query->insert('calendarsubscriptions')
				->values($valuesToInsert)
				->executeStatement();

			$subscriptionId = $query->getLastInsertId();

			$subscriptionRow = $this->getSubscriptionById($subscriptionId);
			return [$subscriptionId, $subscriptionRow];
		}, $this->db);

		$this->dispatcher->dispatchTyped(new SubscriptionCreatedEvent($subscriptionId, $subscriptionRow));

		return $subscriptionId;
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
	public function updateSubscription($subscriptionId, PropPatch $propPatch) {
		$this->atomic(function () use ($subscriptionId, $propPatch) {
			$supportedProperties = array_keys($this->subscriptionPropertyMap);
			$supportedProperties[] = '{http://calendarserver.org/ns/}source';

			$propPatch->handle($supportedProperties, function ($mutations) use ($subscriptionId) {
				$newValues = [];

				foreach ($mutations as $propertyName => $propertyValue) {
					if ($propertyName === '{http://calendarserver.org/ns/}source') {
						$newValues['source'] = $propertyValue->getHref();
					} else {
						$fieldName = $this->subscriptionPropertyMap[$propertyName][0];
						$newValues[$fieldName] = $propertyValue;
					}
				}

				$query = $this->db->getQueryBuilder();
				$query->update('calendarsubscriptions')
					->set('lastmodified', $query->createNamedParameter(time()));
				foreach ($newValues as $fieldName => $value) {
					$query->set($fieldName, $query->createNamedParameter($value));
				}
				$query->where($query->expr()->eq('id', $query->createNamedParameter($subscriptionId)))
					->executeStatement();

				$subscriptionRow = $this->getSubscriptionById($subscriptionId);
				$this->dispatcher->dispatchTyped(new SubscriptionUpdatedEvent((int)$subscriptionId, $subscriptionRow, [], $mutations));

				return true;
			});
		}, $this->db);
	}

	/**
	 * Deletes a subscription.
	 *
	 * @param mixed $subscriptionId
	 * @return void
	 */
	public function deleteSubscription($subscriptionId) {
		$this->atomic(function () use ($subscriptionId) {
			$subscriptionRow = $this->getSubscriptionById($subscriptionId);

			$query = $this->db->getQueryBuilder();
			$query->delete('calendarsubscriptions')
				->where($query->expr()->eq('id', $query->createNamedParameter($subscriptionId)))
				->executeStatement();

			$query = $this->db->getQueryBuilder();
			$query->delete('calendarobjects')
				->where($query->expr()->eq('calendarid', $query->createNamedParameter($subscriptionId)))
				->andWhere($query->expr()->eq('calendartype', $query->createNamedParameter(self::CALENDAR_TYPE_SUBSCRIPTION)))
				->executeStatement();

			$query->delete('calendarchanges')
				->where($query->expr()->eq('calendarid', $query->createNamedParameter($subscriptionId)))
				->andWhere($query->expr()->eq('calendartype', $query->createNamedParameter(self::CALENDAR_TYPE_SUBSCRIPTION)))
				->executeStatement();

			$query->delete($this->dbObjectPropertiesTable)
				->where($query->expr()->eq('calendarid', $query->createNamedParameter($subscriptionId)))
				->andWhere($query->expr()->eq('calendartype', $query->createNamedParameter(self::CALENDAR_TYPE_SUBSCRIPTION)))
				->executeStatement();

			if ($subscriptionRow) {
				$this->dispatcher->dispatchTyped(new SubscriptionDeletedEvent((int)$subscriptionId, $subscriptionRow, []));
			}
		}, $this->db);
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
	public function getSchedulingObject($principalUri, $objectUri) {
		$query = $this->db->getQueryBuilder();
		$stmt = $query->select(['uri', 'calendardata', 'lastmodified', 'etag', 'size'])
			->from('schedulingobjects')
			->where($query->expr()->eq('principaluri', $query->createNamedParameter($principalUri)))
			->andWhere($query->expr()->eq('uri', $query->createNamedParameter($objectUri)))
			->executeQuery();

		$row = $stmt->fetch();

		if (!$row) {
			return null;
		}

		return [
			'uri' => $row['uri'],
			'calendardata' => $row['calendardata'],
			'lastmodified' => $row['lastmodified'],
			'etag' => '"' . $row['etag'] . '"',
			'size' => (int)$row['size'],
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
	public function getSchedulingObjects($principalUri) {
		$query = $this->db->getQueryBuilder();
		$stmt = $query->select(['uri', 'calendardata', 'lastmodified', 'etag', 'size'])
				->from('schedulingobjects')
				->where($query->expr()->eq('principaluri', $query->createNamedParameter($principalUri)))
				->executeQuery();

		$results = [];
		while (($row = $stmt->fetch()) !== false) {
			$results[] = [
				'calendardata' => $row['calendardata'],
				'uri' => $row['uri'],
				'lastmodified' => $row['lastmodified'],
				'etag' => '"' . $row['etag'] . '"',
				'size' => (int)$row['size'],
			];
		}
		$stmt->closeCursor();

		return $results;
	}

	/**
	 * Deletes a scheduling object from the inbox collection.
	 *
	 * @param string $principalUri
	 * @param string $objectUri
	 * @return void
	 */
	public function deleteSchedulingObject($principalUri, $objectUri) {
		$this->cachedObjects = [];
		$query = $this->db->getQueryBuilder();
		$query->delete('schedulingobjects')
				->where($query->expr()->eq('principaluri', $query->createNamedParameter($principalUri)))
				->andWhere($query->expr()->eq('uri', $query->createNamedParameter($objectUri)))
				->executeStatement();
	}

	/**
	 * Creates a new scheduling object. This should land in a users' inbox.
	 *
	 * @param string $principalUri
	 * @param string $objectUri
	 * @param string $objectData
	 * @return void
	 */
	public function createSchedulingObject($principalUri, $objectUri, $objectData) {
		$this->cachedObjects = [];
		$query = $this->db->getQueryBuilder();
		$query->insert('schedulingobjects')
			->values([
				'principaluri' => $query->createNamedParameter($principalUri),
				'calendardata' => $query->createNamedParameter($objectData, IQueryBuilder::PARAM_LOB),
				'uri' => $query->createNamedParameter($objectUri),
				'lastmodified' => $query->createNamedParameter(time()),
				'etag' => $query->createNamedParameter(md5($objectData)),
				'size' => $query->createNamedParameter(strlen($objectData))
			])
			->executeStatement();
	}

	/**
	 * Adds a change record to the calendarchanges table.
	 *
	 * @param mixed $calendarId
	 * @param string $objectUri
	 * @param int $operation 1 = add, 2 = modify, 3 = delete.
	 * @param int $calendarType
	 * @return void
	 */
	protected function addChange(int $calendarId, string $objectUri, int $operation, int $calendarType = self::CALENDAR_TYPE_CALENDAR): void {
		$this->cachedObjects = [];
		$table = $calendarType === self::CALENDAR_TYPE_CALENDAR ? 'calendars': 'calendarsubscriptions';

		$this->atomic(function () use ($calendarId, $objectUri, $operation, $calendarType, $table) {
			$query = $this->db->getQueryBuilder();
			$query->select('synctoken')
				->from($table)
				->where($query->expr()->eq('id', $query->createNamedParameter($calendarId)));
			$result = $query->executeQuery();
			$syncToken = (int)$result->fetchOne();
			$result->closeCursor();

			$query = $this->db->getQueryBuilder();
			$query->insert('calendarchanges')
				->values([
					'uri' => $query->createNamedParameter($objectUri),
					'synctoken' => $query->createNamedParameter($syncToken),
					'calendarid' => $query->createNamedParameter($calendarId),
					'operation' => $query->createNamedParameter($operation),
					'calendartype' => $query->createNamedParameter($calendarType),
				])
				->executeStatement();

			$query = $this->db->getQueryBuilder();
			$query->update($table)
				->set('synctoken', $query->createNamedParameter($syncToken + 1, IQueryBuilder::PARAM_INT))
				->where($query->expr()->eq('id', $query->createNamedParameter($calendarId)))
				->executeStatement();
		}, $this->db);
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
	public function getDenormalizedData(string $calendarData): array {
		$vObject = Reader::read($calendarData);
		$vEvents = [];
		$componentType = null;
		$component = null;
		$firstOccurrence = null;
		$lastOccurrence = null;
		$uid = null;
		$classification = self::CLASSIFICATION_PUBLIC;
		$hasDTSTART = false;
		foreach ($vObject->getComponents() as $component) {
			if ($component->name !== 'VTIMEZONE') {
				// Finding all VEVENTs, and track them
				if ($component->name === 'VEVENT') {
					$vEvents[] = $component;
					if ($component->DTSTART) {
						$hasDTSTART = true;
					}
				}
				// Track first component type and uid
				if ($uid === null) {
					$componentType = $component->name;
					$uid = (string)$component->UID;
				}
			}
		}
		if (!$componentType) {
			throw new BadRequest('Calendar objects must have a VJOURNAL, VEVENT or VTODO component');
		}

		if ($hasDTSTART) {
			$component = $vEvents[0];

			// Finding the last occurrence is a bit harder
			if (!isset($component->RRULE) && count($vEvents) === 1) {
				$firstOccurrence = $component->DTSTART->getDateTime()->getTimeStamp();
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
				$it = new EventIterator($vEvents);
				$maxDate = new DateTime(self::MAX_DATE);
				$firstOccurrence = $it->getDtStart()->getTimestamp();
				if ($it->isInfinite()) {
					$lastOccurrence = $maxDate->getTimestamp();
				} else {
					$end = $it->getDtEnd();
					while ($it->valid() && $end < $maxDate) {
						$end = $it->getDtEnd();
						$it->next();
					}
					$lastOccurrence = $end->getTimestamp();
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
			'lastOccurence' => is_null($lastOccurrence) ? null : max(0, $lastOccurrence),
			'uid' => $uid,
			'classification' => $classification
		];
	}

	/**
	 * @param $cardData
	 * @return bool|string
	 */
	private function readBlob($cardData) {
		if (is_resource($cardData)) {
			return stream_get_contents($cardData);
		}

		return $cardData;
	}

	/**
	 * @param list<array{href: string, commonName: string, readOnly: bool}> $add
	 * @param list<string> $remove
	 */
	public function updateShares(IShareable $shareable, array $add, array $remove): void {
		$this->atomic(function () use ($shareable, $add, $remove) {
			$calendarId = $shareable->getResourceId();
			$calendarRow = $this->getCalendarById($calendarId);
			if ($calendarRow === null) {
				throw new \RuntimeException('Trying to update shares for non-existing calendar: ' . $calendarId);
			}
			$oldShares = $this->getShares($calendarId);

			$this->calendarSharingBackend->updateShares($shareable, $add, $remove);

			$this->dispatcher->dispatchTyped(new CalendarShareUpdatedEvent($calendarId, $calendarRow, $oldShares, $add, $remove));
		}, $this->db);
	}

	/**
	 * @return list<array{href: string, commonName: string, status: int, readOnly: bool, '{http://owncloud.org/ns}principal': string, '{http://owncloud.org/ns}group-share': bool}>
	 */
	public function getShares(int $resourceId): array {
		return $this->calendarSharingBackend->getShares($resourceId);
	}

	public function preloadShares(array $resourceIds): void {
		$this->calendarSharingBackend->preloadShares($resourceIds);
	}

	/**
	 * @param boolean $value
	 * @param \OCA\DAV\CalDAV\Calendar $calendar
	 * @return string|null
	 */
	public function setPublishStatus($value, $calendar) {
		return $this->atomic(function () use ($value, $calendar) {
			$calendarId = $calendar->getResourceId();
			$calendarData = $this->getCalendarById($calendarId);

			$query = $this->db->getQueryBuilder();
			if ($value) {
				$publicUri = $this->random->generate(16, ISecureRandom::CHAR_HUMAN_READABLE);
				$query->insert('dav_shares')
					->values([
						'principaluri' => $query->createNamedParameter($calendar->getPrincipalURI()),
						'type' => $query->createNamedParameter('calendar'),
						'access' => $query->createNamedParameter(self::ACCESS_PUBLIC),
						'resourceid' => $query->createNamedParameter($calendar->getResourceId()),
						'publicuri' => $query->createNamedParameter($publicUri)
					]);
				$query->executeStatement();

				$this->dispatcher->dispatchTyped(new CalendarPublishedEvent($calendarId, $calendarData, $publicUri));
				return $publicUri;
			}
			$query->delete('dav_shares')
				->where($query->expr()->eq('resourceid', $query->createNamedParameter($calendar->getResourceId())))
				->andWhere($query->expr()->eq('access', $query->createNamedParameter(self::ACCESS_PUBLIC)));
			$query->executeStatement();

			$this->dispatcher->dispatchTyped(new CalendarUnpublishedEvent($calendarId, $calendarData));
			return null;
		}, $this->db);
	}

	/**
	 * @param \OCA\DAV\CalDAV\Calendar $calendar
	 * @return mixed
	 */
	public function getPublishStatus($calendar) {
		$query = $this->db->getQueryBuilder();
		$result = $query->select('publicuri')
			->from('dav_shares')
			->where($query->expr()->eq('resourceid', $query->createNamedParameter($calendar->getResourceId())))
			->andWhere($query->expr()->eq('access', $query->createNamedParameter(self::ACCESS_PUBLIC)))
			->executeQuery();

		$row = $result->fetch();
		$result->closeCursor();
		return $row ? reset($row) : false;
	}

	/**
	 * @param int $resourceId
	 * @param list<array{privilege: string, principal: string, protected: bool}> $acl
	 * @return list<array{privilege: string, principal: string, protected: bool}>
	 */
	public function applyShareAcl(int $resourceId, array $acl): array {
		return $this->calendarSharingBackend->applyShareAcl($resourceId, $acl);
	}

	/**
	 * update properties table
	 *
	 * @param int $calendarId
	 * @param string $objectUri
	 * @param string $calendarData
	 * @param int $calendarType
	 */
	public function updateProperties($calendarId, $objectUri, $calendarData, $calendarType = self::CALENDAR_TYPE_CALENDAR) {
		$this->cachedObjects = [];
		$this->atomic(function () use ($calendarId, $objectUri, $calendarData, $calendarType) {
			$objectId = $this->getCalendarObjectId($calendarId, $objectUri, $calendarType);

			try {
				$vCalendar = $this->readCalendarData($calendarData);
			} catch (\Exception $ex) {
				return;
			}

			$this->purgeProperties($calendarId, $objectId);

			$query = $this->db->getQueryBuilder();
			$query->insert($this->dbObjectPropertiesTable)
				->values(
					[
						'calendarid' => $query->createNamedParameter($calendarId),
						'calendartype' => $query->createNamedParameter($calendarType),
						'objectid' => $query->createNamedParameter($objectId),
						'name' => $query->createParameter('name'),
						'parameter' => $query->createParameter('parameter'),
						'value' => $query->createParameter('value'),
					]
				);

			$indexComponents = ['VEVENT', 'VJOURNAL', 'VTODO'];
			foreach ($vCalendar->getComponents() as $component) {
				if (!in_array($component->name, $indexComponents)) {
					continue;
				}

				foreach ($component->children() as $property) {
					if (in_array($property->name, self::INDEXED_PROPERTIES, true)) {
						$value = $property->getValue();
						// is this a shitty db?
						if (!$this->db->supports4ByteText()) {
							$value = preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xEF\xBF\xBD", $value);
						}
						$value = mb_strcut($value, 0, 254);

						$query->setParameter('name', $property->name);
						$query->setParameter('parameter', null);
						$query->setParameter('value', $value);
						$query->executeStatement();
					}

					if (array_key_exists($property->name, self::$indexParameters)) {
						$parameters = $property->parameters();
						$indexedParametersForProperty = self::$indexParameters[$property->name];

						foreach ($parameters as $key => $value) {
							if (in_array($key, $indexedParametersForProperty)) {
								// is this a shitty db?
								if ($this->db->supports4ByteText()) {
									$value = preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xEF\xBF\xBD", $value);
								}

								$query->setParameter('name', $property->name);
								$query->setParameter('parameter', mb_strcut($key, 0, 254));
								$query->setParameter('value', mb_strcut($value, 0, 254));
								$query->executeStatement();
							}
						}
					}
				}
			}
		}, $this->db);
	}

	/**
	 * deletes all birthday calendars
	 */
	public function deleteAllBirthdayCalendars() {
		$this->atomic(function () {
			$query = $this->db->getQueryBuilder();
			$result = $query->select(['id'])->from('calendars')
				->where($query->expr()->eq('uri', $query->createNamedParameter(BirthdayService::BIRTHDAY_CALENDAR_URI)))
				->executeQuery();

			while (($row = $result->fetch()) !== false) {
				$this->deleteCalendar(
					$row['id'],
					true // No data to keep in the trashbin, if the user re-enables then we regenerate
				);
			}
			$result->closeCursor();
		}, $this->db);
	}

	/**
	 * @param $subscriptionId
	 */
	public function purgeAllCachedEventsForSubscription($subscriptionId) {
		$this->atomic(function () use ($subscriptionId) {
			$query = $this->db->getQueryBuilder();
			$query->select('uri')
				->from('calendarobjects')
				->where($query->expr()->eq('calendarid', $query->createNamedParameter($subscriptionId)))
				->andWhere($query->expr()->eq('calendartype', $query->createNamedParameter(self::CALENDAR_TYPE_SUBSCRIPTION)));
			$stmt = $query->executeQuery();

			$uris = [];
			while (($row = $stmt->fetch()) !== false) {
				$uris[] = $row['uri'];
			}
			$stmt->closeCursor();

			$query = $this->db->getQueryBuilder();
			$query->delete('calendarobjects')
				->where($query->expr()->eq('calendarid', $query->createNamedParameter($subscriptionId)))
				->andWhere($query->expr()->eq('calendartype', $query->createNamedParameter(self::CALENDAR_TYPE_SUBSCRIPTION)))
				->executeStatement();

			$query->delete('calendarchanges')
				->where($query->expr()->eq('calendarid', $query->createNamedParameter($subscriptionId)))
				->andWhere($query->expr()->eq('calendartype', $query->createNamedParameter(self::CALENDAR_TYPE_SUBSCRIPTION)))
				->executeStatement();

			$query->delete($this->dbObjectPropertiesTable)
				->where($query->expr()->eq('calendarid', $query->createNamedParameter($subscriptionId)))
				->andWhere($query->expr()->eq('calendartype', $query->createNamedParameter(self::CALENDAR_TYPE_SUBSCRIPTION)))
				->executeStatement();

			foreach ($uris as $uri) {
				$this->addChange($subscriptionId, $uri, 3, self::CALENDAR_TYPE_SUBSCRIPTION);
			}
		}, $this->db);
	}

	/**
	 * Move a calendar from one user to another
	 *
	 * @param string $uriName
	 * @param string $uriOrigin
	 * @param string $uriDestination
	 * @param string $newUriName (optional) the new uriName
	 */
	public function moveCalendar($uriName, $uriOrigin, $uriDestination, $newUriName = null) {
		$query = $this->db->getQueryBuilder();
		$query->update('calendars')
			->set('principaluri', $query->createNamedParameter($uriDestination))
			->set('uri', $query->createNamedParameter($newUriName ?: $uriName))
			->where($query->expr()->eq('principaluri', $query->createNamedParameter($uriOrigin)))
			->andWhere($query->expr()->eq('uri', $query->createNamedParameter($uriName)))
			->executeStatement();
	}

	/**
	 * read VCalendar data into a VCalendar object
	 *
	 * @param string $objectData
	 * @return VCalendar
	 */
	protected function readCalendarData($objectData) {
		return Reader::read($objectData);
	}

	/**
	 * delete all properties from a given calendar object
	 *
	 * @param int $calendarId
	 * @param int $objectId
	 */
	protected function purgeProperties($calendarId, $objectId) {
		$this->cachedObjects = [];
		$query = $this->db->getQueryBuilder();
		$query->delete($this->dbObjectPropertiesTable)
			->where($query->expr()->eq('objectid', $query->createNamedParameter($objectId)))
			->andWhere($query->expr()->eq('calendarid', $query->createNamedParameter($calendarId)));
		$query->executeStatement();
	}

	/**
	 * get ID from a given calendar object
	 *
	 * @param int $calendarId
	 * @param string $uri
	 * @param int $calendarType
	 * @return int
	 */
	protected function getCalendarObjectId($calendarId, $uri, $calendarType):int {
		$query = $this->db->getQueryBuilder();
		$query->select('id')
			->from('calendarobjects')
			->where($query->expr()->eq('uri', $query->createNamedParameter($uri)))
			->andWhere($query->expr()->eq('calendarid', $query->createNamedParameter($calendarId)))
			->andWhere($query->expr()->eq('calendartype', $query->createNamedParameter($calendarType)));

		$result = $query->executeQuery();
		$objectIds = $result->fetch();
		$result->closeCursor();

		if (!isset($objectIds['id'])) {
			throw new \InvalidArgumentException('Calendarobject does not exists: ' . $uri);
		}

		return (int)$objectIds['id'];
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function pruneOutdatedSyncTokens(int $keep = 10_000): int {
		if ($keep < 0) {
			throw new \InvalidArgumentException();
		}

		$query = $this->db->getQueryBuilder();
		$query->select($query->func()->max('id'))
			->from('calendarchanges');

		$result = $query->executeQuery();
		$maxId = (int) $result->fetchOne();
		$result->closeCursor();
		if (!$maxId || $maxId < $keep) {
			return 0;
		}

		$query = $this->db->getQueryBuilder();
		$query->delete('calendarchanges')
			->where($query->expr()->lte('id', $query->createNamedParameter($maxId - $keep, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT));
		return $query->executeStatement();
	}

	/**
	 * return legacy endpoint principal name to new principal name
	 *
	 * @param $principalUri
	 * @param $toV2
	 * @return string
	 */
	private function convertPrincipal($principalUri, $toV2) {
		if ($this->principalBackend->getPrincipalPrefix() === 'principals') {
			[, $name] = Uri\split($principalUri);
			if ($toV2 === true) {
				return "principals/users/$name";
			}
			return "principals/$name";
		}
		return $principalUri;
	}

	/**
	 * adds information about an owner to the calendar data
	 *
	 */
	private function addOwnerPrincipalToCalendar(array $calendarInfo): array {
		$ownerPrincipalKey = '{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal';
		$displaynameKey = '{' . \OCA\DAV\DAV\Sharing\Plugin::NS_NEXTCLOUD . '}owner-displayname';
		if (isset($calendarInfo[$ownerPrincipalKey])) {
			$uri = $calendarInfo[$ownerPrincipalKey];
		} else {
			$uri = $calendarInfo['principaluri'];
		}

		$principalInformation = $this->principalBackend->getPrincipalByPath($uri);
		if (isset($principalInformation['{DAV:}displayname'])) {
			$calendarInfo[$displaynameKey] = $principalInformation['{DAV:}displayname'];
		}
		return $calendarInfo;
	}

	private function addResourceTypeToCalendar(array $row, array $calendar): array {
		if (isset($row['deleted_at'])) {
			// Columns is set and not null -> this is a deleted calendar
			// we send a custom resourcetype to hide the deleted calendar
			// from ordinary DAV clients, but the Calendar app will know
			// how to handle this special resource.
			$calendar['{DAV:}resourcetype'] = new DAV\Xml\Property\ResourceType([
				'{DAV:}collection',
				sprintf('{%s}deleted-calendar', \OCA\DAV\DAV\Sharing\Plugin::NS_NEXTCLOUD),
			]);
		}
		return $calendar;
	}

	/**
	 * Amend the calendar info with database row data
	 *
	 * @param array $row
	 * @param array $calendar
	 *
	 * @return array
	 */
	private function rowToCalendar($row, array $calendar): array {
		foreach ($this->propertyMap as $xmlName => [$dbName, $type]) {
			$value = $row[$dbName];
			if ($value !== null) {
				settype($value, $type);
			}
			$calendar[$xmlName] = $value;
		}
		return $calendar;
	}

	/**
	 * Amend the subscription info with database row data
	 *
	 * @param array $row
	 * @param array $subscription
	 *
	 * @return array
	 */
	private function rowToSubscription($row, array $subscription): array {
		foreach ($this->subscriptionPropertyMap as $xmlName => [$dbName, $type]) {
			$value = $row[$dbName];
			if ($value !== null) {
				settype($value, $type);
			}
			$subscription[$xmlName] = $value;
		}
		return $subscription;
	}
}
