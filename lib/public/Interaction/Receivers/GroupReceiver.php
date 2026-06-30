<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Interaction\Receivers;

use OCP\AppFramework\Attribute\Consumable;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\Interaction\InteractionReceiver;
use OCP\Server;
use RuntimeException;

/**
 * @since 34.0.2
 */
#[Consumable(since: '34.0.2')]
final class GroupReceiver implements InteractionReceiver {
	/**
	 * @since 34.0.2
	 */
	public function __construct(
		public readonly string $groupId,
		private ?IGroup $group = null,
	) {
	}

	/**
	 * @since 34.0.2
	 */
	public function getGroup(): IGroup {
		if ($this->group instanceof IGroup) {
			return $this->group;
		}

		$group = Server::get(IGroupManager::class)->get($this->groupId);
		if ($group === null) {
			throw new RuntimeException('Group does not exist: ' . $this->groupId);
		}

		return $this->group = $group;
	}

	/**
	 * @since 34.0.2
	 */
	#[\Override]
	public function getID(): string {
		return $this->groupId;
	}
}
