<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Events;

use OCP\EventDispatcher\Event;

/**
 * @since 18.0.0
 */
class FolderScannedEvent extends Event {
	/** @var string */
	private $absolutePath;

	/**
	 * @param string $absolutePath
	 *
	 * @since 18.0.0
	 */
	public function __construct(string $absolutePath) {
		parent::__construct();
		$this->absolutePath = $absolutePath;
	}

	/**
	 * @return string
	 * @since 18.0.0
	 */
	public function getAbsolutePath(): string {
		return $this->absolutePath;
	}
}
