<?php
/**
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Owen Winkler <a_github@midnightcircus.com>
 * @author Steffen Lindner <mail@steffen-lindner.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OC\Core\Command;

use OC\Console\TimestampFormatter;
use OC\Updater;
use OCP\IConfig;
use OCP\ILogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class Upgrade extends Command {

	const ERROR_SUCCESS = 0;
	const ERROR_NOT_INSTALLED = 1;
	const ERROR_MAINTENANCE_MODE = 2;
	const ERROR_UP_TO_DATE = 3;
	const ERROR_INVALID_ARGUMENTS = 4;
	const ERROR_FAILURE = 5;

	/** @var IConfig */
	private $config;

	/** @var ILogger */
	private $logger;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config, ILogger $logger) {
		parent::__construct();
		$this->config = $config;
		$this->logger = $logger;
	}

	protected function configure() {
		$this
			->setName('upgrade')
			->setDescription('run upgrade routines after installation of a new release. The release has to be installed before.')
			->addOption(
				'--skip-migration-test',
				null,
				InputOption::VALUE_NONE,
				'skips the database schema migration simulation and update directly'
			)
			->addOption(
				'--dry-run',
				null,
				InputOption::VALUE_NONE,
				'only runs the database schema migration simulation, do not actually update'
			)
			->addOption(
				'--no-app-disable',
				null,
				InputOption::VALUE_NONE,
				'skips the disable of third party apps'
			);
	}

	/**
	 * Execute the upgrade command
	 *
	 * @param InputInterface $input input interface
	 * @param OutputInterface $output output interface
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {

		$simulateStepEnabled = true;
		$updateStepEnabled = true;
		$skip3rdPartyAppsDisable = false;

		if ($input->getOption('skip-migration-test')) {
			$simulateStepEnabled = false;
		}
	   	if ($input->getOption('dry-run')) {
			$updateStepEnabled = false;
		}
		if ($input->getOption('no-app-disable')) {
			$skip3rdPartyAppsDisable = true;
		}

		if (!$simulateStepEnabled && !$updateStepEnabled) {
			$output->writeln(
				'<error>Only one of "--skip-migration-test" or "--dry-run" ' .
				'can be specified at a time.</error>'
			);
			return self::ERROR_INVALID_ARGUMENTS;
		}

		if(\OC::checkUpgrade(false)) {
			if (OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
				// Prepend each line with a little timestamp
				$timestampFormatter = new TimestampFormatter($this->config, $output->getFormatter());
				$output->setFormatter($timestampFormatter);
			}

			$self = $this;
			$updater = new Updater(\OC::$server->getHTTPHelper(),
				$this->config,
				$this->logger);

			$updater->setSimulateStepEnabled($simulateStepEnabled);
			$updater->setUpdateStepEnabled($updateStepEnabled);
			$updater->setSkip3rdPartyAppsDisable($skip3rdPartyAppsDisable);

			$updater->listen('\OC\Updater', 'maintenanceEnabled', function () use($output) {
				$output->writeln('<info>Turned on maintenance mode</info>');
			});
			$updater->listen('\OC\Updater', 'maintenanceDisabled', function () use($output) {
				$output->writeln('<info>Turned off maintenance mode</info>');
			});
			$updater->listen('\OC\Updater', 'maintenanceActive', function () use($output) {
				$output->writeln('<info>Maintenance mode is kept active</info>');
			});
			$updater->listen('\OC\Updater', 'updateEnd',
				function ($success) use($output, $updateStepEnabled, $self) {
					$mode = $updateStepEnabled ? 'Update' : 'Update simulation';
					if ($success) {
						$message = "<info>$mode successful</info>";
					} else {
						$message = "<error>$mode failed</error>";
					}
					$output->writeln($message);
				});
			$updater->listen('\OC\Updater', 'dbUpgradeBefore', function () use($output) {
				$output->writeln('<info>Updating database schema</info>');
			});
			$updater->listen('\OC\Updater', 'dbUpgrade', function () use($output) {
				$output->writeln('<info>Updated database</info>');
			});
			$updater->listen('\OC\Updater', 'dbSimulateUpgradeBefore', function () use($output) {
				$output->writeln('<info>Checking whether the database schema can be updated (this can take a long time depending on the database size)</info>');
			});
			$updater->listen('\OC\Updater', 'dbSimulateUpgrade', function () use($output) {
				$output->writeln('<info>Checked database schema update</info>');
			});
			$updater->listen('\OC\Updater', 'incompatibleAppDisabled', function ($app) use($output) {
				$output->writeln('<info>Disabled incompatible app: ' . $app . '</info>');
			});
			$updater->listen('\OC\Updater', 'thirdPartyAppDisabled', function ($app) use ($output) {
				$output->writeln('<info>Disabled 3rd-party app: ' . $app . '</info>');
			});
			$updater->listen('\OC\Updater', 'upgradeAppStoreApp', function ($app) use($output) {
				$output->writeln('<info>Update 3rd-party app: ' . $app . '</info>');
			});
			$updater->listen('\OC\Updater', 'repairWarning', function ($app) use($output) {
				$output->writeln('<error>Repair warning: ' . $app . '</error>');
			});
			$updater->listen('\OC\Updater', 'repairError', function ($app) use($output) {
				$output->writeln('<error>Repair error: ' . $app . '</error>');
			});
			$updater->listen('\OC\Updater', 'appUpgradeCheckBefore', function () use ($output) {
				$output->writeln('<info>Checking updates of apps</info>');
			});
			$updater->listen('\OC\Updater', 'appSimulateUpdate', function ($app) use ($output) {
				$output->writeln("<info>Checking whether the database schema for <$app> can be updated (this can take a long time depending on the database size)</info>");
			});
			$updater->listen('\OC\Updater', 'appUpgradeCheck', function () use ($output) {
				$output->writeln('<info>Checked database schema update for apps</info>');
			});
			$updater->listen('\OC\Updater', 'appUpgradeStarted', function ($app, $version) use ($output) {
				$output->writeln("<info>Updating <$app> ...</info>");
			});
			$updater->listen('\OC\Updater', 'appUpgrade', function ($app, $version) use ($output) {
				$output->writeln("<info>Updated <$app> to $version</info>");
			});
			$updater->listen('\OC\Updater', 'failure', function ($message) use($output, $self) {
				$output->writeln("<error>$message</error>");
			});
			$updater->listen('\OC\Updater', 'setDebugLogLevel', function ($logLevel, $logLevelName) use($output) {
				$output->writeln("<info>Set log level to debug - current level: '$logLevelName'</info>");
			});
			$updater->listen('\OC\Updater', 'resetLogLevel', function ($logLevel, $logLevelName) use($output) {
				$output->writeln("<info>Reset log level to '$logLevelName'</info>");
			});

			if(OutputInterface::VERBOSITY_NORMAL < $output->getVerbosity()) {
				$updater->listen('\OC\Updater', 'repairInfo', function ($message) use($output) {
					$output->writeln('<info>Repair info: ' . $message . '</info>');
				});
				$updater->listen('\OC\Updater', 'repairStep', function ($message) use($output) {
					$output->writeln('<info>Repair step: ' . $message . '</info>');
				});
			}

			$success = $updater->upgrade();

			$this->postUpgradeCheck($input, $output);

			if(!$success) {
				return self::ERROR_FAILURE;
			}

			return self::ERROR_SUCCESS;
		} else if($this->config->getSystemValue('maintenance', false)) {
			//Possible scenario: ownCloud core is updated but an app failed
			$output->writeln('<warning>ownCloud is in maintenance mode</warning>');
			$output->write('<comment>Maybe an upgrade is already in process. Please check the '
				. 'logfile (data/owncloud.log). If you want to re-run the '
				. 'upgrade procedure, remove the "maintenance mode" from '
				. 'config.php and call this script again.</comment>'
				, true);
			return self::ERROR_MAINTENANCE_MODE;
		} else {
			$output->writeln('<info>ownCloud is already latest version</info>');
			return self::ERROR_UP_TO_DATE;
		}
	}

	/**
	 * Perform a post upgrade check (specific to the command line tool)
	 *
	 * @param InputInterface $input input interface
	 * @param OutputInterface $output output interface
	 */
	protected function postUpgradeCheck(InputInterface $input, OutputInterface $output) {
		$trustedDomains = $this->config->getSystemValue('trusted_domains', array());
		if (empty($trustedDomains)) {
			$output->write(
				'<warning>The setting "trusted_domains" could not be ' .
				'set automatically by the upgrade script, ' .
				'please set it manually</warning>'
			);
		}
	}
}
