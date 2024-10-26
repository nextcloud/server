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
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$configPrefix = $this->helper->getNextServerConfigurationPrefix();
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
