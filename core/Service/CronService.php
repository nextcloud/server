<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Core\Service;

use OC;
use OC\Authentication\LoginCredentials\Store;
use OC\Files\SetupManager;
use OC\Security\CSRF\TokenStorage\SessionStorage;
use OC\Session\CryptoWrapper;
use OC\Session\Memory;
use OC\User\Session;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\ILogger;
use OCP\ISession;
use OCP\ITempManager;
use OCP\Util;
use Psr\Log\LoggerInterface;

class CronService {
	/** * @var ?callable $verboseCallback */
	private $verboseCallback = null;

	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly IConfig $config,
		private readonly IAppManager $appManager,
		private readonly ISession $session,
		private readonly Session $userSession,
		private readonly CryptoWrapper $cryptoWrapper,
		private readonly Store $store,
		private readonly SessionStorage $sessionStorage,
		private readonly ITempManager $tempManager,
		private readonly IAppConfig $appConfig,
		private readonly IJobList $jobList,
		private readonly SetupManager $setupManager,
		private readonly bool $isCLI,
	) {
	}

	/**
	 * @param callable(string):void $callback
	 */
	public function registerVerboseCallback(callable $callback): void {
		$this->verboseCallback = $callback;
	}

	/**
	 * @throws \RuntimeException
	 */
	public function run(?array $jobClasses): void {
		if (Util::needUpgrade()) {
			$this->logger->debug('Update required, skipping cron', ['app' => 'core']);
			return;
		}

		if ($this->config->getSystemValueBool('maintenance', false)) {
			$this->logger->debug('We are in maintenance mode, skipping cron', ['app' => 'core']);
			return;
		}

		// Don't do anything if Nextcloud has not been installed
		if (!$this->config->getSystemValueBool('installed', false)) {
			return;
		}

		// load all apps to get all api routes properly setup
		$this->appManager->loadApps();
		$this->session->close();

		// initialize a dummy memory session
		$session = new Memory();
		$session = $this->cryptoWrapper->wrapSession($session);
		$this->sessionStorage->setSession($session);
		$this->userSession->setSession($session);
		$this->store->setSession($session);

		$this->tempManager->cleanOld();

		// Exit if background jobs are disabled!
		$appMode = $this->appConfig->getValueString('core', 'backgroundjobs_mode', 'ajax');
		if ($appMode === 'none') {
			throw new \RuntimeException('Background Jobs are disabled!');
		}

		if ($this->isCLI) {
			$this->runCli($appMode, $jobClasses);
		} else {
			$this->runWeb($appMode);
		}

		// Log the successful cron execution
		$this->appConfig->setValueInt('core', 'lastcron', time());
	}

	/**
	 * @throws \RuntimeException
	 */
	private function runCli(string $appMode, ?array $jobClasses): void {
		// set to run indefinitely if needed
		if (!str_contains(@ini_get('disable_functions'), 'set_time_limit')) {
			@set_time_limit(0);
		}

		// the cron job must be executed with the right user
		if (!function_exists('posix_getuid')) {
			throw new \RuntimeException('The posix extensions are required - see https://www.php.net/manual/en/book.posix.php');
		}

		$user = posix_getuid();
		$configUser = fileowner(OC::$configDir . 'config.php');
		if ($user !== $configUser) {
			throw new \RuntimeException('Console has to be executed with the user that owns the file config/config.php.' . PHP_EOL . 'Current user id: ' . $user . PHP_EOL . 'Owner id of config.php: ' . $configUser . PHP_EOL);
		}

		// We call Nextcloud from the CLI (aka cron)
		if ($appMode !== 'cron') {
			$this->appConfig->setValueString('core', 'backgroundjobs_mode', 'cron');
		}

		// Low-load hours
		$onlyTimeSensitive = false;
		$startHour = $this->config->getSystemValueInt('maintenance_window_start', 100);
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

		// We only ask for jobs for 14 minutes, because after 5 minutes the next
		// system cron task should spawn and we want to have at most three
		// cron jobs running in parallel.
		$endTime = time() + 14 * 60;

		$executedJobs = [];

		while ($job = $this->jobList->getNext($onlyTimeSensitive, $jobClasses)) {
			if (isset($executedJobs[$job->getId()])) {
				$this->jobList->unlockJob($job);
				break;
			}

			$jobDetails = get_class($job) . ' (id: ' . $job->getId() . ', arguments: ' . json_encode($job->getArgument()) . ')';
			$this->logger->debug('CLI cron call has selected job ' . $jobDetails, ['app' => 'cron']);

			$timeBefore = time();
			$memoryBefore = memory_get_usage();
			$memoryPeakBefore = memory_get_peak_usage();

			$this->verboseOutput('Starting job ' . $jobDetails);

			$job->start($this->jobList);

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
				$this->logger->log(
					$logLevel,
					'Background job ' . $jobDetails . ' ran for ' . $timeSpent . ' seconds',
					['app' => 'cron']
				);
			}

			if ($memoryAfter - $memoryBefore > 50_000_000) {
				$message = 'Used memory grew by more than 50 MB when executing job ' . $jobDetails . ': ' . Util::humanFileSize($memoryAfter) . ' (before: ' . Util::humanFileSize($memoryBefore) . ')';
				$this->logger->warning($message, ['app' => 'cron']);
				$this->verboseOutput($message);
			}
			if ($memoryPeakAfter > 300_000_000 && $memoryPeakBefore <= 300_000_000) {
				$message = 'Cron job used more than 300 MB of ram after executing job ' . $jobDetails . ': ' . Util::humanFileSize($memoryPeakAfter) . ' (before: ' . Util::humanFileSize($memoryPeakBefore) . ')';
				$this->logger->warning($message, ['app' => 'cron']);
				$this->verboseOutput($message);
			}

			// clean up after unclean jobs
			$this->setupManager->tearDown();
			$this->tempManager->clean();

			$this->verboseOutput('Job ' . $jobDetails . ' done in ' . ($timeAfter - $timeBefore) . ' seconds');

			$this->jobList->setLastJob($job);
			$executedJobs[$job->getId()] = true;
			unset($job);

			if ($timeAfter > $endTime) {
				break;
			}
		}
	}

	private function runWeb(string $appMode): void {
		if ($appMode === 'cron') {
			// Cron is cron :-P
			throw new \RuntimeException('Backgroundjobs are using system cron!');
		} else {
			// Work and success :-)
			$job = $this->jobList->getNext();
			if ($job != null) {
				$this->logger->debug('WebCron call has selected job with ID ' . strval($job->getId()), ['app' => 'cron']);
				$job->start($this->jobList);
				$this->jobList->setLastJob($job);
			}
		}
	}

	private function verboseOutput(string $message): void {
		if ($this->verboseCallback !== null) {
			call_user_func($this->verboseCallback, $message);
		}
	}
}
