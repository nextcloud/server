<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Federation\Events;

use OCP\EventDispatcher\Event;

/**
 * @since 25.0.0
 */
class TrustedServerRemovedEvent extends Event {
	private string $urlHash;

	/**
	 * @since 25.0.0
	 */
	public function __construct(string $urlHash) {
		parent::__construct();
		$this->urlHash = $urlHash;
	}

	/**
	 * @since 25.0.0
	 */
	public function getUrlHash(): string {
		return $this->urlHash;
	}
}
