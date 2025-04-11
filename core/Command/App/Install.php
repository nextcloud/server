<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\App;

use OC\Installer;
use OCP\App\IAppManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Install extends Command {
	public function __construct(
		protected IAppManager $appManager,
		private Installer $installer,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('app:install')
			->setDescription('install an app')
			->addArgument(
				'app-id',
				InputArgument::REQUIRED,
				'install the specified app'
			)
			->addOption(
				'keep-disabled',
				null,
				InputOption::VALUE_NONE,
				'don\'t enable the app afterwards'
			)
			->addOption(
				'force',
				'f',
				InputOption::VALUE_NONE,
				'install the app regardless of the Nextcloud version requirement'
			)
			->addOption(
				'allow-unstable',
				null,
				InputOption::VALUE_NONE,
				'allow installing an unstable releases'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$appId = $input->getArgument('app-id');
		$forceEnable = (bool)$input->getOption('force');

		if ($this->appManager->isEnabledForAnyone($appId)) {
			$output->writeln($appId . ' already installed');
			return 1;
		}

		try {
			$this->installer->downloadApp($appId, $input->getOption('allow-unstable'));
			$result = $this->installer->installApp($appId, $forceEnable);
		} catch (\Exception $e) {
			$output->writeln('Error: ' . $e->getMessage());
			return 1;
		}

		$appVersion = $this->appManager->getAppVersion($appId);
		$output->writeln($appId . ' ' . $appVersion . ' installed');

		if (!$input->getOption('keep-disabled')) {
			$this->appManager->enableApp($appId);
			$output->writeln($appId . ' enabled');
		}

		return 0;
	}
}
