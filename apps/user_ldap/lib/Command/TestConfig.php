<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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

	protected AccessFactory $accessFactory;
	protected Helper $helper;
	protected ILDAPWrapper $ldap;

	public function __construct(
		AccessFactory $accessFactory,
		Helper $helper,
		ILDAPWrapper $ldap
	) {
		$this->accessFactory = $accessFactory;
		$this->helper = $helper;
		$this->ldap = $ldap;
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
			return 1;
		}

		$result = $this->testConfig($configID);
		switch ($result) {
			case static::ESTABLISHED:
				$output->writeln('The configuration is valid and the connection could be established!');
				return 0;
			case static::CONF_INVALID:
				$output->writeln('The configuration is invalid. Please have a look at the logs for further details.');
				break;
			case static::BINDFAILURE:
				$output->writeln('The configuration is valid, but the bind failed. Please check the server settings and credentials.');
				break;
			case static::SEARCHFAILURE:
				$output->writeln('The configuration is valid and the bind passed, but a simple search on the base fails. Please check the server base setting.');
				break;
			default:
				$output->writeln('Your LDAP server was kidnapped by aliens.');
				break;
		}
		return 1;
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
