<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\IUser;
use OCP\IUserManager;

class RegisterRegenerateBirthdayCalendars extends QueuedJob {

	/** @var IUserManager */
	private $userManager;

	/** @var IJobList */
	private $jobList;

	/**
	 * RegisterRegenerateBirthdayCalendars constructor.
	 *
	 * @param ITimeFactory $time
	 * @param IUserManager $userManager
	 * @param IJobList $jobList
	 */
	public function __construct(ITimeFactory $time,
		IUserManager $userManager,
		IJobList $jobList) {
		parent::__construct($time);
		$this->userManager = $userManager;
		$this->jobList = $jobList;
	}

	/**
	 * @inheritDoc
	 */
	public function run($argument) {
		$this->userManager->callForSeenUsers(function (IUser $user) {
			$this->jobList->add(GenerateBirthdayCalendarBackgroundJob::class, [
				'userId' => $user->getUID(),
				'purgeBeforeGenerating' => true
			]);
		});
	}
}
