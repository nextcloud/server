<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Config\App;

use OC\AppConfig;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class SetConfig extends Base {
	protected function configure() {
		parent::configure();

		$this
			->setName('config:app:set')
			->setDescription('Set an app config value')
			->addArgument(
				'app',
				InputArgument::REQUIRED,
				'Name of the app'
			)
			->addArgument(
				'name',
				InputArgument::REQUIRED,
				'Name of the config to set'
			)
			->addOption(
				'value',
				null,
				InputOption::VALUE_REQUIRED,
				'The new value of the config'
			)
			->addOption(
				'type',
				null,
				InputOption::VALUE_REQUIRED,
				'Value type [string, integer, float, boolean, array]',
				'string'
			)
			->addOption(
				'lazy',
				null,
				InputOption::VALUE_NEGATABLE,
				'Set value as lazy loaded',
			)
			->addOption(
				'sensitive',
				null,
				InputOption::VALUE_NEGATABLE,
				'Set value as sensitive',
			)
			->addOption(
				'update-only',
				null,
				InputOption::VALUE_NONE,
				'Only updates the value, if it is not set before, it is not being added'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$appName = $input->getArgument('app');
		$configName = $input->getArgument('name');

		if (!($this->appConfig instanceof AppConfig)) {
			throw new \Exception('Only compatible with OC\AppConfig as it uses internal methods');
		}

		if ($input->hasParameterOption('--update-only') && !$this->appConfig->hasKey($appName, $configName)) {
			$output->writeln(
				'<comment>Config value ' . $configName . ' for app ' . $appName
				. ' not updated, as it has not been set before.</comment>'
			);

			return 1;
		}

		$type = $typeString = null;
		if ($input->hasParameterOption('--type')) {
			$typeString = $input->getOption('type');
			$type = $this->appConfig->convertTypeToInt($typeString);
		}

		/**
		 * If --Value is not specified, returns an exception if no value exists in database
		 * compare with current status in database and displays a reminder that this can break things.
		 * confirmation is required by admin, unless --no-interaction
		 */
		$updated = false;
		if (!$input->hasParameterOption('--value')) {
			if (!$input->getOption('lazy') && $this->appConfig->isLazy($appName, $configName) && $this->ask($input, $output, 'NOT LAZY')) {
				$updated = $this->appConfig->updateLazy($appName, $configName, false);
			}
			if ($input->getOption('lazy') && !$this->appConfig->isLazy($appName, $configName) && $this->ask($input, $output, 'LAZY')) {
				$updated = $this->appConfig->updateLazy($appName, $configName, true) || $updated;
			}
			if (!$input->getOption('sensitive') && $this->appConfig->isSensitive($appName, $configName) && $this->ask($input, $output, 'NOT SENSITIVE')) {
				$updated = $this->appConfig->updateSensitive($appName, $configName, false) || $updated;
			}
			if ($input->getOption('sensitive') && !$this->appConfig->isSensitive($appName, $configName) && $this->ask($input, $output, 'SENSITIVE')) {
				$updated = $this->appConfig->updateSensitive($appName, $configName, true) || $updated;
			}
			if ($type !== null && $type !== $this->appConfig->getValueType($appName, $configName) && $typeString !== null && $this->ask($input, $output, $typeString)) {
				$updated = $this->appConfig->updateType($appName, $configName, $type) || $updated;
			}
		} else {
			/**
			 * If --type is specified in the command line, we upgrade the type in database
			 * after a confirmation from admin.
			 * If not we get the type from current stored value or VALUE_MIXED as default.
			 */
			try {
				$currType = $this->appConfig->getValueType($appName, $configName);
				if ($type === null || $typeString === null || $type === $currType || !$this->ask($input, $output, $typeString)) {
					$type = $currType;
				} else {
					$updated = $this->appConfig->updateType($appName, $configName, $type);
				}
			} catch (AppConfigUnknownKeyException) {
				$type = $type ?? IAppConfig::VALUE_MIXED;
			}

			/**
			 * if --lazy/--no-lazy option are set, compare with data stored in database.
			 * If no data in database, or identical, continue.
			 * If different, ask admin for confirmation.
			 */
			$lazy = $input->getOption('lazy');
			try {
				$currLazy = $this->appConfig->isLazy($appName, $configName);
				if ($lazy === null || $lazy === $currLazy || !$this->ask($input, $output, ($lazy) ? 'LAZY' : 'NOT LAZY')) {
					$lazy = $currLazy;
				}
			} catch (AppConfigUnknownKeyException) {
				$lazy = $lazy ?? false;
			}

			/**
			 * same with sensitive status
			 */
			$sensitive = $input->getOption('sensitive');
			try {
				$currSensitive = $this->appConfig->isSensitive($appName, $configName, null);
				if ($sensitive === null || $sensitive === $currSensitive || !$this->ask($input, $output, ($sensitive) ? 'SENSITIVE' : 'NOT SENSITIVE')) {
					$sensitive = $currSensitive;
				}
			} catch (AppConfigUnknownKeyException) {
				$sensitive = $sensitive ?? false;
			}

			$value = (string)$input->getOption('value');
			switch ($type) {
				case IAppConfig::VALUE_MIXED:
					$updated = $this->appConfig->setValueMixed($appName, $configName, $value, $lazy, $sensitive);
					break;

				case IAppConfig::VALUE_STRING:
					$updated = $this->appConfig->setValueString($appName, $configName, $value, $lazy, $sensitive);
					break;

				case IAppConfig::VALUE_INT:
					$updated = $this->appConfig->setValueInt($appName, $configName, $this->configManager->convertToInt($value), $lazy, $sensitive);
					break;

				case IAppConfig::VALUE_FLOAT:
					$updated = $this->appConfig->setValueFloat($appName, $configName, $this->configManager->convertToFloat($value), $lazy, $sensitive);
					break;

				case IAppConfig::VALUE_BOOL:
					$updated = $this->appConfig->setValueBool($appName, $configName, $this->configManager->convertToBool($value), $lazy);
					break;

				case IAppConfig::VALUE_ARRAY:
					$updated = $this->appConfig->setValueArray($appName, $configName, $this->configManager->convertToArray($value), $lazy, $sensitive);
					break;
			}
		}

		if ($updated) {
			$current = $this->appConfig->getDetails($appName, $configName);
			$output->writeln(
				sprintf(
					"<info>Config value '%s' for app '%s' is now set to '%s', stored as %s in %s</info>",
					$configName,
					$appName,
					$current['sensitive'] ? '<sensitive>' : $current['value'],
					$current['typeString'],
					$current['lazy'] ? 'lazy cache' : 'fast cache'
				)
			);
			$keyDetails = $this->appConfig->getKeyDetails($appName, $configName);
			if (($keyDetails['note'] ?? '') !== '') {
				$output->writeln('<comment>Note:</comment> ' . $keyDetails['note']);
			}

		} else {
			$output->writeln('<info>Config value were not updated</info>');
		}

		return 0;
	}

	private function ask(InputInterface $input, OutputInterface $output, string $request): bool {
		/** @var QuestionHelper $helper */
		$helper = $this->getHelper('question');
		if ($input->getOption('no-interaction')) {
			return true;
		}

		$output->writeln(sprintf('You are about to set config value %s as <info>%s</info>',
			'<info>' . $input->getArgument('app') . '</info>/<info>' . $input->getArgument('name') . '</info>',
			strtoupper($request)
		));
		$output->writeln('');
		$output->writeln('<comment>This might break thing, affect performance on your instance or its security!</comment>');

		$result = (strtolower((string)$helper->ask(
			$input,
			$output,
			new Question('<comment>Confirm this action by typing \'yes\'</comment>: '))) === 'yes');

		$output->writeln(($result) ? 'done' : 'cancelled');
		$output->writeln('');

		return $result;
	}
}
