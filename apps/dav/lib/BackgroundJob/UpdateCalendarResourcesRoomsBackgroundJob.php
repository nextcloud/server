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

namespace OCA\DAV\BackgroundJob;

use OC\BackgroundJob\TimedJob;
use OCA\DAV\CalDAV\CalDavBackend;
use OCP\Calendar\BackendTemporarilyUnavailableException;
use OCP\Calendar\Resource\IManager as IResourceManager;
use OCP\Calendar\Resource\IResource;
use OCP\Calendar\Room\IManager as IRoomManager;
use OCP\Calendar\Room\IRoom;
use OCP\IDBConnection;

class UpdateCalendarResourcesRoomsBackgroundJob extends TimedJob {

	/** @var IResourceManager */
	private $resourceManager;

	/** @var IRoomManager */
	private $roomManager;

	/** @var IDBConnection */
	private $db;

	/** @var CalDavBackend */
	private $calDavBackend;

	/** @var string */
	private $resourceDbTable;

	/** @var string */
	private $resourcePrincipalUri;

	/** @var string */
	private $roomDbTable;

	/** @var string */
	private $roomPrincipalUri;

	/**
	 * UpdateCalendarResourcesRoomsBackgroundJob constructor.
	 *
	 * @param IResourceManager $resourceManager
	 * @param IRoomManager $roomManager
	 * @param IDBConnection $dbConnection
	 * @param CalDavBackend $calDavBackend
	 */
	public function __construct(IResourceManager $resourceManager, IRoomManager $roomManager,
								IDBConnection $dbConnection, CalDavBackend $calDavBackend) {
		$this->resourceManager = $resourceManager;
		$this->roomManager = $roomManager;
		$this->db = $dbConnection;
		$this->calDavBackend = $calDavBackend;
		$this->resourceDbTable = 'calendar_resources';
		$this->resourcePrincipalUri = 'principals/calendar-resources';
		$this->roomDbTable = 'calendar_rooms';
		$this->roomPrincipalUri = 'principals/calendar-rooms';

		// run once an hour
		$this->setInterval(60 * 60);
	}

	/**
	 * @param $argument
	 */
	public function run($argument) {
		$this->runResources();
		$this->runRooms();
	}

	/**
	 * run timed job for resources
	 */
	private function runResources() {
		$resourceBackends = $this->resourceManager->getBackends();
		$cachedResources = $this->getCached($this->resourceDbTable);
		$cachedResourceIds = $this->getCachedResourceIds($cachedResources);

		$remoteResourceIds = [];
		foreach($resourceBackends as $resourceBackend) {
			try {
				$remoteResourceIds[$resourceBackend->getBackendIdentifier()] =
					$resourceBackend->listAllResources();
			} catch(BackendTemporarilyUnavailableException $ex) {
				// If the backend is temporarily unavailable
				// ignore this backend in this execution
				unset($cachedResourceIds[$resourceBackend->getBackendIdentifier()]);
			}
		}

		$sortedResources = $this->sortByNewDeletedExisting($cachedResourceIds, $remoteResourceIds);

		foreach($sortedResources['new'] as $backendId => $newResources) {
			foreach ($newResources as $newResource) {
				$backend = $this->resourceManager->getBackend($backendId);
				if ($backend === null) {
					continue;
				}

				$resource = $backend->getResource($newResource);
				$this->addToCache($this->resourceDbTable, $resource);
			}
		}
		foreach($sortedResources['deleted'] as $backendId => $deletedResources) {
			foreach ($deletedResources as $deletedResource) {
				$this->deleteFromCache($this->resourceDbTable,
					$this->resourcePrincipalUri, $backendId, $deletedResource);
			}
		}
		foreach($sortedResources['edited'] as $backendId => $editedResources) {
			foreach ($editedResources as $editedResource) {
				$backend = $this->resourceManager->getBackend($backendId);
				if ($backend === null) {
					continue;
				}

				$resource = $backend->getResource($editedResource);
				$this->updateCache($this->resourceDbTable, $resource);
			}
		}
	}

	/**
	 * run timed job for rooms
	 */
	private function runRooms() {
		$roomBackends = $this->roomManager->getBackends();
		$cachedRooms = $this->getCached($this->roomDbTable);
		$cachedRoomIds = $this->getCachedRoomIds($cachedRooms);

		$remoteRoomIds = [];
		foreach($roomBackends as $roomBackend) {
			try {
				$remoteRoomIds[$roomBackend->getBackendIdentifier()] =
					$roomBackend->listAllRooms();
			} catch(BackendTemporarilyUnavailableException $ex) {
				// If the backend is temporarily unavailable
				// ignore this backend in this execution
				unset($cachedRoomIds[$roomBackend->getBackendIdentifier()]);
			}
		}

		$sortedRooms = $this->sortByNewDeletedExisting($cachedRoomIds, $remoteRoomIds);

		foreach($sortedRooms['new'] as $backendId => $newRooms) {
			foreach ($newRooms as $newRoom) {
				$backend = $this->roomManager->getBackend($backendId);
				if ($backend === null) {
					continue;
				}

				$resource = $backend->getRoom($newRoom);
				$this->addToCache($this->roomDbTable, $resource);
			}
		}
		foreach($sortedRooms['deleted'] as $backendId => $deletedRooms) {
			foreach ($deletedRooms as $deletedRoom) {
				$this->deleteFromCache($this->roomDbTable,
					$this->roomPrincipalUri, $backendId, $deletedRoom);
			}
		}
		foreach($sortedRooms['edited'] as $backendId => $editedRooms) {
			foreach ($editedRooms as $editedRoom) {
				$backend = $this->roomManager->getBackend($backendId);
				if ($backend === null) {
					continue;
				}

				$resource = $backend->getRoom($editedRoom);
				$this->updateCache($this->roomDbTable, $resource);
			}
		}
	}

