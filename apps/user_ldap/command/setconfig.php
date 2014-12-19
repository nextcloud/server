<?php
/**
 * Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\user_ldap\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \OCA\user_ldap\lib\Helper;
use \OCA\user_ldap\lib\Configuration;

class SetConfig extends Command {

	protected function configure() {
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

	protected function execute(InputInterface $input, OutputInterface $output) {
		$helper = new Helper();
		$availableConfigs = $helper->getServerConfigurationPrefixes();
		$configID = $input->getArgument('configID');
		if(!in_array($configID, $availableConfigs)) {
			$output->writeln("Invalid configID");
			return;
		}

		$this->setValue(
			$configID,
			$input->getArgument('configKey'),
			$input->getArgument('configValue')
		);
	}

	/**
	 * save the configuration value as provided
	 * @param string $configID
	 * @param string $configKey
	 * @param string $configValue
	 */
	protected function setValue($configID, $key, $value) {
		$configHolder = new Configuration($configID);
		$configHolder->$key = $value;
		$configHolder->saveConfiguration();
	}
}
