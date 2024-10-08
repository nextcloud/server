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

	/** @var UserStatusMapper */
	private $mapper;

	/**
	 * ClearOldStatusesBackgroundJob constructor.
	 *
	 * @param ITimeFactory $time
	 * @param UserStatusMapper $mapper
	 */
	public function __construct(ITimeFactory $time,
		UserStatusMapper $mapper) {
		parent::__construct($time);
		$this->mapper = $mapper;

		// Run every time the cron is run
		$this->setInterval(0);
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
