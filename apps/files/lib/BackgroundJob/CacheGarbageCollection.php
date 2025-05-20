<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\BackgroundJob;

use Exception;
use OC\Cache\File;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class CacheGarbageCollection extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private IUserManager $userManager,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);

		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
		$this->setInterval(24 * 60 * 60);
	}

	protected function run(mixed $argument): void {
		$cache = new File();

		$this->userManager->callForSeenUsers(function (IUser $user) use ($cache): void {
			$userId = $user->getUID();

			try {
				$cache->gc($userId);
			} catch (Exception $e) {
				$this->logger->warning('Exception when running cache gc.', [
					'app' => 'files',
					'user' => $userId,
					'exception' => $e,
				]);
			}
		});
	}
}
