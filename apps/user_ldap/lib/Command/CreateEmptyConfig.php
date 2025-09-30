<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateEmptyConfig extends Command {
	public function __construct(
		protected Helper $helper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ldap:create-empty-config')
			->setDescription('creates an empty LDAP configuration')
			->addOption(
				'only-print-prefix',
				'p',
				InputOption::VALUE_NONE,
				'outputs only the prefix'
			)
			->addOption(
				'set-prefix',
				's',
				InputOption::VALUE_OPTIONAL,
				'manually set the prefix (must be unique)',
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$configPrefix = $input->getOption('set-prefix');
		if (is_string($configPrefix)) {
			if ($configPrefix === '') {
				$output->writeln('The prefix cannot be empty');
				return self::FAILURE;
			}
			if (preg_match('/^[a-zA-Z0-9-_]+$/', $configPrefix) !== 1) {
				$output->writeln('The prefix may only contain alphanumeric characters, dashes and underscores');
				return self::FAILURE;
			}
			$configPrefix = $this->helper->registerNewServerConfigurationPrefix($configPrefix);
			if ($configPrefix === false) {
				$output->writeln('The prefix is already in use');
				return self::FAILURE;
			}
		} else {
			$configPrefix = $this->helper->getNextServerConfigurationPrefix();
		}
		$configHolder = new Configuration($configPrefix);
		$configHolder->ldapConfigurationActive = false;
		$configHolder->saveConfiguration();

		$prose = '';
		if (!$input->getOption('only-print-prefix')) {
			$prose = 'Created new configuration with configID ';
		}
		$output->writeln($prose . "{$configPrefix}");
		return self::SUCCESS;
	}
}
