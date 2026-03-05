<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DirectEditing\IManager;

class CleanupDirectEditingTokens extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private IManager $manager,
	) {
		parent::__construct($time);
		$this->setInterval(15 * 60);
	}

	/**
	 * Makes the background job do its work
	 *
	 * @param array $argument unused argument
	 * @throws \Exception
	 */
	public function run($argument) {
		$this->manager->cleanup();
	}
}
