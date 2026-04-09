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

class DeleteConfig extends Base {
	public function __construct(
		SystemConfig $systemConfig,
	) {
		parent::__construct($systemConfig);
	}

	protected function configure() {
		parent::configure();

		$this
			->setName('config:system:delete')
			->setDescription('Delete a system config value')
			->addArgument(
				'name',
				InputArgument::REQUIRED | InputArgument::IS_ARRAY,
				'Name of the config to delete, specify multiple for array parameter'
			)
			->addOption(
				'error-if-not-exists',
				null,
				InputOption::VALUE_NONE,
				'Checks whether the config exists before deleting it'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$configNames = $input->getArgument('name');
		$configName = $configNames[0];

		if (count($configNames) > 1) {
			if ($input->hasParameterOption('--error-if-not-exists') && !in_array($configName, $this->systemConfig->getKeys())) {
				$output->writeln('<error>System config ' . implode(' => ', $configNames) . ' could not be deleted because it did not exist</error>');
				return 1;
			}

			$value = $this->systemConfig->getValue($configName);

			try {
				$value = $this->removeSubValue(array_slice($configNames, 1), $value, $input->hasParameterOption('--error-if-not-exists'));
			} catch (\UnexpectedValueException $e) {
				$output->writeln('<error>System config ' . implode(' => ', $configNames) . ' could not be deleted because it did not exist</error>');
				return 1;
			}

			$this->systemConfig->setValue($configName, $value);
			$output->writeln('<info>System config value ' . implode(' => ', $configNames) . ' deleted</info>');
			return 0;
		} else {
			if ($input->hasParameterOption('--error-if-not-exists') && !in_array($configName, $this->systemConfig->getKeys())) {
				$output->writeln('<error>System config ' . $configName . ' could not be deleted because it did not exist</error>');
				return 1;
			}

			$this->systemConfig->deleteValue($configName);
			$output->writeln('<info>System config value ' . $configName . ' deleted</info>');
			return 0;
		}
	}

	protected function removeSubValue($keys, $currentValue, $throwError) {
		$nextKey = array_shift($keys);

		if (is_array($currentValue)) {
			if (isset($currentValue[$nextKey])) {
				if (empty($keys)) {
					unset($currentValue[$nextKey]);
				} else {
					$currentValue[$nextKey] = $this->removeSubValue($keys, $currentValue[$nextKey], $throwError);
				}
			} elseif ($throwError) {
				throw new \UnexpectedValueException('Config parameter does not exist');
			}
		} elseif ($throwError) {
			throw new \UnexpectedValueException('Config parameter does not exist');
		}

		return $currentValue;
	}
}
