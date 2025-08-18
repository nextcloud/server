<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\BackgroundJob;

use OCA\UserStatus\Db\UserStatusMapper;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

/**
 * Class ClearOldStatusesBackgroundJob
 *
 * @package OCA\UserStatus\BackgroundJob
 */
class ClearOldStatusesBackgroundJob extends TimedJob {

	/**
	 * ClearOldStatusesBackgroundJob constructor.
	 *
	 * @param ITimeFactory $time
	 * @param UserStatusMapper $mapper
	 */
	public function __construct(
		ITimeFactory $time,
		private UserStatusMapper $mapper,
	) {
		parent::__construct($time);

		$this->setInterval(60);
	}

	/**
	 * @inheritDoc
	 */
	protected function run($argument) {
		$now = $this->time->getTime();

		$this->mapper->clearOlderThanClearAt($now);
		$this->mapper->clearStatusesOlderThan($now - StatusService::INVALIDATE_STATUS_THRESHOLD, $now);
	}
}
