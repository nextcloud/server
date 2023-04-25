<?php
/**
 * @copyright 2019, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\CalDAV\ResourceBooking;

use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCA\DAV\Traits\PrincipalProxyTrait;
use OCP\Calendar\Resource\IResourceMetadata;
use OCP\Calendar\Room\IRoomMetadata;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Sabre\DAV\PropPatch;
use Sabre\DAVACL\PrincipalBackend\BackendInterface;
use function array_intersect;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;

abstract class AbstractPrincipalBackend implements BackendInterface {

	/** @var IDBConnection */
	private $db;

	/** @var IUserSession */
	private $userSession;

	/** @var IGroupManager */
	private $groupManager;

	private LoggerInterface $logger;

	/** @var ProxyMapper */
	private $proxyMapper;

	/** @var string */
	private $principalPrefix;

	/** @var string */
	private $dbTableName;

	/** @var string */
	private $dbMetaDataTableName;

	/** @var string */
	private $dbForeignKeyName;

	/** @var string */
	private $cuType;

	public function __construct(IDBConnection $dbConnection,
								IUserSession $userSession,
								IGroupManager $groupManager,
								LoggerInterface $logger,
								ProxyMapper $proxyMapper,
								string $principalPrefix,
								string $dbPrefix,
								string $cuType) {
		$this->db = $dbConnection;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->logger = $logger;
		$this->proxyMapper = $proxyMapper;
		$this->principalPrefix = $principalPrefix;
		$this->dbTableName = 'calendar_' . $dbPrefix . 's';
		$this->dbMetaDataTableName = $this->dbTableName . '_md';
		$this->dbForeignKeyName = $dbPrefix . '_id';
		$this->cuType = $cuType;
	}

	use PrincipalProxyTrait;

	/**
	 * Returns a list of principals based on a prefix.
	 *
	 * This prefix will often contain something like 'principals'. You are only
	 * expected to return principals that are in this base path.
	 *
	 * You are expected to return at least a 'uri' for every user, you can
	 * return any additional properties if you wish so. Common properties are:
	 *   {DAV:}displayname
	 *
	 * @param string $prefixPath
	 * @return string[]
	 */
	public function getPrincipalsByPrefix($prefixPath): array {
		$principals = [];

		if ($prefixPath === $this->principalPrefix) {
			$query = $this->db->getQueryBuilder();
			$query->select(['id', 'backend_id', 'resource_id', 'email', 'displayname'])
				->from($this->dbTableName);
			$stmt = $query->execute();

			$metaDataQuery = $this->db->getQueryBuilder();
			$metaDataQuery->select([$this->dbForeignKeyName, 'key', 'value'])
				->from($this->dbMetaDataTableName);
			$metaDataStmt = $metaDataQuery->execute();
			$metaDataRows = $metaDataStmt->fetchAll(\PDO::FETCH_ASSOC);

			$metaDataById = [];
			foreach ($metaDataRows as $metaDataRow) {
				if (!isset($metaDataById[$metaDataRow[$this->dbForeignKeyName]])) {
					$metaDataById[$metaDataRow[$this->dbForeignKeyName]] = [];
				}

				$metaDataById[$metaDataRow[$this->dbForeignKeyName]][$metaDataRow['key']] =
					$metaDataRow['value'];
			}

			while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
				$id = $row['id'];

				if (isset($metaDataById[$id])) {
					$principals[] = $this->rowToPrincipal($row, $metaDataById[$id]);
				} else {
					$principals[] = $this->rowToPrincipal($row);
				}
			}

			$stmt->closeCursor();
		}

		return $principals;
	}

	/**
	 * Returns a specific principal, specified by its path.
	 * The returned structure should be the exact same as from
	 * getPrincipalsByPrefix.
	 *
	 * @param string $prefixPath
	 *
	 * @return array
	 */
	public function getPrincipalByPath($path) {
		if (strpos($path, $this->principalPrefix) !== 0) {
			return null;
		}
		[, $name] = \Sabre\Uri\split($path);

		[$backendId, $resourceId] = explode('-',  $name, 2);

		$query = $this->db->getQueryBuilder();
		$query->select(['id', 'backend_id', 'resource_id', 'email', 'displayname'])
			->from($this->dbTableName)
			->where($query->expr()->eq('backend_id', $query->createNamedParameter($backendId)))
			->andWhere($query->expr()->eq('resource_id', $query->createNamedParameter($resourceId)));
		$stmt = $query->execute();
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);

		if (!$row) {
			return null;
		}

		$metaDataQuery = $this->db->getQueryBuilder();
		$metaDataQuery->select(['key', 'value'])
			->from($this->dbMetaDataTableName)
			->where($metaDataQuery->expr()->eq($this->dbForeignKeyName, $metaDataQuery->createNamedParameter($row['id'])));
		$metaDataStmt = $metaDataQuery->execute();
		$metaDataRows = $metaDataStmt->fetchAll(\PDO::FETCH_ASSOC);
		$metadata = [];

		foreach ($metaDataRows as $metaDataRow) {
			$metadata[$metaDataRow['key']] = $metaDataRow['value'];
		}

		return $this->rowToPrincipal($row, $metadata);
	}

	/**
	 * @param int $id
	 * @return string[]|null
	 */
	public function getPrincipalById($id): ?array {
		$query = $this->db->getQueryBuilder();
		$query->select(['id', 'backend_id', 'resource_id', 'email', 'displayname'])
			->from($this->dbTableName)
			->where($query->expr()->eq('id', $query->createNamedParameter($id)));
		$stmt = $query->execute();
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);

		if (!$row) {
			return null;
		}

		$metaDataQuery = $this->db->getQueryBuilder();
		$metaDataQuery->select(['key', 'value'])
			->from($this->dbMetaDataTableName)
			->where($metaDataQuery->expr()->eq($this->dbForeignKeyName, $metaDataQuery->createNamedParameter($row['id'])));
		$metaDataStmt = $metaDataQuery->execute();
		$metaDataRows = $metaDataStmt->fetchAll(\PDO::FETCH_ASSOC);
		$metadata = [];

		foreach ($metaDataRows as $metaDataRow) {
			$metadata[$metaDataRow['key']] = $metaDataRow['value'];
		}

		return $this->rowToPrincipal($row, $metadata);
	}

	/**
	 * @param string $path
	 * @param PropPatch $propPatch
	 * @return int
	 */
	public function updatePrincipal($path, PropPatch $propPatch): int {
		return 0;
	}

	/**
	 * @param string $prefixPath
	 * @param string $test
	 *
	 * @return array
	 */
	public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof') {
		$results = [];
		if (\count($searchProperties) === 0) {
			return [];
		}
		if ($prefixPath !== $this->principalPrefix) {
			return [];
		}

		$user = $this->userSession->getUser();
		if (!$user) {
			return [];
		}
		$usersGroups = $this->groupManager->getUserGroupIds($user);

		foreach ($searchProperties as $prop => $value) {
			switch ($prop) {
				case '{http://sabredav.org/ns}email-address':
					$query = $this->db->getQueryBuilder();
					$query->select(['id', 'backend_id', 'resource_id', 'email', 'displayname', 'group_restrictions'])
						->from($this->dbTableName)
						->where($query->expr()->iLike('email', $query->createNamedParameter('%' . $this->db->escapeLikeParameter($value) . '%')));

					$stmt = $query->execute();
					$principals = [];
					while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
						if (!$this->isAllowedToAccessResource($row, $usersGroups)) {
							continue;
						}
						$principals[] = $this->rowToPrincipal($row)['uri'];
					}
					$results[] = $principals;

					$stmt->closeCursor();
					break;

				case '{DAV:}displayname':
					$query = $this->db->getQueryBuilder();
					$query->select(['id', 'backend_id', 'resource_id', 'email', 'displayname', 'group_restrictions'])
						->from($this->dbTableName)
						->where($query->expr()->iLike('displayname', $query->createNamedParameter('%' . $this->db->escapeLikeParameter($value) . '%')));

					$stmt = $query->execute();
					$principals = [];
					while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
						if (!$this->isAllowedToAccessResource($row, $usersGroups)) {
							continue;
						}
						$principals[] = $this->rowToPrincipal($row)['uri'];
					}
					$results[] = $principals;

					$stmt->closeCursor();
					break;

				case '{urn:ietf:params:xml:ns:caldav}calendar-user-address-set':
					// If you add support for more search properties that qualify as a user-address,
					// please also add them to the array below
					$results[] = $this->searchPrincipals($this->principalPrefix, [
						'{http://sabredav.org/ns}email-address' => $value,
					], 'anyof');
					break;

				case IRoomMetadata::FEATURES:
					$results[] = $this->searchPrincipalsByRoomFeature($prop, $value);
					break;

				case IRoomMetadata::CAPACITY:
				case IResourceMetadata::VEHICLE_SEATING_CAPACITY:
					$results[] = $this->searchPrincipalsByCapacity($prop,$value);
					break;

				default:
					$results[] = $this->searchPrincipalsByMetadataKey($prop, $value, $usersGroups);
					break;
			}
		}

		// results is an array of arrays, so this is not the first search result
		// but the results of the first searchProperty
		if (count($results) === 1) {
			return $results[0];
		}

		switch ($test) {
			case 'anyof':
				return array_values(array_unique(array_merge(...$results)));

			case 'allof':
			default:
				return array_values(array_intersect(...$results));
		}
	}

	/**
	 * @param string $key
	 * @return IQueryBuilder
	 */
	private function getMetadataQuery(string $key): IQueryBuilder {
		$query = $this->db->getQueryBuilder();
		$query->select([$this->dbForeignKeyName])
			->from($this->dbMetaDataTableName)
			->where($query->expr()->eq('key', $query->createNamedParameter($key)));
		return $query;
	}

	/**
	 * Searches principals based on their metadata keys.
	 * This allows to search for all principals with a specific key.
	 * e.g.:
	 * '{http://nextcloud.com/ns}room-building-address' => 'ABC Street 123, ...'
	 *
	 * @param string $key
	 * @param string $value
	 * @param string[] $usersGroups
	 * @return string[]
	 */
	private function searchPrincipalsByMetadataKey(string $key, string $value, array $usersGroups = []): array {
		$query = $this->getMetadataQuery($key);
		$query->andWhere($query->expr()->iLike('value', $query->createNamedParameter('%' . $this->db->escapeLikeParameter($value) . '%')));
		return $this->getRows($query, $usersGroups);
	}

	/**
	 * Searches principals based on room features
	 * e.g.:
	 * '{http://nextcloud.com/ns}room-features' => 'TV,PROJECTOR'
	 *
	 * @param string $key
	 * @param string $value
	 * @param string[] $usersGroups
	 * @return string[]
	 */
	private function searchPrincipalsByRoomFeature(string $key, string $value, array $usersGroups = []): array {
		$query = $this->getMetadataQuery($key);
		foreach (explode(',', $value) as $v) {
			$query->andWhere($query->expr()->iLike('value', $query->createNamedParameter('%' . $this->db->escapeLikeParameter($v) . '%')));
		}
		return $this->getRows($query, $usersGroups);
	}

	/**
	 * Searches principals based on room seating capacity or vehicle capacity
	 * e.g.:
	 * '{http://nextcloud.com/ns}room-seating-capacity' => '100'
	 *
	 * @param string $key
	 * @param string $value
	 * @param string[] $usersGroups
	 * @return string[]
	 */
	private function searchPrincipalsByCapacity(string $key, string $value, array $usersGroups = []): array {
		$query = $this->getMetadataQuery($key);
		$query->andWhere($query->expr()->gte('value', $query->createNamedParameter($value)));
		return $this->getRows($query, $usersGroups);
	}

	/**
	 * @param IQueryBuilder $query
	 * @param string[] $usersGroups
	 * @return string[]
	 */
	private function getRows(IQueryBuilder $query, array $usersGroups): array {
		try {
			$stmt = $query->executeQuery();
		} catch (Exception $e) {
			$this->logger->error("Could not search resources: " . $e->getMessage(), ['exception' => $e]);
		}

		$rows = [];
		while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			$principalRow = $this->getPrincipalById($row[$this->dbForeignKeyName]);
			if (!$principalRow) {
				continue;
			}

			$rows[] = $principalRow;
		}

		$stmt->closeCursor();

		$filteredRows = array_filter($rows, function ($row) use ($usersGroups) {
			return $this->isAllowedToAccessResource($row, $usersGroups);
		});

		return array_map(static function ($row): string {
			return $row['uri'];
		}, $filteredRows);
	}

	/**
	 * @param string $uri
	 * @param string $principalPrefix
	 * @return null|string
	 * @throws Exception
	 */
	public function findByUri($uri, $principalPrefix): ?string {
		$user = $this->userSession->getUser();
		if (!$user) {
			return null;
		}
		$usersGroups = $this->groupManager->getUserGroupIds($user);

		if (strpos($uri, 'mailto:') === 0) {
			$email = substr($uri, 7);
			$query = $this->db->getQueryBuilder();
			$query->select(['id', 'backend_id', 'resource_id', 'email', 'displayname', 'group_restrictions'])
				->from($this->dbTableName)
				->where($query->expr()->eq('email', $query->createNamedParameter($email)));

			$stmt = $query->execute();
			$row = $stmt->fetch(\PDO::FETCH_ASSOC);

			if (!$row) {
				return null;
			}
			if (!$this->isAllowedToAccessResource($row, $usersGroups)) {
				return null;
			}

			return $this->rowToPrincipal($row)['uri'];
		}

		if (strpos($uri, 'principal:') === 0) {
			$path = substr($uri, 10);
			if (strpos($path, $this->principalPrefix) !== 0) {
				return null;
			}

			[, $name] = \Sabre\Uri\split($path);
			[$backendId, $resourceId] = explode('-',  $name, 2);

			$query = $this->db->getQueryBuilder();
			$query->select(['id', 'backend_id', 'resource_id', 'email', 'displayname', 'group_restrictions'])
				->from($this->dbTableName)
				->where($query->expr()->eq('backend_id', $query->createNamedParameter($backendId)))
				->andWhere($query->expr()->eq('resource_id', $query->createNamedParameter($resourceId)));
			$stmt = $query->execute();
			$row = $stmt->fetch(\PDO::FETCH_ASSOC);

			if (!$row) {
				return null;
			}
			if (!$this->isAllowedToAccessResource($row, $usersGroups)) {
				return null;
			}

			return $this->rowToPrincipal($row)['uri'];
		}

		return null;
	}

	/**
	 * convert database row to principal
	 *
	 * @param string[] $row
	 * @param string[] $metadata
	 * @return string[]
	 */
	private function rowToPrincipal(array $row, array $metadata = []): array {
		return array_merge([
			'uri' => $this->principalPrefix . '/' . $row['backend_id'] . '-' . $row['resource_id'],
			'{DAV:}displayname' => $row['displayname'],
			'{http://sabredav.org/ns}email-address' => $row['email'],
			'{urn:ietf:params:xml:ns:caldav}calendar-user-type' => $this->cuType,
		], $metadata);
	}

	/**
	 * @param array $row
	 * @param array $userGroups
	 * @return bool
	 */
	private function isAllowedToAccessResource(array $row, array $userGroups): bool {
		if (!isset($row['group_restrictions']) ||
			$row['group_restrictions'] === null ||
			$row['group_restrictions'] === '') {
			return true;
		}

		// group restrictions contains something, but not parsable, deny access and log warning
		$json = json_decode($row['group_restrictions'], null, 512, JSON_THROW_ON_ERROR);
		if (!\is_array($json)) {
			$this->logger->info('group_restrictions field could not be parsed for ' . $this->dbTableName . '::' . $row['id'] . ', denying access to resource');
			return false;
		}

		// empty array => no group restrictions
		if (empty($json)) {
			return true;
		}

		return !empty(array_intersect($json, $userGroups));
	}
}
