<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Maxence Lange <maxence@artificial-owl.com>
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
	 * @param InputInterface  $input  An InputInterface instance
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
