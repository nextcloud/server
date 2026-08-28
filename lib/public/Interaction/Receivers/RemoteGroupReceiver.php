<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Interaction\Receivers;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\Interaction\InteractionReceiver;
use OCP\Server;

/**
 * @since 34.0.2
 */
#[Consumable(since: '34.0.2')]
final class RemoteGroupReceiver implements InteractionReceiver {
	/**
	 * @since 34.0.2
	 */
	public function __construct(
		public readonly string $cloudIdString,
		private ?ICloudId $cloudId = null,
	) {
	}

	/**
	 * @since 34.0.2
	 */
	public function getCloudId(): ICloudId {
		return $this->cloudId ??= Server::get(ICloudIdManager::class)->resolveCloudId($this->cloudIdString);
	}

	/**
	 * @since 34.0.2
	 */
	#[\Override]
	public function getID(): string {
		return $this->cloudIdString;
	}
}
