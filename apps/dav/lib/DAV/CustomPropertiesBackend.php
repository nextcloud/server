<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\DAV;

use Exception;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\CalendarHome;
use OCA\DAV\CalDAV\CalendarObject;
use OCA\DAV\CalDAV\DefaultCalendarValidator;
use OCA\DAV\CalDAV\Integration\ExternalCalendar;
use OCA\DAV\CalDAV\Outbox;
use OCA\DAV\CalDAV\Trashbin\TrashbinHome;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Db\PropertyMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;
use Sabre\CalDAV\Schedule\Inbox;
use Sabre\DAV\Exception as DavException;
use Sabre\DAV\PropertyStorage\Backend\BackendInterface;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\DAV\Xml\Property\Complex;
use Sabre\DAV\Xml\Property\Href;
use Sabre\DAV\Xml\Property\LocalHref;
use Sabre\Xml\ParseException;
use Sabre\Xml\Service as XmlService;

use function array_intersect;

class CustomPropertiesBackend implements BackendInterface {

	/** @var string */
	private const TABLE_NAME = 'properties';

	/**
	 * Value is stored as string.
	 */
	public const PROPERTY_TYPE_STRING = 1;

	/**
	 * Value is stored as XML fragment.
	 */
	public const PROPERTY_TYPE_XML = 2;

	/**
	 * Value is stored as a property object.
	 */
	public const PROPERTY_TYPE_OBJECT = 3;

	/**
	 * Value is stored as a {DAV:}href string.
	 */
	public const PROPERTY_TYPE_HREF = 4;

	/**
	 * Ignored properties
	 *
	 * @var string[]
	 */
	private const IGNORED_PROPERTIES = [
		'{DAV:}getcontentlength',
		'{DAV:}getcontenttype',
		'{DAV:}getetag',
		'{DAV:}quota-used-bytes',
		'{DAV:}quota-available-bytes',
	];

	/**
	 * Allowed properties for the oc/nc namespace, all other properties in the namespace are ignored
	 *
	 * @var string[]
	 */
	private const ALLOWED_NC_PROPERTIES = [
		'{http://owncloud.org/ns}calendar-enabled',
		'{http://owncloud.org/ns}enabled',
	];

	/**
	 * Properties set by one user, readable by all others
	 *
	 * @var string[]
	 */
	private const PUBLISHED_READ_ONLY_PROPERTIES = [
		'{urn:ietf:params:xml:ns:caldav}calendar-availability',
		'{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL',
	];

	/**
	 * Map of custom XML elements to parse when trying to deserialize an instance of
	 * \Sabre\DAV\Xml\Property\Complex to find a more specialized PROPERTY_TYPE_*
	 */
	private const COMPLEX_XML_ELEMENT_MAP = [
		'{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL' => Href::class,
	];

	/**
	 * Map of well-known property names to default values
	 */
	private const PROPERTY_DEFAULT_VALUES = [
		'{http://owncloud.org/ns}calendar-enabled' => '1',
	];

	/**
	 * Properties cache
	 */
	private array $userCache = [];
	private array $publishedCache = [];
	private XmlService $xmlService;

	/**
	 * @param Tree $tree node tree
	 * @param IDBConnection $connection database connection
	 * @param IUser $user owner of the tree and properties
	 */
	public function __construct(
		private Server $server,
		private Tree $tree,
		private IDBConnection $connection,
		private IUser $user,
		private PropertyMapper $propertyMapper,
		private DefaultCalendarValidator $defaultCalendarValidator,
	) {
		$this->xmlService = new XmlService();
		$this->xmlService->elementMap = array_merge(
			$this->xmlService->elementMap,
			self::COMPLEX_XML_ELEMENT_MAP,
		);
	}

