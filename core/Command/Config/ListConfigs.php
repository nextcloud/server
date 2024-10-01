<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Config;

use OC\Core\Command\Base;
use OC\SystemConfig;
use OCP\IAppConfig;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListConfigs extends Base {
	protected string $defaultOutputFormat = self::OUTPUT_FORMAT_JSON_PRETTY;

	public function __construct(
		protected SystemConfig $systemConfig,
		protected IAppConfig $appConfig,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();

		$this
			->setName('config:list')
			->setDescription('List all configs')
			->addArgument(
				'app',
				InputArgument::OPTIONAL,
				'Name of the app ("system" to get the config.php values, "all" for all apps and system)',
				'all'
			)
			->addOption(
				'private',
				null,
				InputOption::VALUE_NONE,
				'Use this option when you want to include sensitive configs like passwords, salts, ...'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$app = $input->getArgument('app');
		$noSensitiveValues = !$input->getOption('private');

		if (!is_string($app)) {
			$output->writeln('<error>Invalid app value given</error>');
			return 1;
		}

		switch ($app) {
			case 'system':
				$configs = [
					'system' => $this->getSystemConfigs($noSensitiveValues),
				];
				break;

			case 'all':
				$apps = $this->appConfig->getApps();
				$configs = [
					'system' => $this->getSystemConfigs($noSensitiveValues),
					'apps' => [],
				];
				foreach ($apps as $appName) {
					$configs['apps'][$appName] = $this->getAppConfigs($appName, $noSensitiveValues);
				}
				break;

			default:
				$configs = [
					'apps' => [$app => $this->getAppConfigs($app, $noSensitiveValues)],
				];
		}

		$this->writeArrayInOutputFormat($input, $output, $configs);
		return 0;
	}

	/**
	 * Get the system configs
	 *
	 * @param bool $noSensitiveValues
	 * @return array
	 */
	protected function getSystemConfigs(bool $noSensitiveValues): array {
		$keys = $this->systemConfig->getKeys();

		$configs = [];
		foreach ($keys as $key) {
			if ($noSensitiveValues) {
				$value = $this->systemConfig->getFilteredValue($key, serialize(null));
			} else {
				$value = $this->systemConfig->getValue($key, serialize(null));
			}

			if ($value !== 'N;') {
				$configs[$key] = $value;
			}
		}

		return $configs;
	}

	/**
	 * Get the app configs
	 *
	 * @param string $app
	 * @param bool $noSensitiveValues
	 * @return array
	 */
	protected function getAppConfigs(string $app, bool $noSensitiveValues) {
		if ($noSensitiveValues) {
			return $this->appConfig->getFilteredValues($app, false);
		} else {
			return $this->appConfig->getValues($app, false);
		}
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'app') {
			return array_merge(['all', 'system'], \OC_App::getAllApps());
		}
		return [];
	}
}
