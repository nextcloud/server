<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Command;

use OC\Core\Command\Base;
use OCA\Files_External\Service\BackendService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Dependencies extends Base {
	public function __construct(
		private readonly BackendService $backendService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files_external:dependencies')
			->setDescription('Show information about the backend dependencies');
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$storageBackends = $this->backendService->getBackends();

		$anyMissing = false;

		foreach ($storageBackends as $backend) {
			if ($backend->getDeprecateTo() !== null) {
				continue;
			}
			$missingDependencies = $backend->checkDependencies();
			if ($missingDependencies) {
				$anyMissing = true;
				$output->writeln($backend->getText() . ':');
				foreach ($missingDependencies as $missingDependency) {
					if ($missingDependency->getMessage()) {
						$output->writeln(" - <comment>{$missingDependency->getDependency()}</comment>: {$missingDependency->getMessage()}");
					} else {
						$output->writeln(" - <comment>{$missingDependency->getDependency()}</comment>");
					}
				}
			}
		}

		if (!$anyMissing) {
			$output->writeln('<info>All dependencies are met</info>');
		}

		return self::SUCCESS;
	}
}
