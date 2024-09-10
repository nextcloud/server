<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\User\BackgroundJobs;

use OC\User\FailedUsersBackend;
use OC\User\Manager;
use OC\User\User;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class CleanupDeletedUsers extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private Manager $userManager,
		private IConfig $config,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
		$this->setInterval(3600);
	}

	protected function run($argument): void {
		$backend = new FailedUsersBackend($this->config);
		$users = $backend->getUsers();

		if (empty($users)) {
			$this->logger->debug('No failed deleted users found.');
			return;
		}

		foreach ($users as $userId) {
			try {
				$user = new User(
					$userId,
					$backend,
					\OCP\Server::get(IEventDispatcher::class),
					config: $this->config,
				);
				$user->delete();
				$this->logger->info('Cleaned up deleted user {userId}', ['userId' => $userId]);
			} catch (\Throwable $error) {
				$this->logger->warning('Could not cleanup deleted user {userId}', ['userId' => $userId, 'exception' => $error]);
			}
		}
	}
}
