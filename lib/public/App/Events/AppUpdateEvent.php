<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\App\Events;

use OCP\EventDispatcher\Event;

/**
 * @since 27.0.0
 */
class AppUpdateEvent extends Event {
	private string $appId;

	/**
	 * @since 27.0.0
	 */
	public function __construct(string $appId) {
		parent::__construct();

		$this->appId = $appId;
	}

	/**
	 * @since 27.0.0
	 */
	public function getAppId(): string {
		return $this->appId;
	}
}
