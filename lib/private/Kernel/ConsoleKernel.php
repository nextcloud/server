<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Kernel;

use OC\Console\Application;
use OC\SystemConfig;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\Response;
use OCP\Diagnostics\IEventLogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Profiler\IProfiler;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Throwable;

class ConsoleKernel extends Kernel {
	public function __construct() {
		parent::__construct();

		set_exception_handler($this->exceptionHandler(...));

		// set to run indefinitely if needed
		if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
			@set_time_limit(0);
		}

		if (!$this->isCli()) {
			echo 'This script can be run from the command line only' . PHP_EOL;
			exit(1);
		}

		if (!function_exists('posix_getuid')) {
			echo 'The posix extensions are required - see https://www.php.net/manual/en/book.posix.php' . PHP_EOL;
			exit(1);
		}
	}

	public function boot(): self {
		try {
			parent::boot();

			$user = posix_getuid();
			$configUser = fileowner($this->configDir . 'config.php');
			if ($user !== $configUser) {
				echo 'Console has to be executed with the user that owns the file config/config.php' . PHP_EOL;
				echo 'Current user id: ' . $user . PHP_EOL;
				echo 'Owner id of config.php: ' . $configUser . PHP_EOL;
				echo "Try adding 'sudo -u #" . $configUser . "' to the beginning of the command (without the single quotes)" . PHP_EOL;
				echo "If running with 'docker exec' try adding the option '-u " . $configUser . "' to the docker command (without the single quotes)" . PHP_EOL;
				exit(1);
			}

			$oldWorkingDir = getcwd();
			if ($oldWorkingDir === false) {
				echo 'This script can be run from the Nextcloud root directory only.' . PHP_EOL;
				echo "Can't determine current working dir - the script will continue to work but be aware of the above fact." . PHP_EOL;
			} elseif ($oldWorkingDir !== __DIR__ && !chdir(__DIR__)) {
				echo 'This script can be run from the Nextcloud root directory only.' . PHP_EOL;
				echo "Can't change to Nextcloud root directory." . PHP_EOL;
				exit(1);
			}

			return $this;
		} catch (Throwable $e) {
			echo $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
			exit(1);
		}
	}

	public function run(array $argv): void {
		try {
			if (!(function_exists('pcntl_signal') && function_exists('pcntl_signal_dispatch')) && !in_array('--no-warnings', $argv)) {
				echo 'The process control (PCNTL) extensions are required in case you want to interrupt long running commands - see https://www.php.net/manual/en/book.pcntl.php' . PHP_EOL;
				echo "Additionally the function 'pcntl_signal' and 'pcntl_signal_dispatch' need to be enabled in your php.ini." . PHP_EOL;
			}

			$eventLogger = $this->server->get(IEventLogger::class);
			$eventLogger->start('console:build_application', 'Build Application instance and load commands');

			$application = $this->server->get(Application::class);
			/* base.php will have removed eventual debug options from argv in $_SERVER */
			$input = new ArgvInput($argv);
			$output = new ConsoleOutput();
			$application->loadCommands($input, $output);

			$eventLogger->end('console:build_application');
			$eventLogger->start('console:run', 'Run the command');

			$application->setAutoExit(false);
			$exitCode = $application->run($input);

			$eventLogger->end('console:run');

			$profiler = $this->server->get(IProfiler::class);
			if ($profiler->isEnabled()) {
				$eventLogger->end('runtime');
				$profile = $profiler->collect($this->server->get(IRequest::class), new Response());
				$profile->setMethod('occ');
				$profile->setUrl(implode(' ', $argv));
				$profiler->saveProfile($profile);

				if ($this->server->get(IAppManager::class)->isEnabledForAnyone('profiler')) {
					$urlGenerator = $this->server->get(IURLGenerator::class);
					$url = $urlGenerator->linkToRouteAbsolute('profiler.main.profiler', [
						'profiler' => 'db',
						'token' => $profile->getToken(),
					]);
					$output->getErrorOutput()->writeln('Profiler output available at ' . $url);
				}
			}

			if ($exitCode > 255) {
				$exitCode = 255;
			}

			exit($exitCode);
		} catch (Throwable $e) {
			echo $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
			exit(1);
		}
	}

	function exceptionHandler($exception): never {
		echo 'An unhandled exception has been thrown:' . PHP_EOL;
		echo $exception;
		exit(1);
	}

	protected function setupSession(IRequest $request, IEventLogger $eventLogger): void {
	}

	public function checkInstalled(SystemConfig $systemConfig): void {
	}
}