	/**
	 * Fetches properties for a path.
	 *
	 * @param string $path
	 * @param PropFind $propFind
	 * @return void
	 */
	public function propFind($path, PropFind $propFind) {
		$requestedProps = $propFind->get404Properties();

		$requestedProps = array_filter(
			$requestedProps,
			$this->isPropertyAllowed(...),
		);

		// substr of calendars/ => path is inside the CalDAV component
		// two '/' => this a calendar (no calendar-home nor calendar object)
		if (str_starts_with($path, 'calendars/') && substr_count($path, '/') === 2) {
			$allRequestedProps = $propFind->getRequestedProperties();
			$customPropertiesForShares = [
				'{DAV:}displayname',
				'{urn:ietf:params:xml:ns:caldav}calendar-description',
				'{urn:ietf:params:xml:ns:caldav}calendar-timezone',
				'{http://apple.com/ns/ical/}calendar-order',
				'{http://apple.com/ns/ical/}calendar-color',
				'{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp',
			];

			foreach ($customPropertiesForShares as $customPropertyForShares) {
				if (in_array($customPropertyForShares, $allRequestedProps)) {
					$requestedProps[] = $customPropertyForShares;
				}
			}
		}

		// substr of addressbooks/ => path is inside the CardDAV component
		// three '/' => this a addressbook (no addressbook-home nor contact object)
		if (str_starts_with($path, 'addressbooks/') && substr_count($path, '/') === 3) {
			$allRequestedProps = $propFind->getRequestedProperties();
			$customPropertiesForShares = [
				'{DAV:}displayname',
			];

			foreach ($customPropertiesForShares as $customPropertyForShares) {
				if (in_array($customPropertyForShares, $allRequestedProps, true)) {
					$requestedProps[] = $customPropertyForShares;
				}
			}
		}

		// substr of principals/users/ => path is a user principal
		// two '/' => this a principal collection (and not some child object)
		if (str_starts_with($path, 'principals/users/') && substr_count($path, '/') === 2) {
			$allRequestedProps = $propFind->getRequestedProperties();
			$customProperties = [
				'{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL',
			];

			foreach ($customProperties as $customProperty) {
				if (in_array($customProperty, $allRequestedProps, true)) {
					$requestedProps[] = $customProperty;
				}
			}
		}

		if (empty($requestedProps)) {
			return;
		}

		$node = $this->tree->getNodeForPath($path);
		if ($node instanceof Directory && $propFind->getDepth() !== 0) {
			$this->cacheDirectory($path, $node);
		}

		if ($node instanceof CalendarHome && $propFind->getDepth() !== 0) {
			$backend = $node->getCalDAVBackend();
			if ($backend instanceof CalDavBackend) {
				$this->cacheCalendars($node, $requestedProps);
			}
		}

		if ($node instanceof CalendarObject) {
			// No custom properties supported on individual events
			return;
		}

		// First fetch the published properties (set by another user), then get the ones set by
		// the current user. If both are set then the latter as priority.
		foreach ($this->getPublishedProperties($path, $requestedProps) as $propName => $propValue) {
			try {
				$this->validateProperty($path, $propName, $propValue);
			} catch (DavException $e) {
				continue;
			}
			$propFind->set($propName, $propValue);
		}
		foreach ($this->getUserProperties($path, $requestedProps) as $propName => $propValue) {
			try {
				$this->validateProperty($path, $propName, $propValue);
			} catch (DavException $e) {
				continue;
			}
			$propFind->set($propName, $propValue);
		}
	}

	private function isPropertyAllowed(string $property): bool {
		if (in_array($property, self::IGNORED_PROPERTIES)) {
			return false;
		}
		if (str_starts_with($property, '{http://owncloud.org/ns}') || str_starts_with($property, '{http://nextcloud.org/ns}')) {
			return in_array($property, self::ALLOWED_NC_PROPERTIES);
		}
		return true;
	}

	/**
	 * Updates properties for a path
	 *
	 * @param string $path
	 * @param PropPatch $propPatch
	 *
	 * @return void
	 */
	public function propPatch($path, PropPatch $propPatch) {
		$propPatch->handleRemaining(function ($changedProps) use ($path) {
			return $this->updateProperties($path, $changedProps);
		});
	}

