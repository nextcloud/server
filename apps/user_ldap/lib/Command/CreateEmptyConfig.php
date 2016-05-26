<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Martin Konrad <konrad@frib.msu.edu>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\User_LDAP\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use \OCA\User_LDAP\Helper;
use \OCA\User_LDAP\Configuration;

class CreateEmptyConfig extends Command {
	/** @var \OCA\User_LDAP\Helper */
	protected $helper;

	/**
	 * @param Helper $helper
	 */
	public function __construct(Helper $helper) {
		$this->helper = $helper;
		parent::__construct();
	}

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
		$serverConnections = $this->helper->getServerConfigurationPrefixes();

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
