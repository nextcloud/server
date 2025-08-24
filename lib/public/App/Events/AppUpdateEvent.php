<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\App\Events;

use OCP\EventDispatcher\Event;

/**
 * This event is triggered when an app is updated.
 *
 * @since 27.0.0
 */
class AppUpdateEvent extends Event {
	/**
	 * @since 27.0.0
	 */
	public function __construct(
		private readonly string $appId,
	) {
		parent::__construct();
	}

	/**
	 * @since 27.0.0
	 */
	public function getAppId(): string {
		return $this->appId;
	}
}
