<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\BackgroundJobs;

use OC\Cache\File;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IAppConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class FileCacheGcJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private readonly LoggerInterface $logger,
		private readonly IAppConfig $appConfig,
		private readonly IUserManager $userManager,
	) {
		parent::__construct($time);

		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
		$this->setInterval(24 * 60 * 60);
	}

	protected function run(mixed $argument): void {
		$offset = $this->appConfig->getValueInt('core', 'files_gc_offset');

		$users = $this->userManager->getSeenUsers($offset);
		$start = time();
		$count = 0;
		foreach ($users as $user) {
			$cache = new File();
			try {
				$cache->gc($user);
			} catch (\Exception $e) {
				$this->logger->warning('Exception when running cache gc.', [
					'app' => 'core',
					'exception' => $e,
				]);
			}
			$count++;
			$now = time();

			// almost time for the next job run, stop early and save our location
			if ($now - $start > 23 * 60 * 60) {
				$this->appConfig->setValueInt('core', 'files_gc_offset', $offset + $count);
				return;
			}
		}
		$this->appConfig->setValueInt('core', 'files_gc_offset', 0);
	}
}
