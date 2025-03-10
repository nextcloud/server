<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\LookupServerConnector;

use OCA\LookupServerConnector\BackgroundJobs\RetryJob;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IUser;

/**
 * Class UpdateLookupServer
 *
 * @package OCA\LookupServerConnector
 */
class UpdateLookupServer {
	/**
	 * @param IJobList $jobList
	 * @param IConfig $config
	 */
	public function __construct(
		private IJobList $jobList,
		private IConfig $config,
	) {
	}

	/**
	 * @param IUser $user
	 */
	public function userUpdated(IUser $user): void {
		if (!$this->shouldUpdateLookupServer()) {
			return;
		}

		// Reset retry counter
		$this->config->deleteUserValue(
			$user->getUID(),
			'lookup_server_connector',
			'update_retries'
		);
		$this->jobList->add(RetryJob::class, ['userId' => $user->getUID()]);
	}

	/**
	 * check if we should update the lookup server, we only do it if
	 *
	 * + we have an internet connection
	 * + the lookup server update was not disabled by the admin
	 * + we have a valid lookup server URL
	 *
	 * @return bool
	 */
	private function shouldUpdateLookupServer(): bool {
		// TODO: Consider reenable for non-global-scale setups by checking "'files_sharing', 'lookupServerUploadEnabled'" instead of "gs.enabled"
		return $this->config->getSystemValueBool('gs.enabled', false)
			&& $this->config->getSystemValueBool('has_internet_connection', true)
			&& $this->config->getSystemValueString('lookup_server', 'https://lookup.nextcloud.com') !== '';
	}
}
