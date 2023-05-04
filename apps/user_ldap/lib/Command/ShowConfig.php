<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Johannes Leuker <j.leuker@hosting.de>
 * @author Laurens Post <Crote@users.noreply.github.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

use OC\Core\Command\Base;
use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\Helper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ShowConfig extends Base {
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
			->addOption(
					'output',
					null,
					InputOption::VALUE_OPTIONAL,
					'Output format (table, plain, json or json_pretty, default is table)',
					'table'
					 )
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$availableConfigs = $this->helper->getServerConfigurationPrefixes();
		$configID = $input->getArgument('configID');
		if (!is_null($configID)) {
			$configIDs[] = $configID;
			if (!in_array($configIDs[0], $availableConfigs)) {
				$output->writeln("Invalid configID");
				return 1;
			}
		} else {
			$configIDs = $availableConfigs;
		}

		$this->renderConfigs($configIDs, $input, $output);
		return 0;
	}

	/**
	 * prints the LDAP configuration(s)
	 * @param string[] configID(s)
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function renderConfigs($configIDs, $input, $output) {
		$renderTable = $input->getOption('output') === 'table' or $input->getOption('output') === null;
		$showPassword = $input->getOption('show-password');

		$configs = [];
		foreach ($configIDs as $id) {
			$configHolder = new Configuration($id);
			$configuration = $configHolder->getConfiguration();
			ksort($configuration);

			$rows = [];
			if ($renderTable) {
				foreach ($configuration as $key => $value) {
					if (is_array($value)) {
						$value = implode(';', $value);
					}
					if ($key === 'ldapAgentPassword' && !$showPassword) {
						$rows[] = [$key, '***'];
					} else {
						$rows[] = [$key, $value];
					}
				}
				$table = new Table($output);
				$table->setHeaders(['Configuration', $id]);
				$table->setRows($rows);
				$table->render();
			} else {
				foreach ($configuration as $key => $value) {
					if ($key === 'ldapAgentPassword' && !$showPassword) {
						$rows[$key] = '***';
					} else {
						$rows[$key] = $value;
					}
				}
				$configs[$id] = $rows;
			}
		}
		if (!$renderTable) {
			$this->writeArrayInOutputFormat($input, $output, $configs);
		}
	}
}
