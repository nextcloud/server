<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Config\App;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteConfig extends Base {
	protected function configure() {
		parent::configure();

		$this
			->setName('config:app:delete')
			->setDescription('Delete an app config value')
			->addArgument(
				'app',
				InputArgument::REQUIRED,
				'Name of the app'
			)
			->addArgument(
				'name',
				InputArgument::REQUIRED,
				'Name of the config to delete'
			)
			->addOption(
				'error-if-not-exists',
				null,
				InputOption::VALUE_NONE,
				'Checks whether the config exists before deleting it'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$appName = $input->getArgument('app');
		$configName = $input->getArgument('name');

		if ($input->hasParameterOption('--error-if-not-exists') && !in_array($configName, $this->appConfig->getKeys($appName), true)) {
			$output->writeln('<error>Config ' . $configName . ' of app ' . $appName . ' could not be deleted because it did not exist</error>');
			return 1;
		}

		$this->appConfig->deleteKey($appName, $configName);
		$output->writeln('<info>Config value ' . $configName . ' of app ' . $appName . ' deleted</info>');
		return 0;
	}
}
