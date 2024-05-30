<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Profile;

use OCP\EventDispatcher\Event;

/**
 * Emitted before the rendering step of the public profile page happens.
 *
 * @since 25.0.0
 */
class BeforeTemplateRenderedEvent extends Event {
	private string $userId;

	/**
	 * @since 25.0.0
	 */
	public function __construct(string $userId) {
		parent::__construct();

		$this->userId = $userId;
	}

	/**
	 * @since 25.0.0
	 */
	public function getUserId(): string {
		return $this->userId;
	}
}
