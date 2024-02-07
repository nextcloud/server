<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Artem Sidorenko <artem@posteo.de>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author hoellen <dev@hoellen.eu>
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Ko- <k.stoffelen@cs.ru.nl>
 * @author Michael Kuhn <michael@ikkoku.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Oliver Kohl D.Sc. <oliver@kohl.bz>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Steffen Lindner <mail@steffen-lindner.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 * @author Stephen Michel <git@smichel.me>
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
require_once __DIR__ . '/lib/versioncheck.php';

try {
	require_once __DIR__ . '/lib/base.php';

	if (\OCP\Util::needUpgrade()) {
		\OC::$server->getLogger()->debug('Update required, skipping cron', ['app' => 'cron']);
		exit;
	}
	if ((bool) \OC::$server->getSystemConfig()->getValue('maintenance', false)) {
		\OC::$server->getLogger()->debug('We are in maintenance mode, skipping cron', ['app' => 'cron']);
		exit;
	}

	// load all apps to get all api routes properly setup
	OC_App::loadApps();

	\OC::$server->getSession()->close();

	// initialize a dummy memory session
	$session = new \OC\Session\Memory('');
	$cryptoWrapper = \OC::$server->getSessionCryptoWrapper();
	$session = $cryptoWrapper->wrapSession($session);
	\OC::$server->setSession($session);

	$logger = \OC::$server->getLogger();
	$config = \OC::$server->getConfig();
	$tempManager = \OC::$server->getTempManager();

	// Don't do anything if Nextcloud has not been installed
	if (!$config->getSystemValue('installed', false)) {
		exit(0);
	}

	$tempManager->cleanOld();

	// Exit if background jobs are disabled!
	$appMode = $config->getAppValue('core', 'backgroundjobs_mode', 'ajax');
	if ($appMode === 'none') {
		if (OC::$CLI) {
			echo 'Background Jobs are disabled!' . PHP_EOL;
		} else {
			OC_JSON::error(['data' => ['message' => 'Background jobs disabled!']]);
		}
		exit(1);
	}

	if (OC::$CLI) {
		// set to run indefinitely if needed
		if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
			@set_time_limit(0);
		}

		// the cron job must be executed with the right user
		if (!function_exists('posix_getuid')) {
			echo "The posix extensions are required - see https://www.php.net/manual/en/book.posix.php" . PHP_EOL;
			exit(1);
		}

		$user = posix_getuid();
		$configUser = fileowner(OC::$configDir . 'config.php');
		if ($user !== $configUser) {
			echo "Console has to be executed with the user that owns the file config/config.php" . PHP_EOL;
			echo "Current user id: " . $user . PHP_EOL;
			echo "Owner id of config.php: " . $configUser . PHP_EOL;
			exit(1);
		}


		// We call Nextcloud from the CLI (aka cron)
		if ($appMode !== 'cron') {
			$config->setAppValue('core', 'backgroundjobs_mode', 'cron');
		}

		// Low-load hours
		$onlyTimeSensitive = false;
		$startHour = $config->getSystemValueInt('maintenance_window_start', 100);
		if ($startHour <= 23) {
			$date = new \DateTime('now', new \DateTimeZone('UTC'));
			$currentHour = (int) $date->format('G');
			$endHour = $startHour + 4;

			if ($startHour <= 20) {
				// Start time: 01:00
				// End time: 05:00
				// Only run sensitive tasks when it's before the start or after the end
				$onlyTimeSensitive = $currentHour < $startHour || $currentHour > $endHour;
			} else {
				// Start time: 23:00
				// End time: 03:00
				$endHour -= 24; // Correct the end time from 27:00 to 03:00
				// Only run sensitive tasks when it's after the end and before the start
				$onlyTimeSensitive = $currentHour > $endHour && $currentHour < $startHour;
			}
		}

		// Work
		$jobList = \OC::$server->getJobList();

		// We only ask for jobs for 14 minutes, because after 5 minutes the next
		// system cron task should spawn and we want to have at most three
		// cron jobs running in parallel.
		$endTime = time() + 14 * 60;

		$executedJobs = [];
		while ($job = $jobList->getNext($onlyTimeSensitive)) {
			if (isset($executedJobs[$job->getId()])) {
				$jobList->unlockJob($job);
				break;
			}

			$jobDetails = get_class($job) . ' (id: ' . $job->getId() . ', arguments: ' . json_encode($job->getArgument()) . ')';
			$logger->debug('CLI cron call has selected job ' . $jobDetails, ['app' => 'cron']);

			$memoryBefore = memory_get_usage();
			$memoryPeakBefore = memory_get_peak_usage();

			$job->execute($jobList, $logger);

			$memoryAfter = memory_get_usage();
			$memoryPeakAfter = memory_get_peak_usage();

			if ($memoryAfter - $memoryBefore > 10_000_000) {
				$logger->warning('Used memory grew by more than 10 MB when executing job ' . $jobDetails . ': ' . \OCP\Util::humanFileSize($memoryAfter). ' (before: ' . \OCP\Util::humanFileSize($memoryBefore) . ')', ['app' => 'cron']);
			}
			if ($memoryPeakAfter > 300_000_000) {
				$logger->warning('Cron job used more than 300 MB of ram after executing job ' . $jobDetails . ': ' . \OCP\Util::humanFileSize($memoryPeakAfter) . ' (before: ' . \OCP\Util::humanFileSize($memoryPeakBefore) . ')', ['app' => 'cron']);
			}

			// clean up after unclean jobs
			\OC_Util::tearDownFS();
			$tempManager->clean();

			$jobList->setLastJob($job);
			$executedJobs[$job->getId()] = true;
			unset($job);

			if (time() > $endTime) {
				break;
			}
		}
	} else {
		// We call cron.php from some website
		if ($appMode === 'cron') {
			// Cron is cron :-P
			OC_JSON::error(['data' => ['message' => 'Backgroundjobs are using system cron!']]);
		} else {
			// Work and success :-)
			$jobList = \OC::$server->getJobList();
			$job = $jobList->getNext();
			if ($job != null) {
				$logger->debug('WebCron call has selected job with ID ' . strval($job->getId()), ['app' => 'cron']);
				$job->execute($jobList, $logger);
				$jobList->setLastJob($job);
			}
			OC_JSON::success();
		}
	}

	// Log the successful cron execution
	$config->setAppValue('core', 'lastcron', time());
	exit();
} catch (Exception $ex) {
	\OC::$server->getLogger()->logException($ex, ['app' => 'cron']);
	echo $ex . PHP_EOL;
	exit(1);
} catch (Error $ex) {
	\OC::$server->getLogger()->logException($ex, ['app' => 'cron']);
	echo $ex . PHP_EOL;
	exit(1);
}
