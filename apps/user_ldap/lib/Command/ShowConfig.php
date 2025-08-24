<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
	public function __construct(
		protected Helper $helper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
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
				$output->writeln('Invalid configID');
				return self::FAILURE;
			}
		} else {
			$configIDs = $availableConfigs;
		}

		$this->renderConfigs($configIDs, $input, $output);
		return self::SUCCESS;
	}

	/**
	 * prints the LDAP configuration(s)
	 *
	 * @param string[] $configIDs
	 */
	protected function renderConfigs(
		array $configIDs,
		InputInterface $input,
		OutputInterface $output,
	): void {
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
				continue;
			}

			foreach ($configuration as $key => $value) {
				if ($key === 'ldapAgentPassword' && !$showPassword) {
					$rows[$key] = '***';
				} else {
					$rows[$key] = $value;
				}
			}
			$configs[$id] = $rows;
		}
		if (!$renderTable) {
			$this->writeArrayInOutputFormat($input, $output, $configs);
		}
	}
}
