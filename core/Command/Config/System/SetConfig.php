<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Core\Command\Config\System;

use OC\SystemConfig;
use OCP\IConfig;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetConfig extends Base {
	public function __construct(
		SystemConfig $systemConfig,
		private CastHelper $castHelper,
	) {
		parent::__construct($systemConfig);
	}

	#[\Override]
	protected function configure() {
		parent::configure();

		$this
			->setName('config:system:set')
			->setDescription('Set a system config value')
			->addArgument(
				'name',
				InputArgument::REQUIRED | InputArgument::IS_ARRAY,
				'Name of the config parameter, specify multiple for array parameter'
			)
			->addOption(
				'type',
				null,
				InputOption::VALUE_REQUIRED,
				'Value type [string, integer, double, boolean]',
				'string'
			)
			->addOption(
				'value',
				null,
				InputOption::VALUE_REQUIRED,
				'The new value of the config'
			)
			->addOption(
				'update-only',
				null,
				InputOption::VALUE_NONE,
				'Only updates the value, if it is not set before, it is not being added'
			)
		;
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$configNames = $input->getArgument('name');
		$configName = $configNames[0];
		$configValue = $this->castHelper->castValue($input->getOption('value'), $input->getOption('type'));
		$updateOnly = $input->getOption('update-only');

		if (count($configNames) > 1) {
			$existingValue = $this->systemConfig->getValue($configName);

			if ($this->hasNestedValue($existingValue, array_slice($configNames, 1))
				&& $this->valuesEqual(
					$this->getNestedValue($existingValue, array_slice($configNames, 1)),
					$configValue['value']
				)) {
				$readableValue = $this->getReadableValue($configNames, $configValue);
				$output->writeln('<info>System config value ' . implode(' => ', $configNames) . ' already set to ' . $readableValue . '</info>');
				return 0;
			}

			$newValue = $this->mergeArrayValue(
				array_slice($configNames, 1), $existingValue, $configValue['value'], $updateOnly
			);

			$this->systemConfig->setValue($configName, $newValue);
		} else {
			if ($updateOnly && !in_array($configName, $this->systemConfig->getKeys(), true)) {
				throw new \UnexpectedValueException('Config parameter does not exist');
			}

			if (in_array($configName, $this->systemConfig->getKeys(), true)
				&& $this->valuesEqual($this->systemConfig->getValue($configName), $configValue['value'])) {
				$readableValue = $this->getReadableValue($configNames, $configValue);
				$output->writeln('<info>System config value ' . implode(' => ', $configNames) . ' already set to ' . $readableValue . '</info>');
				return 0;
			}

			$this->systemConfig->setValue($configName, $configValue['value']);
		}

		$readableValue = $this->getReadableValue($configNames, $configValue);
		$output->writeln('<info>System config value ' . implode(' => ', $configNames) . ' set to ' . $readableValue . '</info>');
		return 0;
	}

	/**
	 * @param mixed $existingValues
	 * @param string[] $path
	 */
	protected function hasNestedValue(mixed $existingValues, array $path): bool {
		$current = $existingValues;
		foreach ($path as $key) {
			if (!is_array($current) || !array_key_exists($key, $current)) {
				return false;
			}
			$current = $current[$key];
		}
		return true;
	}

	/**
	 * @param mixed $existingValues
	 * @param string[] $path
	 * @return mixed
	 */
	protected function getNestedValue(mixed $existingValues, array $path): mixed {
		$current = $existingValues;
		foreach ($path as $key) {
			$current = $current[$key];
		}
		return $current;
	}

	protected function valuesEqual(mixed $current, mixed $new): bool {
		return $current === $new;
	}

	/**
	 * Mask sensitive values
	 * @param string[] $configNames
	 * @param array{value: mixed, readable-value: string} $configValue
	 */
	protected function getReadableValue(array $configNames, array $configValue): string {
		$filteredValue = $this->systemConfig->getFilteredValue($configNames[0]);
		foreach (array_slice($configNames, 1) as $key) {
			if (!is_array($filteredValue) || !array_key_exists($key, $filteredValue)) {
				// Nothing got filtered along this path
				return $configValue['readable-value'];
			}
			$filteredValue = $filteredValue[$key];
		}

		if ($filteredValue === $configValue['value']) {
			return $configValue['readable-value'];
		}

		if ($filteredValue === IConfig::SENSITIVE_VALUE) {
			return IConfig::SENSITIVE_VALUE;
		}

		return 'array ' . json_encode($filteredValue);
	}

	/**
	 * @param array $configNames
	 * @param mixed $existingValues
	 * @param mixed $value
	 * @param bool $updateOnly
	 * @return array merged value
	 * @throws \UnexpectedValueException
	 */
	protected function mergeArrayValue(array $configNames, $existingValues, $value, $updateOnly) {
		$configName = array_shift($configNames);
		if (!is_array($existingValues)) {
			$existingValues = [];
		}
		if (!empty($configNames)) {
			if (isset($existingValues[$configName])) {
				$existingValue = $existingValues[$configName];
			} else {
				$existingValue = [];
			}
			$existingValues[$configName] = $this->mergeArrayValue($configNames, $existingValue, $value, $updateOnly);
		} else {
			if (!isset($existingValues[$configName]) && $updateOnly) {
				throw new \UnexpectedValueException('Config parameter does not exist');
			}
			$existingValues[$configName] = $value;
		}
		return $existingValues;
	}

	/**
	 * @param string $optionName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	#[\Override]
	public function completeOptionValues($optionName, CompletionContext $context) {
		if ($optionName === 'type') {
			return ['string', 'integer', 'double', 'boolean', 'json', 'null'];
		}
		return parent::completeOptionValues($optionName, $context);
	}
}
