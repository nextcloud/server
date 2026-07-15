<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Sharing\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Scans an external share with a specific path
 */
class ExternalShareScanJob extends QueuedJob {
	public function __construct(
		private readonly IConfig $config,
		private readonly IRootFolder $rootFolder,
		private readonly LoggerInterface $logger,
		ITimeFactory $time,
	) {
		parent::__construct($time);
	}

	#[\Override]
	protected function run($argument): void {
		if ($this->config->getSystemValueBool('files_no_background_scan', false)) {
			return;
		}

		[$userId, $path] = $argument;
		try {
			$this->rootFolder
				->getUserFolder($userId)
				->get($path)
				->getStorage()
				->getScanner()
				->scan('');
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), [ 'exception' => $e ]);
		}
	}
}
