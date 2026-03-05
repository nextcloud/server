<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\AccessFactory;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\ILDAPWrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestConfig extends Command {
	protected const ESTABLISHED = 0;
	protected const CONF_INVALID = 1;
	protected const BINDFAILURE = 2;
	protected const SEARCHFAILURE = 3;

	public function __construct(
		protected AccessFactory $accessFactory,
		protected Helper $helper,
		protected ILDAPWrapper $ldap,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('ldap:test-config')
			->setDescription('tests an LDAP configuration')
			->addArgument(
				'configID',
				InputArgument::REQUIRED,
				'the configuration ID'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$availableConfigs = $this->helper->getServerConfigurationPrefixes();
		$configID = $input->getArgument('configID');
		if (!in_array($configID, $availableConfigs)) {
			$output->writeln('Invalid configID');
			return self::FAILURE;
		}

		$result = $this->testConfig($configID);

		$message = match ($result) {
			static::ESTABLISHED => 'The configuration is valid and the connection could be established!',
			static::CONF_INVALID => 'The configuration is invalid. Please have a look at the logs for further details.',
			static::BINDFAILURE => 'The configuration is valid, but the bind failed. Please check the server settings and credentials.',
			static::SEARCHFAILURE => 'The configuration is valid and the bind passed, but a simple search on the base fails. Please check the server base setting.',
			default => 'Your LDAP server was kidnapped by aliens.',
		};

		$output->writeln($message);

		return $result === static::ESTABLISHED
			? self::SUCCESS
			: self::FAILURE;
	}

	/**
	 * Tests the specified connection
	 */
	protected function testConfig(string $configID): int {
		$connection = new Connection($this->ldap, $configID);

		// Ensure validation is run before we attempt the bind
		$connection->getConfiguration();

		if (!$connection->setConfiguration([
			'ldap_configuration_active' => 1,
		])) {
			return static::CONF_INVALID;
		}
		if (!$connection->bind()) {
			return static::BINDFAILURE;
		}
		$access = $this->accessFactory->get($connection);
		$result = $access->countObjects(1);
		if (!is_int($result) || ($result <= 0)) {
			return static::SEARCHFAILURE;
		}
		return static::ESTABLISHED;
	}
}
