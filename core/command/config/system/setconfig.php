<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
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

namespace OC\Core\Command\Config\System;

use OC\Core\Command\Base;
use OC\SystemConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetConfig extends Base {
	/** * @var SystemConfig */
	protected $systemConfig;

	/**
	 * @param SystemConfig $systemConfig
	 */
	public function __construct(SystemConfig $systemConfig) {
		parent::__construct();
		$this->systemConfig = $systemConfig;
	}

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

	protected function execute(InputInterface $input, OutputInterface $output) {
		$configNames = $input->getArgument('name');
		$configName = $configNames[0];
		$configValue = $this->castValue($input->getOption('value'), $input->getOption('type'));
		$updateOnly = $input->getOption('update-only');

		if (count($configNames) > 1) {
			$existingValue = $this->systemConfig->getValue($configName);

			$newValue = $this->mergeArrayValue(
				array_slice($configNames, 1), $existingValue, $configValue, $updateOnly
			);

			$this->systemConfig->setValue($configName, $newValue);
		} else {
			if ($updateOnly && !in_array($configName, $this->systemConfig->getKeys(), true)) {
				throw new \UnexpectedValueException('Config parameter does not exist');
			}

			$this->systemConfig->setValue($configName, $configValue);
		}

		$output->writeln('<info>System config value ' . implode(' => ', $configNames) . ' set to ' . $configValue . '</info>');
		return 0;
	}

	/**
	 * @param string $value
	 * @param string $type
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	protected function castValue($value, $type) {
		if ($value === null) {
			return null;
		}

		$type = strtolower($type);
		switch ($type) {
		case 'string':
		case 'str':
		case 's':
			return $value;

		case 'integer':
		case 'int':
		case 'i':
			if (!is_numeric($value)) {
				throw new \InvalidArgumentException('Non-numeric value specified');
			}
			return (int) $value;

		case 'double':
		case 'd':
		case 'float':
		case 'f':
			if (!is_numeric($value)) {
				throw new \InvalidArgumentException('Non-numeric value specified');
			}
			return (double) $value;

		case 'boolean':
		case 'bool':
		case 'b':
			$value = strtolower($value);
			switch ($value) {
			case 'true':
			case 'yes':
			case 'y':
			case '1':
				return true;

			case 'false':
			case 'no':
			case 'n':
			case '0':
				return false;

			default:
				throw new \InvalidArgumentException('Unable to parse value as boolean');
			}

		default:
			throw new \InvalidArgumentException('Invalid type');
		}
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

}
