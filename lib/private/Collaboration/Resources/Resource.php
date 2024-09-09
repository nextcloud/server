<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Collaboration\Resources;

use OCP\Collaboration\Resources\IManager;
use OCP\Collaboration\Resources\IResource;
use OCP\IDBConnection;
use OCP\IUser;

class Resource implements IResource {
	protected ?array $data = null;

	public function __construct(
		protected IManager $manager,
		protected IDBConnection $connection,
		protected string $type,
		protected string $id,
		protected ?IUser $userForAccess = null,
		protected ?bool $access = null
	) {
	}

	public function getType(): string {
		return $this->type;
	}

	public function getId(): string {
		return $this->id;
	}

	public function getRichObject(): array {
		if ($this->data === null) {
			$this->data = $this->manager->getResourceRichObject($this);
		}

		return $this->data;
	}

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

		$access = $this->manager->canAccessResource($this, $user);
		if ($this->userForAccess instanceof IUser && $user->getUID() === $this->userForAccess->getUID()) {
			$this->access = $access;
		}
		return $access;
	}

	protected function canGuestAccess(): bool {
		if (\is_bool($this->access) && !$this->userForAccess instanceof IUser) {
			return $this->access;
		}

		$access = $this->manager->canAccessResource($this, null);
		if (!$this->userForAccess instanceof IUser) {
			$this->access = $access;
		}
		return $access;
	}

	public function getCollections(): array {
		$collections = [];

		$query = $this->connection->getQueryBuilder();

		$query->select('collection_id')
			->from('collres_resources')
			->where($query->expr()->eq('resource_type', $query->createNamedParameter($this->getType())))
			->andWhere($query->expr()->eq('resource_id', $query->createNamedParameter($this->getId())));

		$result = $query->execute();
		while ($row = $result->fetch()) {
			$collections[] = $this->manager->getCollection((int)$row['collection_id']);
		}
		$result->closeCursor();

		return $collections;
	}
}
