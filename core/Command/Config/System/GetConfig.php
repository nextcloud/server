<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Config\System;

use OC\SystemConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetConfig extends Base {
	public function __construct(
		SystemConfig $systemConfig,
	) {
		parent::__construct($systemConfig);
	}

	protected function configure() {
		parent::configure();

		$this
			->setName('config:system:get')
			->setDescription('Get a system config value')
			->addArgument(
				'name',
				InputArgument::REQUIRED | InputArgument::IS_ARRAY,
				'Name of the config to get, specify multiple for array parameter'
			)
			->addOption(
				'default-value',
				null,
				InputOption::VALUE_OPTIONAL,
				'If no default value is set and the config does not exist, the command will exit with 1'
			)
		;
	}

	/**
	 * Executes the current command.
	 *
	 * @param InputInterface $input An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 * @return int 0 if everything went fine, or an error code
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$configNames = $input->getArgument('name');
		$configName = array_shift($configNames);
		$defaultValue = $input->getOption('default-value');

		if (!in_array($configName, $this->systemConfig->getKeys()) && !$input->hasParameterOption('--default-value')) {
			return 1;
		}

		if (!in_array($configName, $this->systemConfig->getKeys())) {
			$configValue = $defaultValue;
		} else {
			$configValue = $this->systemConfig->getValue($configName);
			if (!empty($configNames)) {
				foreach ($configNames as $configName) {
					if (isset($configValue[$configName])) {
						$configValue = $configValue[$configName];
					} elseif (!$input->hasParameterOption('--default-value')) {
						return 1;
					} else {
						$configValue = $defaultValue;
						break;
					}
				}
			}
		}

		$this->writeMixedInOutputFormat($input, $output, $configValue);
		return 0;
	}
}
