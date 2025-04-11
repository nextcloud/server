<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;
use OCP\Files\File;

/**
 * @since 22.0.0
 */
class BeforeFileDirectDownloadedEvent extends Event {
	public function __construct(
		private File $file,
	) {
		parent::__construct();
	}

	/**
	 * @return File
	 * @since 22.0.0
	 */
	public function getFile(): File {
		return $this->file;
	}
}
