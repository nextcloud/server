<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Jobs;

use OCA\User_LDAP\Service\UpdateGroupsService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\Exception;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class UpdateGroups extends TimedJob {
	public function __construct(
		private UpdateGroupsService $service,
		private LoggerInterface $logger,
		IConfig $config,
		ITimeFactory $timeFactory,
	) {
		parent::__construct($timeFactory);
		$this->interval = (int)$config->getAppValue('user_ldap', 'bgjRefreshInterval', '3600');
	}

	/**
	 * @param mixed $argument
	 * @throws Exception
	 */
	public function run($argument): void {
		$this->logger->debug('Run background job "updateGroups"');
		$this->service->updateGroups();
	}
}
