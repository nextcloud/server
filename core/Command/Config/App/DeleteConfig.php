<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Core\Command\Config\App;

use OCP\Config\BeforeAppConfigDeletedEvent;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteConfig extends Base {
	#[\Override]
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
			->addOption(
				'force',
				'f',
				InputOption::VALUE_NONE,
				'Delete the value even if the app rejects it',
			)
		;
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$appName = $input->getArgument('app');
		$configName = $input->getArgument('name');

		if ($input->hasParameterOption('--error-if-not-exists') && !in_array($configName, $this->appConfig->getKeys($appName), true)) {
			$output->writeln('<error>Config ' . $configName . ' of app ' . $appName . ' could not be deleted because it did not exist</error>');
			return 1;
		}

		$event = new BeforeAppConfigDeletedEvent($appName, $configName);
		$this->eventDispatcher->dispatchTyped($event);
		if ($event->isRejected()) {
			if (!$input->getOption('force')) {
				$output->writeln('<error>Deleting config ' . $configName . ' of app ' . $appName . ' was rejected, use --force to delete it anyway</error>');
				return 1;
			}
			$output->writeln('<comment>Deleting config ' . $configName . ' of app ' . $appName . ' was rejected, deleting it anyway</comment>');
		}

		$this->appConfig->deleteKey($appName, $configName);
		$output->writeln('<info>Config value ' . $configName . ' of app ' . $appName . ' deleted</info>');
		return 0;
	}
}
