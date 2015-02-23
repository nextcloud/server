<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
namespace OCA\user_ldap\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \OCA\user_ldap\lib\Helper;
use \OCA\user_ldap\lib\Configuration;

class ShowConfig extends Command {

	protected function configure() {
		$this
			->setName('ldap:show-config')
			->setDescription('shows the LDAP configuration')
			->addArgument(
					'configID',
					InputArgument::OPTIONAL,
					'will show the configuration of the specified id'
				     )
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$helper = new Helper();
		$availableConfigs = $helper->getServerConfigurationPrefixes();
		$configID = $input->getArgument('configID');
		if(!is_null($configID)) {
			$configIDs[] = $configID;
			if(!in_array($configIDs[0], $availableConfigs)) {
				$output->writeln("Invalid configID");
				return;
			}
		} else {
			$configIDs = $availableConfigs;
		}

		$this->renderConfigs($configIDs, $output);
	}

	/**
	 * prints the LDAP configuration(s)
	 * @param string[] configID(s)
	 * @param OutputInterface $output
	 */
	protected function renderConfigs($configIDs, $output) {
		foreach($configIDs as $id) {
			$configHolder = new Configuration($id);
			$configuration = $configHolder->getConfiguration();
			ksort($configuration);

			$table = $this->getHelperSet()->get('table');
			$table->setHeaders(array('Configuration', $id));
			$rows = array();
			foreach($configuration as $key => $value) {
				if($key === 'ldapAgentPassword') {
					$value = '***';
				}
				if(is_array($value)) {
					$value = implode(';', $value);
				}
				$rows[] = array($key, $value);
			}
			$table->setRows($rows);
			$table->render($output);
		}
	}
}
