<?php
/**
 * @copyright Copyright (c) Guillaume Colson <guillaume.colson@univ-lorraine.fr>
 *
 * @author Guillaume Colson <guillaume.colson@univ-lorraine.fr>
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

use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAP;

use OCA\User_LDAP\User_Proxy;
use OCP\IConfig;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncUser extends Command {
	/** @var \OCP\IConfig */
	protected $ocConfig;
	/** @var User_Proxy */
	protected $backend;
	/** @var Helper */
	protected $helper;

	public function __construct(IConfig $ocConfig, User_Proxy $uBackend, Helper $helper) {
		$this->ocConfig = $ocConfig;
		$this->backend = $uBackend;
		$this->helper = $helper;

		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('ldap:syncuser')
			->setDescription('Synchronize user from LDAP immediately')
			->addArgument(
					'uid',
					InputArgument::REQUIRED,
					'the uid of the account to sync'
					 )
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$configPrefixes = $this->helper->getServerConfigurationPrefixes(true);
		$prefix = $this->ocConfig->getAppValue('user_ldap', 'background_sync_prefix', null);
		$ldapWrapper = new LDAP();

		$access = $this->backend->getLDAPAccess($uid);

		$connection = $access->getConnection();

		$loginName = $access->escapeFilterPart($input->getArgument('uid'));
		$filter = str_replace('%uid', $loginName, $connection->ldapLoginFilter);

		$results = $access->fetchListOfUsers(
			$filter,
			$access->userManager->getAttributes(),
			1,
			0,
			true
		);

		if (count($results) > 0) {
			$line = 'Sync of '. $results[0]['cn'][0] .' ('. $results[0]['uid'][0] .') done';
			$output->writeln($line);
		} else {
			$output->writeln('No user found with uid : '.$input->getArgument('uid'));
		}
		return 0;
	}
}
