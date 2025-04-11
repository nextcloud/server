<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Collaboration\Resources;

use Doctrine\DBAL\Exception\ConstraintViolationException;
use OCP\Collaboration\Resources\ICollection;
use OCP\Collaboration\Resources\IManager;
use OCP\Collaboration\Resources\IResource;
use OCP\Collaboration\Resources\ResourceException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;

class Collection implements ICollection {
	/** @var IResource[] */
	protected array $resources = [];

	public function __construct(
		/** @var Manager $manager */
		protected IManager $manager,
		protected IDBConnection $connection,
		protected int $id,
		protected string $name,
		protected ?IUser $userForAccess = null,
		protected ?bool $access = null,
	) {
	}

	/**
	 * @since 16.0.0
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * @since 16.0.0
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @since 16.0.0
	 */
	public function setName(string $name): void {
		$query = $this->connection->getQueryBuilder();
		$query->update(Manager::TABLE_COLLECTIONS)
			->set('name', $query->createNamedParameter($name))
			->where($query->expr()->eq('id', $query->createNamedParameter($this->getId(), IQueryBuilder::PARAM_INT)));
		$query->executeStatement();

		$this->name = $name;
	}

	/**
	 * @return IResource[]
	 * @since 16.0.0
	 */
	public function getResources(): array {
		if (empty($this->resources)) {
			$this->resources = $this->manager->getResourcesByCollectionForUser($this, $this->userForAccess);
		}

		return $this->resources;
	}

	/**
	 * Adds a resource to a collection
	 *
	 * @throws ResourceException when the resource is already part of the collection
	 * @since 16.0.0
	 */
	public function addResource(IResource $resource): void {
		array_map(function (IResource $r) use ($resource) {
			if ($this->isSameResource($r, $resource)) {
				throw new ResourceException('Already part of the collection');
			}
		}, $this->getResources());

		$this->resources[] = $resource;

		$query = $this->connection->getQueryBuilder();
		$query->insert(Manager::TABLE_RESOURCES)
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

		$this->manager->invalidateAccessCacheForCollection($this);
	}

	/**
	 * Removes a resource from a collection
	 *
	 * @since 16.0.0
	 */
	public function removeResource(IResource $resource): void {
		$this->resources = array_filter($this->getResources(), function (IResource $r) use ($resource) {
			return !$this->isSameResource($r, $resource);
		});

		$query = $this->connection->getQueryBuilder();
		$query->delete(Manager::TABLE_RESOURCES)
			->where($query->expr()->eq('collection_id', $query->createNamedParameter($this->id, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->eq('resource_type', $query->createNamedParameter($resource->getType())))
			->andWhere($query->expr()->eq('resource_id', $query->createNamedParameter($resource->getId())));
		$query->executeStatement();

		if (empty($this->resources)) {
			$this->removeCollection();
		} else {
			$this->manager->invalidateAccessCacheForCollection($this);
		}
	}

	/**
	 * Can a user/guest access the collection
	 *
	 * @since 16.0.0
	 */
	public function canAccess(?IUser $user): bool {
		if ($user instanceof IUser) {
			return $this->canUserAccess($user);
		}
		return $this->canGuestAccess();
	}

	protected function canUserAccess(IUser $user): bool {
		if (\is_bool($this->access) && $this->userForAccess instanceof IUser && $user->getUID() === $this->userForAccess->getUID()) {
			return $this->access;
		}

		$access = $this->manager->canAccessCollection($this, $user);
		if ($this->userForAccess instanceof IUser && $user->getUID() === $this->userForAccess->getUID()) {
			$this->access = $access;
		}
		return $access;
	}

	protected function canGuestAccess(): bool {
		if (\is_bool($this->access) && !$this->userForAccess instanceof IUser) {
			return $this->access;
		}

		$access = $this->manager->canAccessCollection($this, null);
		if (!$this->userForAccess instanceof IUser) {
			$this->access = $access;
		}
		return $access;
	}

	protected function isSameResource(IResource $resource1, IResource $resource2): bool {
		return $resource1->getType() === $resource2->getType() &&
			$resource1->getId() === $resource2->getId();
	}

	protected function removeCollection(): void {
		$query = $this->connection->getQueryBuilder();
		$query->delete(Manager::TABLE_COLLECTIONS)
			->where($query->expr()->eq('id', $query->createNamedParameter($this->id, IQueryBuilder::PARAM_INT)));
		$query->executeStatement();

		$this->manager->invalidateAccessCacheForCollection($this);
		$this->id = 0;
	}
}
