<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 Joas Schilling <coding@schilljs.com>
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

namespace OC\Collaboration\Resources;


use Doctrine\DBAL\Exception\ConstraintViolationException;
use OCP\Collaboration\Resources\IManager;
use OCP\Collaboration\Resources\ResourceException;
use OCP\Collaboration\Resources\ICollection;
use OCP\Collaboration\Resources\IResource;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;

class Collection implements ICollection {

	/** @var IManager */
	protected $manager;

	/** @var IDBConnection */
	protected $connection;

	/** @var int */
	protected $id;

	/** @var IResource[] */
	protected $resources;

	public function __construct(IManager $manager, IDBConnection $connection, int $id) {
		$this->manager = $manager;
		$this->connection = $connection;
		$this->id = $id;
		$this->resources = [];
	}

	/**
	 * @return IResource[]
	 * @since 15.0.0
	 */
	public function getResources(): array {
		if (empty($this->resources)) {
			$query = $this->connection->getQueryBuilder();
			$query->select('resource_type', 'resource_id')
				->from('collres_resources')
				->where($query->expr()->eq('collection_id', $query->createNamedParameter($this->id, IQueryBuilder::PARAM_INT)));

			$result = $query->execute();
			while ($row = $result->fetch()) {
				$this->resources[] = $this->manager->getResource($row['resource_type'], $row['resource_id']);
			}
			$result->closeCursor();
		}

		return $this->resources;
	}

	/**
	 * Adds a resource to a collection
	 *
	 * @param IResource $resource
	 * @throws ResourceException when the resource is already part of the collection
	 * @since 15.0.0
	 */
	public function addResource(IResource $resource) {
		array_map(function(IResource $r) use ($resource) {
			if ($this->isSameResource($r, $resource)) {
				throw new ResourceException('Already part of the collection');
			}
		}, $this->resources);

		$this->resources[] = $resource;

		if ($this->id === 0) {
			$this->makeCollectionPersistent();
		}

		$query = $this->connection->getQueryBuilder();
		$query->insert('collres_resources')
			->values([
				'collection_id' => $query->createNamedParameter($this->id, IQueryBuilder::PARAM_INT),
				'resource_type' => $query->createNamedParameter($resource->getType()),
				'resource_id' => $query->createNamedParameter($resource->getId()),
			]);

		try {
			$query->execute();
		} catch (ConstraintViolationException $e) {
			throw new ResourceException('Already part of the collection');
		}
	}

	/**
	 * Removes a resource from a collection
	 *
	 * @param IResource $resource
	 * @since 15.0.0
	 */
	public function removeResource(IResource $resource) {
		$this->resources = array_filter($this->resources, function(IResource $r) use ($resource) {
			return !$this->isSameResource($r, $resource);
		});

		$query = $this->connection->getQueryBuilder();
		$query->delete('collres_resources')
			->where($query->expr()->eq('collection_id', $query->createNamedParameter($this->id, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->eq('resource_type', $query->createNamedParameter($resource->getType())))
			->andWhere($query->expr()->eq('resource_id', $query->createNamedParameter($resource->getId())));
		$query->execute();

		if (empty($this->resources)) {
			$this->makeCollectionUnsteady();
		}
	}

	/**
	 * Can a user/guest access the collection
	 *
	 * @param IUser $user
	 * @return bool
	 * @since 15.0.0
	 */
	public function canAccess(IUser $user = null): bool {
		foreach ($this->getResources() as $resource) {
			if ($resource->canAccess($user)) {
				return true;
			}
		}

		return false;
	}

	protected function isSameResource(IResource $resource1, IResource $resource2): bool {
		return $resource1->getType() === $resource2->getType() &&
			$resource1->getId() === $resource2->getId();
	}

	protected function makeCollectionPersistent() {
		$query = $this->connection->getQueryBuilder();
		$query->insert('collres_collections');
		$query->execute();

		$this->id = $query->getLastInsertId();
	}

	protected function makeCollectionUnsteady() {
		$query = $this->connection->getQueryBuilder();
		$query->delete('collres_collections')
			->where($query->expr()->eq('id', $query->createNamedParameter($this->id, IQueryBuilder::PARAM_INT)));
		$query->execute();

		$this->id = 0;
	}
}
