<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Maintenance;

use OCP\IConfig;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Mode extends Command {
	public function __construct(
		protected IConfig $config,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('maintenance:mode')
			->setDescription('Show or toggle maintenance mode status')
			->setHelp('Maintenance mode prevents new logins, locks existing sessions, and disables background jobs.')
			->addOption(
				'on',
				null,
				InputOption::VALUE_NONE,
				'enable maintenance mode'
			)
			->addOption(
				'off',
				null,
				InputOption::VALUE_NONE,
				'disable maintenance mode'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$maintenanceMode = $this->config->getSystemValueBool('maintenance');
		if ($input->getOption('on')) {
			if ($maintenanceMode === false) {
				$this->config->setSystemValue('maintenance', true);
				$output->writeln('Maintenance mode enabled');
			} else {
				$output->writeln('Maintenance mode already enabled');
			}
		} elseif ($input->getOption('off')) {
			if ($maintenanceMode === true) {
				$this->config->setSystemValue('maintenance', false);
				$output->writeln('Maintenance mode disabled');
			} else {
				$output->writeln('Maintenance mode already disabled');
			}
		} else {
			if ($maintenanceMode) {
				$output->writeln('Maintenance mode is currently enabled');
			} else {
				$output->writeln('Maintenance mode is currently disabled');
			}
		}
		return 0;
	}
}
