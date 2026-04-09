<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\BackgroundJobs;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;

class LookupServerSendCheckBackgroundJob extends QueuedJob {
	public function __construct(
		protected IConfig $config,
		private IUserManager $userManager,
		ITimeFactory $time,
	) {
		parent::__construct($time);
	}

	/**
	 * @param array $argument
	 */
	public function run($argument): void {
		$this->userManager->callForSeenUsers(function (IUser $user): void {
			// If the user data was not updated yet (check if LUS is enabled and if then update on LUS or delete on LUS)
			// then we need to flag the user data to be checked
			if ($this->config->getUserValue($user->getUID(), 'lookup_server_connector', 'dataSend', '') === '') {
				$this->config->setUserValue($user->getUID(), 'lookup_server_connector', 'dataSend', '1');
			}
		});
	}
}
