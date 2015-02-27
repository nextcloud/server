<?php
/**
 * Copyright (c) 2014 Martin Konrad <info@martin-konrad.net>
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

class CreateEmptyConfig extends Command {

	protected function configure() {
		$this
			->setName('ldap:create-empty-config')
			->setDescription('creates an empty LDAP configuration')
		;
	}


	protected function execute(InputInterface $input, OutputInterface $output) {
		$configPrefix = $this->getNewConfigurationPrefix();
		$output->writeln("Created new configuration with configID '{$configPrefix}'");

		$configHolder = new Configuration($configPrefix);
		$configHolder->saveConfiguration();
	}

	protected function getNewConfigurationPrefix() {
		$serverConnections = Helper::getServerConfigurationPrefixes();

		// first connection uses no prefix
		if(sizeof($serverConnections) == 0) {
			return '';
		}

		sort($serverConnections);
		$lastKey = array_pop($serverConnections);
		$lastNumber = intval(str_replace('s', '', $lastKey));
		$nextPrefix = 's' . str_pad($lastNumber + 1, 2, '0', STR_PAD_LEFT);
		return $nextPrefix;
	}
}
