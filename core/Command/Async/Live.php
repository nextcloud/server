<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Async;

use OC\Async\AsyncManager;
use OC\Async\AsyncProcess;
use OC\Async\Db\BlockMapper;
use OC\Async\ForkManager;
use OC\Async\Wrappers\CliBlockWrapper;
use OCP\Async\Enum\ProcessExecutionTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Live extends Command {
	public function __construct(
		private readonly BlockMapper $blockMapper,
		private readonly ForkManager $forkManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this->setName('async:live')
 			 ->setDescription('test');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		CliBlockWrapper::initStyle($output);
		$this->forkManager->setWrapper(new CliBlockWrapper($output));

		$metadata = ['_processExecutionTime' => ProcessExecutionTime::ASAP];
		while(true) {
			$this->blockMapper->resetFailedBlock();

			foreach ($this->blockMapper->getSessionOnStandBy() as $session) {
				$this->forkManager->forkSession($session, $metadata);
			}

			sleep(3);
		}

		$this->forkManager->waitChildProcess();

		return 0;
	}
}
