<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OC\Core\Command\Config;

use OC\Core\Command\Base;
use OC\SystemConfig;
use OCP\IAppConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListConfigs extends Base {
	/** @var array */
	protected $sensitiveValues = [
		'dbpassword',
		'dbuser',
		'mail_smtpname',
		'mail_smtppassword',
		'passwordsalt',
		'secret',
	];

	/** * @var SystemConfig */
	protected $systemConfig;

	/** @var IAppConfig */
	protected $appConfig;

	/**
	 * @param SystemConfig $systemConfig
	 * @param IAppConfig $appConfig
	 */
	public function __construct(SystemConfig $systemConfig, IAppConfig $appConfig) {
		parent::__construct();
		$this->systemConfig = $systemConfig;
		$this->appConfig = $appConfig;
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
				'public',
				null,
				InputOption::VALUE_NONE,
				'Use this option when you want to exclude sensitive configs like passwords, salts, ...'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$app = $input->getArgument('app');
		$noSensitiveValues = $input->getOption('public');

		if ($noSensitiveValues && !$input->hasParameterOption('--output')) {
			// If you post this publicly we prefer the json format
			$input->setOption('output', 'json_pretty');
		}

		switch ($app) {
			case 'system':
				$configs = $this->getSystemConfigs($noSensitiveValues);
			break;

			case 'all':
				$apps = $this->appConfig->getApps();
				$configs = [];
				foreach ($apps as $appName) {
					$configs[$appName] = $this->appConfig->getValues($appName, false);
				}
				$configs['system'] = $this->getSystemConfigs($noSensitiveValues);
			break;

			default:
				$configs = $this->appConfig->getValues($app, false);
		}

		$this->writeArrayInOutputFormat($input, $output, $configs);
	}

	/**
	 * Get the system configs
	 *
	 * @param bool $noSensitiveValues
	 * @return array
	 */
	protected function getSystemConfigs($noSensitiveValues) {
		$keys = $this->systemConfig->getKeys();

		$configs = [];
		foreach ($keys as $key) {
			if ($noSensitiveValues && in_array($key, $this->sensitiveValues)) {
				continue;
			}

			$value = $this->systemConfig->getValue($key, serialize(null));
			if ($value !== 'N;') {
				$configs[$key] = $value;
			}
		}

		return $configs;
	}
}
