<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\BackgroundJobs;

use OC\Core\Db\LoginFlowV2Mapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class CleanupLoginFlowV2 extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private LoginFlowV2Mapper $loginFlowV2Mapper,
	) {
		parent::__construct($time);

		$this->setInterval(60 * 60);
	}

	protected function run($argument): void {
		$this->loginFlowV2Mapper->cleanup();
	}
}
