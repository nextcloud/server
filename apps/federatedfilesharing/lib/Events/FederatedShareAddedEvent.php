<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\FederatedFileSharing\Events;

use OCP\EventDispatcher\Event;

/**
 * This event is triggered when a federated share is successfully added
 *
 * @since 20.0.0
 */
class FederatedShareAddedEvent extends Event {

	/**
	 * @since 20.0.0
	 */
	public function __construct(
		private string $remote,
	) {
	}

	/**
	 * @since 20.0.0
	 */
	public function getRemote(): string {
		return $this->remote;
	}
}