	/**
	 * This method is called after a node is deleted.
	 *
	 * @param string $path path of node for which to delete properties
	 */
	public function delete($path) {
		$statement = $this->connection->prepare(
			'DELETE FROM `*PREFIX*properties` WHERE `userid` = ? AND `propertypath` = ?'
		);
		$statement->execute([$this->user->getUID(), $this->formatPath($path)]);
		$statement->closeCursor();

		unset($this->userCache[$path]);
	}

	/**
	 * This method is called after a successful MOVE
	 *
	 * @param string $source
	 * @param string $destination
	 *
	 * @return void
	 */
	public function move($source, $destination) {
		$statement = $this->connection->prepare(
			'UPDATE `*PREFIX*properties` SET `propertypath` = ?'
			. ' WHERE `userid` = ? AND `propertypath` = ?'
		);
		$statement->execute([$this->formatPath($destination), $this->user->getUID(), $this->formatPath($source)]);
		$statement->closeCursor();
	}

	/**
	 * Validate the value of a property. Will throw if a value is invalid.
	 *
	 * @throws DavException The value of the property is invalid
	 */
	private function validateProperty(string $path, string $propName, mixed $propValue): void {
		switch ($propName) {
			case '{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL':
				/** @var Href $propValue */
				$href = $propValue->getHref();
				if ($href === null) {
					throw new DavException('Href is empty');
				}

				// $path is the principal here as this prop is only set on principals
				$node = $this->tree->getNodeForPath($href);
				if (!($node instanceof Calendar) || $node->getOwner() !== $path) {
					throw new DavException('No such calendar');
				}

				$this->defaultCalendarValidator->validateScheduleDefaultCalendar($node);
				break;
		}
	}

	/**
	 * @param string $path
	 * @param string[] $requestedProperties
	 *
	 * @return array
	 */
	private function getPublishedProperties(string $path, array $requestedProperties): array {
		$allowedProps = array_intersect(self::PUBLISHED_READ_ONLY_PROPERTIES, $requestedProperties);

		if (empty($allowedProps)) {
			return [];
		}

		if (isset($this->publishedCache[$path])) {
			return $this->publishedCache[$path];
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME)
			->where($qb->expr()->eq('propertypath', $qb->createNamedParameter($path)));
		$result = $qb->executeQuery();
		$props = [];
		while ($row = $result->fetch()) {
			$props[$row['propertyname']] = $this->decodeValueFromDatabase($row['propertyvalue'], $row['valuetype']);
		}
		$result->closeCursor();
		$this->publishedCache[$path] = $props;
		return $props;
	}

