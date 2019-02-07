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


use OCP\Collaboration\Resources\CollectionException;
use OCP\Collaboration\Resources\ICollection;
use OCP\Collaboration\Resources\IManager;
use OCP\Collaboration\Resources\IProvider;
use OCP\Collaboration\Resources\IResource;
use OCP\Collaboration\Resources\ResourceException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;

class Manager implements IManager {

	/** @var IDBConnection */
	protected $connection;

	/** @var IProvider[] */
	protected $providers = [];

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param int $id
	 * @return ICollection
	 * @throws CollectionException when the collection could not be found
	 * @since 16.0.0
	 */
	public function getCollection(int $id): ICollection {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('collres_collections')
			->where($query->expr()->eq('id', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		$result = $query->execute();
		$row = $result->fetch();
		$result->closeCursor();

		if (!$row) {
			throw new CollectionException('Collection not found');
		}

		return new Collection($this, $this->connection, (int) $row['id'], (string) $row['name']);
	}

	/**
	 * @param IUser $user
	 * @param string $filter
	 * @param int $limit
	 * @param int $start
	 * @return ICollection[]
	 * @since 16.0.0
	 */
	public function searchCollections(IUser $user, string $filter, int $limit = 50, int $start = 0): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('collres_collections')
			->where($query->expr()->iLike('name', $query->createNamedParameter($filter, IQueryBuilder::PARAM_STR)))
			->setMaxResults($limit)
			->setFirstResult($start);
		$result = $query->execute();
		$collections = [];
		/** TODO: this is a huge performance bottleneck */
		while ($row = $result->fetch()) {
			$collection = new Collection($this, $this->connection, (int)$row['id'], (string)$row['name']);
			if ($collection->canAccess($user)) {
				$collections[] = $collection;
			}
		}
		$result->closeCursor();

		// TODO: call with increased first result if no matches found

		return $collections;
	}

	/**
	 * @param string $name
	 * @return ICollection
	 * @since 16.0.0
	 */
	public function newCollection(string $name): ICollection {
		$query = $this->connection->getQueryBuilder();
		$query->insert('collres_collections')
			->values([
				'name' => $query->createNamedParameter($name),
			]);
		$query->execute();

		return new Collection($this, $this->connection, $query->getLastInsertId(), $name);
	}

	/**
	 * @param string $type
	 * @param string $id
	 * @return IResource
	 * @since 16.0.0
	 */
	public function getResource(string $type, string $id): IResource {
		return new Resource($this, $this->connection, $type, $id);
	}

	/**
	 * @return IProvider[]
	 * @since 16.0.0
	 */
	public function getProviders(): array {
		return $this->providers;
	}

	/**
	 * Get the display name of a resource
	 *
	 * @param IResource $resource
	 * @return string
	 * @since 16.0.0
	 */
	public function getName(IResource $resource): string {
		foreach ($this->getProviders() as $provider) {
			if ($provider->getType() === $resource->getType()) {
				try {
					return $provider->getName($resource);
				} catch (ResourceException $e) {
				}
			}
		}

		return '';
	}

	/**
	 *
	 * @param IResource $resource
	 * @return string
	 */
	public function getIconClass(IResource $resource): string {
		foreach ($this->getProviders() as $provider) {
			if ($provider->getType() === $resource->getType()) {
				try {
					return $provider->getIconClass($resource);
				} catch (ResourceException $e) {
				}
			}
		}

		return '';
	}

	/**
	 * Can a user/guest access the collection
	 *
	 * @param IResource $resource
	 * @param IUser $user
	 * @return bool
	 * @since 16.0.0
	 */
	public function canAccess(IResource $resource, IUser $user = null): bool {
		foreach ($this->getProviders() as $provider) {
			if ($provider->getType() === $resource->getType()) {
				try {
					if ($provider->canAccess($resource, $user)) {
						return true;
					}
				} catch (ResourceException $e) {
				}
			}
		}

		return false;
	}

	public function cacheAccessForResource(IResource $resource, ?IUser $user, bool $access): void {
		$query = $this->connection->getQueryBuilder();
		$userId = $user instanceof IUser ? $user->getUID() : '';

		$query->insert('collres_accesscache')
			->values([
				'user_id' => $query->createNamedParameter($userId),
				'resource_id' => $query->createNamedParameter($resource->getId()),
				'access' => $query->createNamedParameter($access),
			]);
		$query->execute();
	}

	public function cacheAccessForCollection(ICollection $collection, ?IUser $user, bool $access): void {
		$query = $this->connection->getQueryBuilder();
		$userId = $user instanceof IUser ? $user->getUID() : '';

		$query->insert('collres_accesscache')
			->values([
				'user_id' => $query->createNamedParameter($userId),
				'collection_id' => $query->createNamedParameter($collection->getId()),
				'access' => $query->createNamedParameter($access),
			]);
		$query->execute();
	}

	public function invalidateAccessCacheForUser(?IUser $user): void {
		$query = $this->connection->getQueryBuilder();
		$userId = $user instanceof IUser ? $user->getUID() : '';

		$query->delete('collres_accesscache')
			->where($query->expr()->eq('user_id', $query->createNamedParameter($userId)));
		$query->execute();
	}

	public function invalidateAccessCacheForResource(IResource $resource): void {
		$query = $this->connection->getQueryBuilder();

		$query->delete('collres_accesscache')
			->where($query->expr()->eq('resource_id', $query->createNamedParameter($resource->getId())));
		$query->execute();

		foreach ($resource->getCollections() as $collection) {
			$this->invalidateAccessCacheForCollection($collection);
		}
	}

	protected function invalidateAccessCacheForCollection(ICollection $collection): void {
		$query = $this->connection->getQueryBuilder();

		$query->delete('collres_accesscache')
			->where($query->expr()->eq('collection_id', $query->createNamedParameter($collection->getId())));
		$query->execute();
	}

	public function invalidateAccessCacheForResourceByUser(IResource $resource, ?IUser $user): void {
		$query = $this->connection->getQueryBuilder();
		$userId = $user instanceof IUser ? $user->getUID() : '';

		$query->delete('collres_accesscache')
			->where($query->expr()->eq('resource_id', $query->createNamedParameter($resource->getId())))
			->andWhere($query->expr()->eq('user_id', $query->createNamedParameter($userId)));
		$query->execute();

		foreach ($resource->getCollections() as $collection) {
			$this->invalidateAccessCacheForCollectionByUser($collection, $user);
		}
	}

	protected function invalidateAccessCacheForCollectionByUser(ICollection $collection, ?IUser $user): void {
		$query = $this->connection->getQueryBuilder();
		$userId = $user instanceof IUser ? $user->getUID() : '';

		$query->delete('collres_accesscache')
			->where($query->expr()->eq('collection_id', $query->createNamedParameter($collection->getId())))
			->andWhere($query->expr()->eq('user_id', $query->createNamedParameter($userId)));
		$query->execute();
	}

	/**
	 * @param IProvider $provider
	 */
	public function registerResourceProvider(IProvider $provider): void {
		$this->providers[] = $provider;
	}

	/**
	 * Get the type of a resource
	 *
	 * @param IResource $resource
	 * @return string
	 * @since 16.0.0
	 */
	public function getType(): string {
		return '';
	}

	/**
	 * Get the link to a resource
	 *
	 * @param IResource $resource
	 * @return string
	 * @since 16.0.0
	 */
	public function getLink(IResource $resource): string {
		foreach ($this->getProviders() as $provider) {
			if ($provider->getType() === $resource->getType()) {
				try {
					return $provider->getLink($resource);
				} catch (ResourceException $e) {
				}
			}
		}

		return '';
	}

	/**
	 * @param string $name
	 * @return ICollection
	 * @since 16.0.0
	 */
	public function renameCollection(int $id, string $name): ICollection {
		$query = $this->connection->getQueryBuilder();
		$query->update('collres_collections')
			->set('name', $query->createNamedParameter($name))
			->where($query->expr()->eq('id', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		$query->execute();

		return new Collection($this, $this->connection, $id, $name);
	}
}
