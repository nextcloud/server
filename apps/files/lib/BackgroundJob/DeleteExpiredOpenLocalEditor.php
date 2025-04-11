<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\BackgroundJob;

use OCA\Files\Db\OpenLocalEditorMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

/**
 * Delete all expired "Open local editor" token
 */
class DeleteExpiredOpenLocalEditor extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		protected OpenLocalEditorMapper $mapper,
	) {
		parent::__construct($time);

		// Run every 12h
		$this->interval = 12 * 3600;
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	/**
	 * Makes the background job do its work
	 *
	 * @param array $argument unused argument
	 */
	public function run($argument): void {
		$this->mapper->deleteExpiredTokens($this->time->getTime());
	}
}
