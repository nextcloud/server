<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\BackgroundJobs;

use OC\Async\Db\BlockMapper;
use OC\Async\ForkManager;
use OC\Async\Wrappers\LoggerBlockWrapper;
use OC\Config\Lexicon\CoreConfigLexicon;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Async\Enum\ProcessExecutionTime;
use OCP\BackgroundJob\TimedJob;
use OCP\IAppConfig;

class AsyncProcessJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private IAppConfig $appConfig,
		private ForkManager $forkManager,
		private BlockMapper $blockMapper,
		private LoggerBlockWrapper $loggerProcessWrapper,
	) {
		parent::__construct($time);

		$this->setTimeSensitivity(self::TIME_SENSITIVE);
//		$this->setInterval(60 * 5);
		$this->setInterval(1);
	}

	protected function run(mixed $argument): void {
		$this->discoverLoopAddress();

		$this->forkManager->setWrapper($this->loggerProcessWrapper);

		$this->blockMapper->resetFailedBlock();

		$metadata = ['executionTime' => ProcessExecutionTime::LATER];
		foreach ($this->blockMapper->getSessionOnStandBy() as $session) {
			$this->forkManager->forkSession($session, $metadata);
		}

		$this->blockMapper->removeSuccessfulBlock();

		$this->forkManager->waitChildProcess();
	}

	private function discoverLoopAddress(): void {
		if ($this->appConfig->hasKey('core', CoreConfigLexicon::ASYNC_LOOPBACK_ADDRESS, true)) {
			return;
		}

		$found = $this->forkManager->discoverLoopbackEndpoint();
		if ($found !== null) {
			$this->appConfig->setValueString('core', CoreConfigLexicon::ASYNC_LOOPBACK_ADDRESS, $found);
		}
	}
}
