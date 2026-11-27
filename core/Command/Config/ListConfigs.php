<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Core\Command\Config;

use OC\Config\ConfigManager;
use OC\Core\Command\Base;
use OC\SystemConfig;
use OCP\App\IAppManager;
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
		protected ConfigManager $configManager,
		protected IAppManager $appManager,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure() {
		parent::configure();

		$this
			->setName('config:list')
			->setDescription('List system and app configuration values')
			->addArgument(
				'app',
				InputArgument::OPTIONAL,
				'What to list: an app name, "system" for system configuration values, or "all" for system and all app configs',
				'all'
			)
			->addOption(
				'private',
				null,
				InputOption::VALUE_NONE,
				'Include sensitive configuration values like passwords and secrets'
			)
			->addOption(
				'migrate',
				null,
				InputOption::VALUE_NONE,
				'Migrate config keys using the ConfigLexicon before listing',
			)
			->setHelp(<<<'HELP'
The <info>%command.name%</info> command lists system and app configuration values.

For system, this shows the effective system configuration as loaded by
Nextcloud. It may include values from <info>config.php</info>, additional <info>*.config.php</info>
files, and <info>NC_*</info> environment variables.

Examples:

  <info>php occ config:list</info>
    List all system and app configuration values

  <info>php occ config:list system</info>
    List only system configuration values

  <info>php occ config:list files_sharing</info>
    List configuration values for the files_sharing app

  <info>php occ config:list --private</info>
    List all system and app configuration values, including sensitive values
HELP);
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$app = $input->getArgument('app');
		$noSensitiveValues = !$input->getOption('private');

		if ($input->getOption('migrate')) {
			$this->configManager->migrateConfigLexiconKeys(($app === 'all') ? null : $app);
		}

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
		ksort($configs);

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
			$config = $this->appConfig->getFilteredValues($app);
		} else {
			$config = $this->appConfig->getAllValues($app);
		}
		ksort($config);

		return $config;
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	#[\Override]
	public function completeArgumentValues($argumentName, CompletionContext $context) {
		if ($argumentName === 'app') {
			return array_merge(['all', 'system'], $this->appManager->getAllAppsInAppsFolders());
		}
		return [];
	}
}
