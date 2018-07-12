<?php
/**
 * @copyright 2018, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\CalDAV\ResourceBooking;

use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUserSession;
use Sabre\DAVACL\PrincipalBackend\BackendInterface;
use Sabre\DAV\Exception;
use \Sabre\DAV\PropPatch;

abstract class AbstractPrincipalBackend implements BackendInterface {

	/** @var IDBConnection */
	private $db;

	/** @var IUserSession */
	private $userSession;

	/** @var IGroupManager */
	private $groupManager;

	/** @var ILogger */
	private $logger;

	/** @var string */
	private $principalPrefix;

	/** @var string */
	private $dbTableName;

	/**
	 * @param IDBConnection $dbConnection
	 * @param IUserSession $userSession
	 * @param IGroupManager $groupManager
	 * @param ILogger $logger
	 * @param string $principalPrefix
	 * @param string $dbPrefix
	 */
	public function __construct(IDBConnection $dbConnection,
								IUserSession $userSession,
								IGroupManager $groupManager,
								ILogger $logger,
								$principalPrefix, $dbPrefix) {
		$this->db = $dbConnection;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->logger = $logger;
		$this->principalPrefix = $principalPrefix;
		$this->dbTableName = 'calendar_' . $dbPrefix;
	}

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
	public function getPrincipalsByPrefix($prefixPath) {
		$principals = [];

		if ($prefixPath === $this->principalPrefix) {
			$query = $this->db->getQueryBuilder();
			$query->select(['id', 'backend_id', 'resource_id', 'email', 'displayname'])
				->from($this->dbTableName);
			$stmt = $query->execute();

			while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
				$principals[] = $this->rowToPrincipal($row);
			}

			$stmt->closeCursor();
		}

		return $principals;
	}

	/**
	 * Returns a specific principal, specified by it's path.
	 * The returned structure should be the exact same as from
	 * getPrincipalsByPrefix.
	 *
	 * @param string $path
	 * @return array
	 */
	public function getPrincipalByPath($path) {
		if (strpos($path, $this->principalPrefix) !== 0) {
			return null;
		}
		list(, $name) = \Sabre\Uri\split($path);

		list($backendId, $resourceId) = explode('-',  $name, 2);

		$query = $this->db->getQueryBuilder();
		$query->select(['id', 'backend_id', 'resource_id', 'email', 'displayname'])
			->from($this->dbTableName)
			->where($query->expr()->eq('backend_id', $query->createNamedParameter($backendId)))
			->andWhere($query->expr()->eq('resource_id', $query->createNamedParameter($resourceId)));
		$stmt = $query->execute();
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);

		if(!$row) {
			return null;
		}

		return $this->rowToPrincipal($row);
	}

	/**
	 * Returns the list of members for a group-principal
	 *
	 * @param string $principal
	 * @return string[]
	 */
	public function getGroupMemberSet($principal) {
		return [];
	}

	/**
	 * Returns the list of groups a principal is a member of
	 *
	 * @param string $principal
	 * @return array
	 */
	public function getGroupMembership($principal) {
		return [];
	}

	/**
	 * Updates the list of group members for a group principal.
	 *
	 * The principals should be passed as a list of uri's.
	 *
	 * @param string $principal
	 * @param string[] $members
	 * @throws Exception
	 */
	public function setGroupMemberSet($principal, array $members) {
		throw new Exception('Setting members of the group is not supported yet');
	}

	/**
	 * @param string $path
	 * @param PropPatch $propPatch
	 * @return int
	 */
	function updatePrincipal($path, PropPatch $propPatch) {
		return 0;
	}

	/**
	 * @param string $prefixPath
	 * @param array $searchProperties
	 * @param string $test
	 * @return array
	 */
	function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof') {
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
					while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
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
					while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
						if (!$this->isAllowedToAccessResource($row, $usersGroups)) {
							continue;
						}
						$principals[] = $this->rowToPrincipal($row)['uri'];
					}
					$results[] = $principals;

					$stmt->closeCursor();
					break;

				default:
					$results[] = [];
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
	 * @param string $uri
	 * @param string $principalPrefix
	 * @return null|string
	 */
	function findByUri($uri, $principalPrefix) {
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

			if(!$row) {
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

			list(, $name) = \Sabre\Uri\split($path);
			list($backendId, $resourceId) = explode('-',  $name, 2);

			$query = $this->db->getQueryBuilder();
			$query->select(['id', 'backend_id', 'resource_id', 'email', 'displayname', 'group_restrictions'])
				->from($this->dbTableName)
				->where($query->expr()->eq('backend_id', $query->createNamedParameter($backendId)))
				->andWhere($query->expr()->eq('resource_id', $query->createNamedParameter($resourceId)));
			$stmt = $query->execute();
			$row = $stmt->fetch(\PDO::FETCH_ASSOC);

			if(!$row) {
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
	 */
	private function rowToPrincipal($row) {
		return [
			'uri' => $this->principalPrefix . '/' . $row['backend_id'] . '-' . $row['resource_id'],
			'{DAV:}displayname' => $row['displayname'],
			'{http://sabredav.org/ns}email-address' => $row['email']
		];
	}

	/**
	 * @param $row
	 * @param $userGroups
	 * @return bool
	 */
	private function isAllowedToAccessResource($row, $userGroups) {
		if (!isset($row['group_restrictions']) ||
			$row['group_restrictions'] === null ||
			$row['group_restrictions'] === '') {
			return true;
		}

		// group restrictions contains something, but not parsable, deny access and log warning
		$json = json_decode($row['group_restrictions']);
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
