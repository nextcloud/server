<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\ConnectionFactory;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAP;
use OCP\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetConfig extends Command {
	protected function configure(): void {
		$this
			->setName('ldap:set-config')
			->setDescription('modifies an LDAP configuration')
			->addArgument(
				'configID',
				InputArgument::REQUIRED,
				'the configuration ID'
			)
			->addArgument(
				'configKey',
				InputArgument::REQUIRED,
				'the configuration key'
			)
			->addArgument(
				'configValue',
				InputArgument::REQUIRED,
				'the new configuration value'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$helper = Server::get(Helper::class);
		$availableConfigs = $helper->getServerConfigurationPrefixes();
		$configID = $input->getArgument('configID');
		if (!in_array($configID, $availableConfigs)) {
			$output->writeln('Invalid configID');
			return self::FAILURE;
		}

		$this->setValue(
			$configID,
			$input->getArgument('configKey'),
			$input->getArgument('configValue')
		);
		return self::SUCCESS;
	}

	/**
	 * save the configuration value as provided
	 */
	protected function setValue(string $configID, string $key, string $value): void {
		$configHolder = new Configuration($configID);
		$configHolder->$key = $value;
		$configHolder->saveConfiguration();

		$connectionFactory = new ConnectionFactory(new LDAP());
		$connectionFactory->get($configID)->clearCache();
	}
}
