<?php
/**
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Oliver Kohl D.Sc. <oliver@kohl.bz>
 * @author Robin Appelman <icewind@owncloud.com>
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

try {

	require_once 'lib/base.php';

	if (\OCP\Util::needUpgrade()) {
		\OCP\Util::writeLog('cron', 'Update required, skipping cron', \OCP\Util::DEBUG);
		exit;
	}
	if (\OC::$server->getSystemConfig()->getValue('maintenance', false)) {
		\OCP\Util::writeLog('cron', 'We are in maintenance mode, skipping cron', \OCP\Util::DEBUG);
		exit;
	}

	if (\OC::$server->getSystemConfig()->getValue('singleuser', false)) {
		\OCP\Util::writeLog('cron', 'We are in admin only mode, skipping cron', \OCP\Util::DEBUG);
		exit;
	}

	// load all apps to get all api routes properly setup
	OC_App::loadApps();

	\OC::$server->getSession()->close();

	// initialize a dummy memory session
	\OC::$server->setSession(new \OC\Session\Memory(''));

	$logger = \OC_Log::$object;

	// Don't do anything if ownCloud has not been installed
	if (!OC_Config::getValue('installed', false)) {
		exit(0);
	}

	\OC::$server->getTempManager()->cleanOld();

	// Exit if background jobs are disabled!
	$appMode = \OCP\BackgroundJob::getExecutionType();
	if ($appMode == 'none') {
		if (OC::$CLI) {
			echo 'Background Jobs are disabled!' . PHP_EOL;
		} else {
			OC_JSON::error(array('data' => array('message' => 'Background jobs disabled!')));
		}
		exit(1);
	}

	if (OC::$CLI) {
		// set to run indefinitely if needed
		set_time_limit(0);

		// the cron job must be executed with the right user
		if (!OC_Util::runningOnWindows())  {
			if (!function_exists('posix_getuid')) {
				echo "The posix extensions are required - see http://php.net/manual/en/book.posix.php" . PHP_EOL;
				exit(0);
			}
			$user = posix_getpwuid(posix_getuid());
			$configUser = posix_getpwuid(fileowner(OC::$SERVERROOT . '/config/config.php'));
			if ($user['name'] !== $configUser['name']) {
				echo "Console has to be executed with the same user as the web server is operated" . PHP_EOL;
				echo "Current user: " . $user['name'] . PHP_EOL;
				echo "Web server user: " . $configUser['name'] . PHP_EOL;
				exit(0);
			}
		}

		$config = OC::$server->getConfig();
		$instanceId = $config->getSystemValue('instanceid');
		$lockFileName = 'owncloud-server-' . $instanceId . '-cron.lock';
		$lockDirectory = $config->getSystemValue('cron.lockfile.location', sys_get_temp_dir());
		$lockDirectory = rtrim($lockDirectory, '\\/');
		$lockFile = $lockDirectory . '/' . $lockFileName;

		if (!file_exists($lockFile)) {
			touch($lockFile);
		}

		// We call ownCloud from the CLI (aka cron)
		if ($appMode != 'cron') {
			\OCP\BackgroundJob::setExecutionType('cron');
		}

		// open the file and try to lock if. If it is not locked, the background
		// job can be executed, otherwise another instance is already running
		$fp = fopen($lockFile, 'w');
		$isLocked = flock($fp, LOCK_EX|LOCK_NB, $wouldBlock);

		// check if backgroundjobs is still running. The wouldBlock check is
		// needed on systems with advisory locking, see
		// http://php.net/manual/en/function.flock.php#45464
		if (!$isLocked || $wouldBlock) {
			echo "Another instance of cron.php is still running!" . PHP_EOL;
			exit(1);
		}

		// Work
		$jobList = \OC::$server->getJobList();
		$jobs = $jobList->getAll();
		foreach ($jobs as $job) {
			$job->execute($jobList, $logger);
		}

		// unlock the file
		flock($fp, LOCK_UN);
		fclose($fp);

	} else {
		// We call cron.php from some website
		if ($appMode == 'cron') {
			// Cron is cron :-P
			OC_JSON::error(array('data' => array('message' => 'Backgroundjobs are using system cron!')));
		} else {
			// Work and success :-)
			$jobList = \OC::$server->getJobList();
			$job = $jobList->getNext();
			if ($job != null) {
				$job->execute($jobList, $logger);
				$jobList->setLastJob($job);
			}
			OC_JSON::success();
		}
	}

	// Log the successful cron execution
	if (\OC::$server->getConfig()->getSystemValue('cron_log', true)) {
		\OC::$server->getConfig()->setAppValue('core', 'lastcron', time());
	}
	exit();

} catch (Exception $ex) {
	\OCP\Util::writeLog('cron', $ex->getMessage(), \OCP\Util::FATAL);
}
