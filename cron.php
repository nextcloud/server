<?php

declare(strict_types=1);

use OC\Files\SetupManager;
use OC\Session\CryptoWrapper;
use OC\Session\Memory;
use OCP\ILogger;

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

require_once __DIR__ . '/lib/versioncheck.php';

use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\ISession;
use OCP\ITempManager;
use OCP\Server;
use OCP\Util;
use Psr\Log\LoggerInterface;

try {
	require_once __DIR__ . '/lib/base.php';

	if (isset($argv[1]) && ($argv[1] === '-h' || $argv[1] === '--help')) {
		echo 'Description:
  Run the background job routine

Usage:
  php -f cron.php -- [-h] [--verbose] [<job-classes>...]

Arguments:
  job-classes                  Optional job class list to only run those jobs
                               Providing a class will ignore the time-sensitivity restriction

Options:
  -h, --help                 Display this help message
  -v, --verbose              Output more information' . PHP_EOL;
		exit(0);
	}

	if (Util::needUpgrade()) {
		Server::get(LoggerInterface::class)->debug('Update required, skipping cron', ['app' => 'cron']);
		exit;
	}

	$config = Server::get(IConfig::class);

	if ($config->getSystemValueBool('maintenance', false)) {
		Server::get(LoggerInterface::class)->debug('We are in maintenance mode, skipping cron', ['app' => 'cron']);
		exit;
	}

	// Don't do anything if Nextcloud has not been installed
	if (!$config->getSystemValueBool('installed', false)) {
		exit(0);
	}

	// load all apps to get all api routes properly setup
	Server::get(IAppManager::class)->loadApps();
	Server::get(ISession::class)->close();

	$verbose = isset($argv[1]) && ($argv[1] === '-v' || $argv[1] === '--verbose');

	// initialize a dummy memory session
	$session = new Memory();
	$cryptoWrapper = Server::get(CryptoWrapper::class);
	$session = $cryptoWrapper->wrapSession($session);
	\OC::$server->setSession($session);

	$logger = Server::get(LoggerInterface::class);
	$appConfig = Server::get(IAppConfig::class);
	$tempManager = Server::get(ITempManager::class);

	$tempManager->cleanOld();

	// Exit if background jobs are disabled!
	$appMode = $appConfig->getValueString('core', 'backgroundjobs_mode', 'ajax');
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
			echo 'The posix extensions are required - see https://www.php.net/manual/en/book.posix.php' . PHP_EOL;
			exit(1);
		}

		$user = posix_getuid();
		$configUser = fileowner(OC::$configDir . 'config.php');
		if ($user !== $configUser) {
			echo 'Console has to be executed with the user that owns the file config/config.php' . PHP_EOL;
			echo 'Current user id: ' . $user . PHP_EOL;
			echo 'Owner id of config.php: ' . $configUser . PHP_EOL;
			exit(1);
		}


		// We call Nextcloud from the CLI (aka cron)
		if ($appMode !== 'cron') {
			$appConfig->setValueString('core', 'backgroundjobs_mode', 'cron');
		}

		// a specific job class list can optionally be given as argument
		$jobClasses = array_slice($argv, $verbose ? 2 : 1);
		$jobClasses = empty($jobClasses) ? null : $jobClasses;

		// Low-load hours
		$onlyTimeSensitive = false;
		$startHour = $config->getSystemValueInt('maintenance_window_start', 100);
		if ($jobClasses === null && $startHour <= 23) {
			$date = new \DateTime('now', new \DateTimeZone('UTC'));
			$currentHour = (int)$date->format('G');
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
		$jobList = Server::get(IJobList::class);

		// We only ask for jobs for 14 minutes, because after 5 minutes the next
		// system cron task should spawn and we want to have at most three
		// cron jobs running in parallel.
		$endTime = time() + 14 * 60;

		$executedJobs = [];

		while ($job = $jobList->getNext($onlyTimeSensitive, $jobClasses)) {
			if (isset($executedJobs[$job->getId()])) {
				$jobList->unlockJob($job);
				break;
			}

			$jobDetails = get_class($job) . ' (id: ' . $job->getId() . ', arguments: ' . json_encode($job->getArgument()) . ')';
			$logger->debug('CLI cron call has selected job ' . $jobDetails, ['app' => 'cron']);

			$timeBefore = time();
			$memoryBefore = memory_get_usage();
			$memoryPeakBefore = memory_get_peak_usage();

			if ($verbose) {
				echo 'Starting job ' . $jobDetails . PHP_EOL;
			}

			/** @psalm-suppress DeprecatedMethod Calling execute until it is removed, then will switch to start */
			$job->execute($jobList);

			$timeAfter = time();
			$memoryAfter = memory_get_usage();
			$memoryPeakAfter = memory_get_peak_usage();

			$cronInterval = 5 * 60;
			$timeSpent = $timeAfter - $timeBefore;
			if ($timeSpent > $cronInterval) {
				$logLevel = match (true) {
					$timeSpent > $cronInterval * 128 => ILogger::FATAL,
					$timeSpent > $cronInterval * 64 => ILogger::ERROR,
					$timeSpent > $cronInterval * 16 => ILogger::WARN,
					$timeSpent > $cronInterval * 8 => ILogger::INFO,
					default => ILogger::DEBUG,
				};
				$logger->log(
					$logLevel,
					'Background job ' . $jobDetails . ' ran for ' . $timeSpent . ' seconds',
					['app' => 'cron']
				);
			}

			if ($memoryAfter - $memoryBefore > 50_000_000) {
				$message = 'Used memory grew by more than 50 MB when executing job ' . $jobDetails . ': ' . Util::humanFileSize($memoryAfter) . ' (before: ' . Util::humanFileSize($memoryBefore) . ')';
				$logger->warning($message, ['app' => 'cron']);
				if ($verbose) {
					echo $message . PHP_EOL;
				}
			}
			if ($memoryPeakAfter > 300_000_000 && $memoryPeakBefore <= 300_000_000) {
				$message = 'Cron job used more than 300 MB of ram after executing job ' . $jobDetails . ': ' . Util::humanFileSize($memoryPeakAfter) . ' (before: ' . Util::humanFileSize($memoryPeakBefore) . ')';
				$logger->warning($message, ['app' => 'cron']);
				if ($verbose) {
					echo $message . PHP_EOL;
				}
			}

			// clean up after unclean jobs
			Server::get(SetupManager::class)->tearDown();
			$tempManager->clean();

			if ($verbose) {
				echo 'Job ' . $jobDetails . ' done in ' . ($timeAfter - $timeBefore) . ' seconds' . PHP_EOL;
			}

			$jobList->setLastJob($job);
			$executedJobs[$job->getId()] = true;
			unset($job);

			if ($timeAfter > $endTime) {
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
			$jobList = Server::get(IJobList::class);
			$job = $jobList->getNext();
			if ($job != null) {
				$logger->debug('WebCron call has selected job with ID ' . strval($job->getId()), ['app' => 'cron']);
				/** @psalm-suppress DeprecatedMethod Calling execute until it is removed, then will switch to start */
				$job->execute($jobList);
				$jobList->setLastJob($job);
			}
			OC_JSON::success();
		}
	}

	// Log the successful cron execution
	$appConfig->setValueInt('core', 'lastcron', time());
	exit();
} catch (Exception $ex) {
	Server::get(LoggerInterface::class)->error(
		$ex->getMessage(),
		['app' => 'cron', 'exception' => $ex]
	);
	echo $ex . PHP_EOL;
	exit(1);
} catch (Error $ex) {
	Server::get(LoggerInterface::class)->error(
		$ex->getMessage(),
		['app' => 'cron', 'exception' => $ex]
	);
	echo $ex . PHP_EOL;
	exit(1);
}