	/**
	 * get cached db rows for resources / rooms
	 * @param string $tableName
	 * @return array
	 */
	private function getCached($tableName):array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')->from($tableName);

		$rows = [];
		$stmt = $query->execute();
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * @param array $cachedResources
	 * @return array
	 */
	private function getCachedResourceIds(array $cachedResources):array {
		$cachedResourceIds = [];
		foreach ($cachedResources as $cachedResource) {
			if (!isset($cachedResourceIds[$cachedResource['backend_id']])) {
				$cachedResourceIds[$cachedResource['backend_id']] = [];
			}

			$cachedResourceIds[$cachedResource['backend_id']][] =
				$cachedResource['resource_id'];
		}

		return $cachedResourceIds;
	}

	/**
	 * @param array $cachedRooms
	 * @return array
	 */
	private function getCachedRoomIds(array $cachedRooms):array {
		$cachedRoomIds = [];
		foreach ($cachedRooms as $cachedRoom) {
			if (!isset($cachedRoomIds[$cachedRoom['backend_id']])) {
				$cachedRoomIds[$cachedRoom['backend_id']] = [];
			}

			$cachedRoomIds[$cachedRoom['backend_id']][] =
				$cachedRoom['resource_id'];
		}

		return $cachedRoomIds;
	}

	/**
	 * sort list of ids by whether they appear only in the backend /
	 * only in the cache / in both
	 *
	 * @param array $cached
	 * @param array $remote
	 * @return array
	 */
	private function sortByNewDeletedExisting(array $cached, array $remote):array {
		$sorted = [
			'new' => [],
			'deleted' => [],
			'edited' => [],
		];

		$backendIds = array_merge(array_keys($cached), array_keys($remote));
		foreach($backendIds as $backendId) {
			if (!isset($cached[$backendId])) {
				$sorted['new'][$backendId] = $remote[$backendId];
			} elseif (!isset($remote[$backendId])) {
				$sorted['deleted'][$backendId] = $cached[$backendId];
			} else {
				$sorted['new'][$backendId] = array_diff($remote[$backendId], $cached[$backendId]);
				$sorted['deleted'][$backendId] = array_diff($cached[$backendId], $remote[$backendId]);
				$sorted['edited'][$backendId] = array_intersect($remote[$backendId], $cached[$backendId]);
			}
		}

		return $sorted;
	}

	/**
	 * add entry to cache that exists remotely but not yet in cache
	 *
	 * @param string $table
	 * @param IResource|IRoom $remote
	 */
	private function addToCache($table, $remote) {
		$query = $this->db->getQueryBuilder();
		$query->insert($table)
			->values([
				'backend_id' => $query->createNamedParameter($remote->getBackend()->getBackendIdentifier()),
				'resource_id' => $query->createNamedParameter($remote->getId()),
				'email' => $query->createNamedParameter($remote->getEMail()),
				'displayname' => $query->createNamedParameter($remote->getDisplayName()),
				'group_restrictions' => $query->createNamedParameter(
					$this->serializeGroupRestrictions(
						$remote->getGroupRestrictions()
					))
			])
			->execute();
	}

	/**
	 * delete entry from cache that does not exist anymore remotely
	 *
	 * @param string $table
	 * @param string $principalUri
	 * @param string $backendId
	 * @param string $resourceId
	 */
	private function deleteFromCache($table, $principalUri, $backendId, $resourceId) {
		$query = $this->db->getQueryBuilder();
		$query->delete($table)
			->where($query->expr()->eq('backend_id', $query->createNamedParameter($backendId)))
			->andWhere($query->expr()->eq('resource_id', $query->createNamedParameter($resourceId)))
			->execute();

		$calendar = $this->calDavBackend->getCalendarByUri($principalUri, implode('-', [$backendId, $resourceId]));
		if ($calendar !== null) {
			$this->calDavBackend->deleteCalendar($calendar['id']);
		}
	}

	/**
	 * update an existing entry in cache
	 *
	 * @param string $table
	 * @param IResource|IRoom $remote
	 */
	private function updateCache($table, $remote) {
		$query = $this->db->getQueryBuilder();
		$query->update($table)
			->set('email', $query->createNamedParameter($remote->getEMail()))
			->set('displayname', $query->createNamedParameter($remote->getDisplayName()))
			->set('group_restrictions', $query->createNamedParameter(
				$this->serializeGroupRestrictions(
					$remote->getGroupRestrictions()
				)))
			->where($query->expr()->eq('backend_id', $query->createNamedParameter($remote->getBackend()->getBackendIdentifier())))
			->andWhere($query->expr()->eq('resource_id', $query->createNamedParameter($remote->getId())))
			->execute();
	}

	/**
	 * serialize array of group restrictions to store them in database
	 *
	 * @param array $groups
	 * @return string
	 */
	private function serializeGroupRestrictions(array $groups):string {
		return \json_encode($groups);
	}
}
