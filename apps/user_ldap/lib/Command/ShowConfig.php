<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Laurens Post <Crote@users.noreply.github.com>
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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\Configuration;

class ShowConfig extends Command {
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
			->setName('ldap:show-config')
			->setDescription('shows the LDAP configuration')
			->addArgument(
					'configID',
					InputArgument::OPTIONAL,
					'will show the configuration of the specified id'
				     )
			->addOption(
					'show-password',
					null,
					InputOption::VALUE_NONE,
					'show ldap bind password'
				     )
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$availableConfigs = $this->helper->getServerConfigurationPrefixes();
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

		$this->renderConfigs($configIDs, $output, $input->getOption('show-password'));
	}

	/**
	 * prints the LDAP configuration(s)
	 * @param string[] configID(s)
	 * @param OutputInterface $output
	 * @param bool $withPassword      Set to TRUE to show plaintext passwords in output
	 */
	protected function renderConfigs($configIDs, $output, $withPassword) {
		foreach($configIDs as $id) {
			$configHolder = new Configuration($id);
			$configuration = $configHolder->getConfiguration();
			ksort($configuration);

			$table = $this->getHelperSet()->get('table');
			$table->setHeaders(array('Configuration', $id));
			$rows = array();
			foreach($configuration as $key => $value) {
				if($key === 'ldapAgentPassword' && !$withPassword) {
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
