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

	/**
	 * @since 25.0.0
	 * @since 32.0.0 Added $url argument
	 */
	public function __construct(
		private readonly string $urlHash,
		private readonly ?string $url = null,
	) {
		parent::__construct();
	}

	/**
	 * @since 25.0.0
	 */
	public function getUrlHash(): string {
		return $this->urlHash;
	}

	/**
	 * @since 32.0.0
	 */
	public function getUrl(): ?string {
		return $this->url;
	}
}
