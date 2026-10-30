<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Interaction\Resources;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Constants;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMovableMount;
use OCP\Files\Node;
use OCP\Interaction\InteractionResource;
use OCP\Server;
use RuntimeException;

/**
 * A resource representing files and folders.
 *
 * @since 34.0.2
 */
#[Consumable(since: '34.0.2')]
final class NodeResource implements InteractionResource {
	/**
	 * @since 34.0.2
	 */
	public function __construct(
		public readonly int $nodeId,
		private readonly string $userId,
		private ?Node $node = null,
		/** @var ?int-mask-of<Constants::PERMISSION_*> $nodePermissions */
		private ?int $nodePermissions = null,
	) {
	}

	/**
	 * If you need to check the node permissions, use {@see getNodePermissions} instead.
	 *
	 * @since 34.0.2
	 */
	public function getNode(): Node {
		if ($this->node instanceof Node) {
			return $this->node;
		}

		$node = Server::get(IRootFolder::class)->getUserFolder($this->userId)->getFirstNodeById($this->nodeId);
		if ($node === null) {
			throw new RuntimeException('Node does not exist: ' . $this->nodeId);
		}

		return $this->node = $node;
	}

	/**
	 * Returns the merged permissions of all node instances the user has access to.
	 *
	 * @return int-mask-of<Constants::PERMISSION_*>
	 * @since 34.0.2
	 */
	public function getNodePermissions(): int {
		if ($this->nodePermissions !== null) {
			return $this->nodePermissions;
		}

		$nodes = Server::get(IRootFolder::class)->getUserFolder($this->userId)->getById($this->nodeId);
		if ($nodes === []) {
			throw new RuntimeException('Node does not exist: ' . $this->nodeId);
		}

		/** @var int-mask-of<Constants::PERMISSION_*> $nodePermissions */
		$nodePermissions = array_reduce(
			$nodes,
			static fn (int $nodePermissions, Node $node): int => $nodePermissions | ($node->getInternalPath() === '' && !$node->getMountPoint() instanceof IMovableMount ? $node->getStorage()->getPermissions('') : $node->getPermissions()),
			0,
		);

		return $this->nodePermissions = $nodePermissions;
	}

	/**
	 * @since 34.0.2
	 */
	#[\Override]
	public function getID(): string {
		return (string)$this->nodeId;
	}
}
