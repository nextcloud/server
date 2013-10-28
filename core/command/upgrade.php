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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Upgrade extends Command {
	protected function configure() {
		$this
			->setName('upgrade')
			->setDescription('run upgrade routines')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		global $RUNTIME_NOAPPS;

		$RUNTIME_NOAPPS = true; //no apps, yet

		require_once \OC::$SERVERROOT . '/lib/base.php';

		// Don't do anything if ownCloud has not been installed
		if(!\OC_Config::getValue('installed', false)) {
			echo 'ownCloud has not yet been installed' . PHP_EOL;
			exit(0);
		}

		if(\OC::checkUpgrade(false)) {
			$updater = new Updater();

			$updater->listen('\OC\Updater', 'maintenanceStart', function () {
				echo 'Turned on maintenance mode' . PHP_EOL;
			});
			$updater->listen('\OC\Updater', 'maintenanceEnd', function () {
				echo 'Turned off maintenance mode' . PHP_EOL;
				echo 'Update successful' . PHP_EOL;
			});
			$updater->listen('\OC\Updater', 'dbUpgrade', function () {
				echo 'Updated database' . PHP_EOL;
			});
			$updater->listen('\OC\Updater', 'filecacheStart', function () {
				echo 'Updating filecache, this may take really long...' . PHP_EOL;
			});
			$updater->listen('\OC\Updater', 'filecacheDone', function () {
				echo 'Updated filecache' . PHP_EOL;
			});
			$updater->listen('\OC\Updater', 'filecacheProgress', function ($out) {
				echo '... ' . $out . '% done ...' . PHP_EOL;
			});

			$updater->listen('\OC\Updater', 'failure', function ($message) {
				echo $message . PHP_EOL;
				\OC_Config::setValue('maintenance', false);
			});

			$updater->upgrade();
		} else {
			if(\OC_Config::getValue('maintenance', false)) {
				//Possible scenario: ownCloud core is updated but an app failed
				echo 'ownCloud is in maintenance mode' . PHP_EOL;
				echo 'Maybe an upgrade is already in process. Please check the '
					. 'logfile (data/owncloud.log). If you want to re-run the '
					. 'upgrade procedure, remove the "maintenance mode" from '
					. 'config.php and call this script again.'
					. PHP_EOL;
			} else {
				echo 'ownCloud is already latest version' . PHP_EOL;
			}
		}
	}
}
