<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Config\App;

use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetConfig extends Base {
	public function __construct(
		protected IAppConfig $appConfig,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();

		$this
			->setName('config:app:get')
			->setDescription('Get an app config value')
			->addArgument(
				'app',
				InputArgument::REQUIRED,
				'Name of the app'
			)
			->addArgument(
				'name',
				InputArgument::REQUIRED,
				'Name of the config to get'
			)
			->addOption(
				'details',
				null,
				InputOption::VALUE_NONE,
				'returns complete details about the app config value'
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
		$appName = $input->getArgument('app');
		$configName = $input->getArgument('name');
		$defaultValue = $input->getOption('default-value');

		if ($input->getOption('details')) {
			$details = $this->appConfig->getDetails($appName, $configName);
			$details['type'] = $details['typeString'];
			unset($details['typeString']);
			$this->writeArrayInOutputFormat($input, $output, $details);
			return 0;
		}

		try {
			$configValue = $this->appConfig->getDetails($appName, $configName)['value'];
		} catch (AppConfigUnknownKeyException $e) {
			if (!$input->hasParameterOption('--default-value')) {
				return 1;
			}
			$configValue = $defaultValue;
		}

		$this->writeMixedInOutputFormat($input, $output, $configValue);
		return 0;
	}
}