	/**
	 * prefetch all user properties in a directory
	 */
	private function cacheDirectory(string $path, Directory $node): void {
		$prefix = ltrim($path . '/', '/');
		$query = $this->connection->getQueryBuilder();
		$query->select('name', 'p.propertypath', 'p.propertyname', 'p.propertyvalue', 'p.valuetype')
			->from('filecache', 'f')
			->hintShardKey('storage', $node->getNode()->getMountPoint()->getNumericStorageId())
			->leftJoin('f', 'properties', 'p', $query->expr()->eq('p.propertypath', $query->func()->concat(
				$query->createNamedParameter($prefix),
				'f.name'
			)),
			)
			->where($query->expr()->eq('parent', $query->createNamedParameter($node->getInternalFileId(), IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->orX(
				$query->expr()->eq('p.userid', $query->createNamedParameter($this->user->getUID())),
				$query->expr()->isNull('p.userid'),
			));
		$result = $query->executeQuery();

		$propsByPath = [];

		while ($row = $result->fetch()) {
			$childPath = $prefix . $row['name'];
			if (!isset($propsByPath[$childPath])) {
				$propsByPath[$childPath] = [];
			}
			if (isset($row['propertyname'])) {
				$propsByPath[$childPath][$row['propertyname']] = $this->decodeValueFromDatabase($row['propertyvalue'], $row['valuetype']);
			}
		}
		$this->userCache = array_merge($this->userCache, $propsByPath);
	}

	private function cacheCalendars(CalendarHome $node, array $requestedProperties): void {
		$calendars = $node->getChildren();

		$users = [];
		foreach ($calendars as $calendar) {
			if ($calendar instanceof Calendar) {
				$user = str_replace('principals/users/', '', $calendar->getPrincipalURI());
				if (!isset($users[$user])) {
					$users[$user] = ['calendars/' . $user];
				}
				$users[$user][] = 'calendars/' . $user . '/' . $calendar->getUri();
			} elseif ($calendar instanceof Inbox || $calendar instanceof Outbox || $calendar instanceof TrashbinHome || $calendar instanceof ExternalCalendar) {
				if ($calendar->getOwner()) {
					$user = str_replace('principals/users/', '', $calendar->getOwner());
					if (!isset($users[$user])) {
						$users[$user] = ['calendars/' . $user];
					}
					$users[$user][] = 'calendars/' . $user . '/' . $calendar->getName();
				}
			}
		}

		// user properties
		$properties = $this->propertyMapper->findPropertiesByPathsAndUsers($users);

		$propsByPath = [];
		foreach ($users as $paths) {
			foreach ($paths as $path) {
				$propsByPath[$path] = [];
			}
		}

		foreach ($properties as $property) {
			$propsByPath[$property->getPropertypath()][$property->getPropertyname()] = $this->decodeValueFromDatabase($property->getPropertyvalue(), $property->getValuetype());
		}
		$this->userCache = array_merge($this->userCache, $propsByPath);

		// published properties
		$allowedProps = array_intersect(self::PUBLISHED_READ_ONLY_PROPERTIES, $requestedProperties);
		if (empty($allowedProps)) {
			return;
		}
		$paths = [];
		foreach ($users as $nestedPaths) {
			$paths = array_merge($paths, $nestedPaths);
		}
		$paths = array_unique($paths);

		$propsByPath = array_fill_keys(array_values($paths), []);
		$properties = $this->propertyMapper->findPropertiesByPaths($paths, $allowedProps);
		foreach ($properties as $property) {
			$propsByPath[$property->getPropertypath()][$property->getPropertyname()] = $this->decodeValueFromDatabase($property->getPropertyvalue(), $property->getValuetype());
		}
		$this->publishedCache = array_merge($this->publishedCache, $propsByPath);
	}

	/**
	 * Returns a list of properties for the given path and current user
	 *
	 * @param string $path
	 * @param array $requestedProperties requested properties or empty array for "all"
	 * @return array
	 * @note The properties list is a list of propertynames the client
	 * requested, encoded as xmlnamespace#tagName, for example:
	 * http://www.example.org/namespace#author If the array is empty, all
	 * properties should be returned
	 */
	private function getUserProperties(string $path, array $requestedProperties) {
		if (isset($this->userCache[$path])) {
			return $this->userCache[$path];
		}

		// TODO: chunking if more than 1000 properties
		$sql = 'SELECT * FROM `*PREFIX*properties` WHERE `userid` = ? AND `propertypath` = ?';

		$whereValues = [$this->user->getUID(), $this->formatPath($path)];
		$whereTypes = [null, null];

		if (!empty($requestedProperties)) {
			// request only a subset
			$sql .= ' AND `propertyname` in (?)';
			$whereValues[] = $requestedProperties;
			$whereTypes[] = IQueryBuilder::PARAM_STR_ARRAY;
		}

		$result = $this->connection->executeQuery(
			$sql,
			$whereValues,
			$whereTypes
		);

		$props = [];
		while ($row = $result->fetch()) {
			$props[$row['propertyname']] = $this->decodeValueFromDatabase($row['propertyvalue'], $row['valuetype']);
		}

		$result->closeCursor();

		$this->userCache[$path] = $props;
		return $props;
	}

	private function isPropertyDefaultValue(string $name, mixed $value): bool {
		if (!isset(self::PROPERTY_DEFAULT_VALUES[$name])) {
			return false;
		}

		return self::PROPERTY_DEFAULT_VALUES[$name] === $value;
	}

	/**
	 * @throws Exception
	 */
	private function updateProperties(string $path, array $properties): bool {
		// TODO: use "insert or update" strategy ?
		$existing = $this->getUserProperties($path, []);
		try {
			$this->connection->beginTransaction();
			foreach ($properties as $propertyName => $propertyValue) {
				// common parameters for all queries
				$dbParameters = [
					'userid' => $this->user->getUID(),
					'propertyPath' => $this->formatPath($path),
					'propertyName' => $propertyName,
				];

				// If it was null or set to the default value, we need to delete the property
				if (is_null($propertyValue) || $this->isPropertyDefaultValue($propertyName, $propertyValue)) {
					if (array_key_exists($propertyName, $existing)) {
						$deleteQuery = $deleteQuery ?? $this->createDeleteQuery();
						$deleteQuery
							->setParameters($dbParameters)
							->executeStatement();
					}
				} else {
					[$value, $valueType] = $this->encodeValueForDatabase(
						$path,
						$propertyName,
						$propertyValue,
					);
					$dbParameters['propertyValue'] = $value;
					$dbParameters['valueType'] = $valueType;

					if (!array_key_exists($propertyName, $existing)) {
						$insertQuery = $insertQuery ?? $this->createInsertQuery();
						$insertQuery
							->setParameters($dbParameters)
							->executeStatement();
					} else {
						$updateQuery = $updateQuery ?? $this->createUpdateQuery();
						$updateQuery
							->setParameters($dbParameters)
							->executeStatement();
					}
				}
			}

			$this->connection->commit();
			unset($this->userCache[$path]);
		} catch (Exception $e) {
			$this->connection->rollBack();
			throw $e;
		}

		return true;
	}

	/**
	 * long paths are hashed to ensure they fit in the database
	 *
	 * @param string $path
	 * @return string
	 */
	private function formatPath(string $path): string {
		if (strlen($path) > 250) {
			return sha1($path);
		}

		return $path;
	}

	private static function checkIsArrayOfScalar(string $name, array $array): void {
		foreach ($array as $item) {
			if (is_array($item)) {
				self::checkIsArrayOfScalar($name, $item);
			} elseif ($item !== null && !is_scalar($item)) {
				throw new DavException(
					"Property \"$name\" has an invalid value of array containing " . gettype($item),
				);
			}
		}
	}

	/**
	 * @throws ParseException If parsing a \Sabre\DAV\Xml\Property\Complex value fails
	 * @throws DavException If the property value is invalid
	 */
	private function encodeValueForDatabase(string $path, string $name, mixed $value): array {
		// Try to parse a more specialized property type first
		if ($value instanceof Complex) {
			$xml = $this->xmlService->write($name, [$value], $this->server->getBaseUri());
			$value = $this->xmlService->parse($xml, $this->server->getBaseUri()) ?? $value;
		}

		if ($name === '{urn:ietf:params:xml:ns:caldav}schedule-default-calendar-URL') {
			$value = $this->encodeDefaultCalendarUrl($value);
		}

		try {
			$this->validateProperty($path, $name, $value);
		} catch (DavException $e) {
			throw new DavException(
				"Property \"$name\" has an invalid value: " . $e->getMessage(),
				0,
				$e,
			);
		}

		if (is_scalar($value)) {
			$valueType = self::PROPERTY_TYPE_STRING;
		} elseif ($value instanceof Complex) {
			$valueType = self::PROPERTY_TYPE_XML;
			$value = $value->getXml();
		} elseif ($value instanceof Href) {
			$valueType = self::PROPERTY_TYPE_HREF;
			$value = $value->getHref();
		} else {
			if (is_array($value)) {
				// For array only allow scalar values
				self::checkIsArrayOfScalar($name, $value);
			} elseif (!is_object($value)) {
				throw new DavException(
					"Property \"$name\" has an invalid value of type " . gettype($value),
				);
			} else {
				if (!str_starts_with($value::class, 'Sabre\\DAV\\Xml\\Property\\')
					&& !str_starts_with($value::class, 'Sabre\\CalDAV\\Xml\\Property\\')
					&& !str_starts_with($value::class, 'Sabre\\CardDAV\\Xml\\Property\\')
					&& !str_starts_with($value::class, 'OCA\\DAV\\')) {
					throw new DavException(
						"Property \"$name\" has an invalid value of class " . $value::class,
					);
				}
			}
			$valueType = self::PROPERTY_TYPE_OBJECT;
			// serialize produces null character
			// these can not be properly stored in some databases and need to be replaced
			$value = str_replace(chr(0), '\x00', serialize($value));
		}
		return [$value, $valueType];
	}

	/**
	 * @return mixed|Complex|string
	 */
	private function decodeValueFromDatabase(string $value, int $valueType): mixed {
		switch ($valueType) {
			case self::PROPERTY_TYPE_XML:
				return new Complex($value);
			case self::PROPERTY_TYPE_HREF:
				return new Href($value);
			case self::PROPERTY_TYPE_OBJECT:
				if (preg_match('/^a:/', $value)) {
					// Array, unserialize only scalar values
					return unserialize(str_replace('\x00', chr(0), $value), ['allowed_classes' => false]);
				}
				if (!preg_match('/^O\:\d+\:\"(OCA\\\\DAV\\\\|Sabre\\\\(Cal|Card)?DAV\\\\Xml\\\\Property\\\\)/', $value)) {
					throw new \LogicException('Found an object class serialized in DB that is not allowed');
				}
				// some databases can not handel null characters, these are custom encoded during serialization
				// this custom encoding needs to be first reversed before unserializing
				return unserialize(str_replace('\x00', chr(0), $value));
			default:
				return $value;
		};
	}

	private function encodeDefaultCalendarUrl(Href $value): Href {
		$href = $value->getHref();
		if ($href === null) {
			return $value;
		}

		if (!str_starts_with($href, '/')) {
			return $value;
		}

		try {
			// Build path relative to the dav base URI to be used later to find the node
			$value = new LocalHref($this->server->calculateUri($href) . '/');
		} catch (DavException\Forbidden) {
			// Not existing calendars will be handled later when the value is validated
		}

		return $value;
	}

	private function createDeleteQuery(): IQueryBuilder {
		$deleteQuery = $this->connection->getQueryBuilder();
		$deleteQuery->delete('properties')
			->where($deleteQuery->expr()->eq('userid', $deleteQuery->createParameter('userid')))
			->andWhere($deleteQuery->expr()->eq('propertypath', $deleteQuery->createParameter('propertyPath')))
			->andWhere($deleteQuery->expr()->eq('propertyname', $deleteQuery->createParameter('propertyName')));
		return $deleteQuery;
	}

	private function createInsertQuery(): IQueryBuilder {
		$insertQuery = $this->connection->getQueryBuilder();
		$insertQuery->insert('properties')
			->values([
				'userid' => $insertQuery->createParameter('userid'),
				'propertypath' => $insertQuery->createParameter('propertyPath'),
				'propertyname' => $insertQuery->createParameter('propertyName'),
				'propertyvalue' => $insertQuery->createParameter('propertyValue'),
				'valuetype' => $insertQuery->createParameter('valueType'),
			]);
		return $insertQuery;
	}

	private function createUpdateQuery(): IQueryBuilder {
		$updateQuery = $this->connection->getQueryBuilder();
		$updateQuery->update('properties')
			->set('propertyvalue', $updateQuery->createParameter('propertyValue'))
			->set('valuetype', $updateQuery->createParameter('valueType'))
			->where($updateQuery->expr()->eq('userid', $updateQuery->createParameter('userid')))
			->andWhere($updateQuery->expr()->eq('propertypath', $updateQuery->createParameter('propertyPath')))
			->andWhere($updateQuery->expr()->eq('propertyname', $updateQuery->createParameter('propertyName')));
		return $updateQuery;
	}
}
