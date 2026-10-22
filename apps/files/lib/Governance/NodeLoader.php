<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Governance;

use NCU\Governance\Actor\Actor;
use NCU\Governance\Actor\PrincipalType;
use NCU\Governance\Resource\Exception\ResourceUnavailableException;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotPermittedException;
use OCP\Files\StorageNotAvailableException;
use OCP\User\Exceptions\UserNotFoundException;

/**
 * Loads the nodes for a batch of IDs once, as seen by the actor's principal.
 *
 * A system principal sees every existing node from its owner's point of view.
 */
final class NodeLoader {
	/** @var array<string, ?Node>|null */
	private ?array $nodes = null;

	/** @var array<string, \Throwable> */
	private array $failures = [];

	/**
	 * @param non-empty-list<string> $ids
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly IUserMountCache $userMountCache,
		private readonly Actor $actor,
		private readonly array $ids,
	) {
	}

	/**
	 * @throws ResourceUnavailableException
	 */
	public function get(string $id): ?Node {
		$this->load();

		if (isset($this->failures[$id])) {
			throw new ResourceUnavailableException('Node ' . $id . ' is not available', previous: $this->failures[$id]);
		}

		return $this->nodes[$id] ?? null;
	}

	private function load(): void {
		if ($this->nodes !== null) {
			return;
		}

		$this->nodes = [];
		$principal = $this->actor->principal;
		$userFolder = $principal->is(PrincipalType::User) ? $this->getUserFolder($principal->id) : null;

		foreach ($this->ids as $id) {
			try {
				$this->nodes[$id] = match ($principal->type) {
					PrincipalType::System => $this->getOwnerNode((int)$id),
					PrincipalType::User => $userFolder?->getFirstNodeById((int)$id),
					default => null,
				};
			} catch (StorageNotAvailableException $e) {
				$this->failures[$id] = $e;
			} catch (NotPermittedException) {
				$this->nodes[$id] = null;
			}
		}
	}

	/**
	 * The node as seen by its owner, or by the first user who has it mounted
	 * when the storage has no owner.
	 */
	private function getOwnerNode(int $id): ?Node {
		foreach ($this->userMountCache->getMountsForFileId($id) as $mount) {
			$mountUserId = $mount->getUser()->getUID();
			$node = $this->getUserFolder($mountUserId)?->getFirstNodeById($id);
			if ($node === null) {
				continue;
			}

			$owner = $node->getOwner();
			if ($owner === null || $owner->getUID() === $mountUserId) {
				return $node;
			}

			return $this->getUserFolder($owner->getUID())?->getFirstNodeById($id) ?? $node;
		}

		return null;
	}

	private function getUserFolder(string $userId): ?Folder {
		try {
			return $this->rootFolder->getUserFolder($userId);
		} catch (UserNotFoundException|NotPermittedException) {
			return null;
		}
	}
}
