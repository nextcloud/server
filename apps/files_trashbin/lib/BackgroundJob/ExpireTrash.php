<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\BackgroundJob;

use OC\Files\SetupManager;
use OCA\Files_Trashbin\Expiration;
use OCA\Files_Trashbin\Service\ExpireService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IAppConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class ExpireTrash extends TimedJob {
	public function __construct(
		readonly private IAppConfig $appConfig,
		readonly private IUserManager $userManager,
		readonly private Expiration $expiration,
		readonly private ExpireService $expireService,
		readonly private SetupManager $setupManager,
		readonly private LoggerInterface $logger,
		ITimeFactory $time,
	) {
		parent::__construct($time);
		// Run once per 30 minutes
		$this->setInterval(60 * 30);
	}

	protected function run($argument) {
		$backgroundJob = $this->appConfig->getValueString('files_trashbin', 'background_job_expire_trash', 'yes');
		if ($backgroundJob === 'no') {
			return;
		}

		$maxAge = $this->expiration->getMaxAgeAsTimestamp();
		if (!$maxAge) {
			return;
		}

		$stopTime = time() + 60 * 30; // Stops after 30 minutes.
		$offset = $this->appConfig->getValueInt('files_trashbin', 'background_job_expire_trash_offset', 0);
		$users = $this->userManager->getSeenUsers($offset);

		foreach ($users as $user) {
			try {
				$this->expireService->expireTrashForUser($user);
			} catch (\Throwable $e) {
				$this->logger->error('Error while expiring trashbin for user ' . $user->getUID(), ['exception' => $e]);
			}

			$offset++;

			if ($stopTime < time()) {
				$this->appConfig->setValueInt('files_trashbin', 'background_job_expire_trash_offset', $offset);
				$this->setupManager->tearDown();
				return;
			}
		}

		$this->appConfig->setValueInt('files_trashbin', 'background_job_expire_trash_offset', 0);
		$this->setupManager->tearDown();
	}
}
