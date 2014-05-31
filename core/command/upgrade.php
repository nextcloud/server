<?php
/**
 * Copyright (c) 2013 Owen Winkler <ringmaster@midnightcircus.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command;

use OC\Updater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Upgrade extends Command {

	const ERROR_SUCCESS = 0;
	const ERROR_NOT_INSTALLED = 1;
	const ERROR_MAINTENANCE_MODE = 2;
	const ERROR_UP_TO_DATE = 3;

	protected function configure() {
		$this
			->setName('upgrade')
			->setDescription('run upgrade routines')
		;
	}

	/**
	 * Execute the upgrade command
	 *
	 * @param InputInterface $input input interface
	 * @param OutputInterface $output output interface
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {

		require_once \OC::$SERVERROOT . '/lib/base.php';

		// Don't do anything if ownCloud has not been installed
		if(!\OC_Config::getValue('installed', false)) {
			$output->writeln('<error>ownCloud has not yet been installed</error>');
			return self::ERROR_NOT_INSTALLED;
		}

		if(\OC::checkUpgrade(false)) {
			$updater = new Updater();

			$updater->listen('\OC\Updater', 'maintenanceStart', function () use($output) {
				$output->writeln('<info>Turned on maintenance mode</info>');
			});
			$updater->listen('\OC\Updater', 'maintenanceEnd', function () use($output) {
				$output->writeln('<info>Turned off maintenance mode</info>');
				$output->writeln('<info>Update successful</info>');
			});
			$updater->listen('\OC\Updater', 'dbUpgrade', function () use($output) {
				$output->writeln('<info>Updated database</info>');
			});
			$updater->listen('\OC\Updater', 'disabledApps', function ($appList) use($output) {
				$output->writeln('<info>Disabled incompatible apps: ' . implode(', ', $appList) . '</info>');
			});

			$updater->listen('\OC\Updater', 'failure', function ($message) use($output) {
				$output->writeln($message);
				\OC_Config::setValue('maintenance', false);
			});

			$updater->upgrade();

			$this->postUpgradeCheck($input, $output);

			return self::ERROR_SUCCESS;
		} else if(\OC_Config::getValue('maintenance', false)) {
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
		$trustedDomains = \OC_Config::getValue('trusted_domains', array());
		if (empty($trustedDomains)) {
			$output->write(
				'<warning>The setting "trusted_domains" could not be ' .
				'set automatically by the upgrade script, ' .
				'please set it manually</warning>'
			);
		}
	}
}
