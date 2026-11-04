<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Log;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use OCP\Log\IFileBased;
use OCP\Log\ILogFactory;
use OCP\Log\RotationTrait;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * This rotates the current logfile to a new name, this way the total log usage
 * will stay limited and older entries are available for a while longer.
 * For more professional log management set the 'logfile' config to a different
 * location and manage that with your own tools.
 */
class Rotate extends TimedJob {
	use RotationTrait;

	public function __construct(ITimeFactory $time) {
		parent::__construct($time);

		$this->setInterval(3600);
	}

	#[\Override]
	public function run($argument): void {
		$config = Server::get(IConfig::class);
		$logFile = Server::get(ILogFactory::class)->get('file');
		if (!$logFile instanceof IFileBased) {
			return;
		}
		$this->filePath = $logFile->getLogFilePath();

		$this->maxSize = $config->getSystemValueInt('log_rotate_size', 100 * 1024 * 1024);
		if ($this->shouldRotateBySize()) {
			$rotatedFile = $this->rotate();
			$msg = 'Log file "' . $this->filePath . '" was over ' . $this->maxSize . ' bytes, moved to "' . $rotatedFile . '"';
			Server::get(LoggerInterface::class)->info($msg, ['app' => Rotate::class]);
		}
	}
}
