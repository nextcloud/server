<?php

/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Console;

use ArgumentCountError;
use OC\MemoryInfo;
use OC\SystemConfig;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\Console\ConsoleEvent;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Server;
use OCP\ServerVersion;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application {
	private SymfonyApplication $application;

	public function __construct(
		ServerVersion $serverVersion,
		private IConfig $config,
		private IEventDispatcher $dispatcher,
		private IRequest $request,
		private LoggerInterface $logger,
		private MemoryInfo $memoryInfo,
		private IAppManager $appManager,
		private Defaults $defaults,
	) {
		$this->application = new SymfonyApplication(
			$defaults->getName(),
			$serverVersion->getVersionString()
		);
	}

	/**
	 * Loads relevant core and, if applicable, app commands.
	 *
	 * @throws \Exception
	 */
	public function loadCommands(
		InputInterface $input,
		ConsoleOutputInterface $output,
	): void {
		$this->checkEnvironmentEssentials($input, $output);

		// Variables utilized by downstream `require_once */register_command.php` files (i.e. core)
		$application = $this->application;
		// State flags used to determine which commands to load, checks to do, and warnings/errors to show.
		$installed = (bool) $this->config->getSystemValueBool('installed', false);
		$maintenance = (bool) ($installed && $this->config->getSystemValueBool('maintenance', false));
		$needUpgrade = (bool) ($installed && \OCP\Util::needUpgrade());
		/**
		 * @var bool $debug Used by core/register_command.php (required file reads this local)
		 * @psalm-suppress UnusedVariable
		 * @noinspection PhpUnusedLocalVariableInspection
		 */ 
		$debug = (bool) $this->config->getSystemValueBool('debug', false);

		// Add and handle `--no-warnings` by default regardless of command
		$inputDefinition = $application->getDefinition();
		$inputDefinition->addOption(
			new InputOption(
				'no-warnings',
				null,
				InputOption::VALUE_NONE,
				'Skip global warnings, show command output only',
				null
			)
		);
		// Note: environment errors (above) are still shown
		if ($input->hasParameterOption('--no-warnings', true)) {
			$output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
		}

		$this->writeMemoryCheckInfoIfLow($input, $output);

		// Load core commands
		/** @var \Symfony\Component\Console\Application $application */
		require_once __DIR__ . '/../../../core/register_command.php';

		if ($needUpgrade) {
			$this->writeNeedsUpdateInfo($input, $output);
		} elseif (!$installed) {
			$this->writeNotInstalledInfo($input, $output);
		} elseif ($maintenance) {
			$this->writeMaintenanceModeInfo($input, $output);
		} else {
			// Normal installed path
			$this->loadAppCommands($input, $output);
		}
	}

	/**
	 * Essential environment checks that must pass except in rare cases.
	 *
	 * @throws \Exception if checks fail and command isn't whitelisted.
	 */
	private function checkEnvironmentEssentials(InputInterface $input, ConsoleOutputInterface $output): void {
		$cmd = (string)$input->getFirstArgument();
		$errors = \OC_Util::checkServer(Server::get(SystemConfig::class));
		if (!empty($errors)) {
			foreach ($errors as $error) {
				$errorOutput = $output->getErrorOutput();
				$errorOutput->writeln('<error>' . (string)$error['error'] . '</error>');
				$errorOutput->writeln('<comment>' . (string)$error['hint'] . '</comment>');
				$errorOutput->writeln('');
			}

			// Command exceptions we let proceed even if environment fails essential checks
			// Note: For (conservative) backwards compatibility; other than 'check' most of these are likely unnecessary...
			// ...they can't be used to fix these types of errors anyhow!
			//
			// TODO: Remove all but 'check'.
			$whitelist = [ 'check', 'upgrade', 'maintenance:mode', 'status', '_completion' ];
			if (!in_array($cmd, $whitelist, true)) {
				throw new \Exception(
					'Environment not properly prepared for Nextcloud. Errors should be fixed before proceeding. ' .
					'Please refer to the Admin Manual to correct the above error(s) then retry your "occ" command.');
			}
		}
	}
	
	private function writeMemoryCheckInfoIfLow(InputInterface $input, ConsoleOutputInterface $output): void {
		if ($this->memoryInfo->isMemoryLimitSufficient() === false) {
			$currentLimit = trim((string)ini_get('memory_limit')); // we want the nearly raw ini value to show bogus values too
			$recommendedLimit = '512M';
			$errorOutput = $output->getErrorOutput();
			$errorOutput->writeln(
				'<comment>The PHP-CLI memory limit (' . $currentLimit . ') is below the recommended ' . $recommendedLimit .
				'. Some functions may fail.</comment>');
			$errorOutput->writeln(
				'<comment>Please adjust your "memory_limit" setting in the relevant php.ini file to at least ' .
				$recommendedLimit . ' to prevent potential errors.</comment>');
		}
	}

	/**
	 * @throws \Throwable if unable to load commands specified in an app's info.xml
	 */
	private function loadAppCommands(InputInterface $input, ConsoleOutputInterface $output): void {
		$this->appManager->loadApps();

		foreach ($this->appManager->getEnabledApps() as $app) {
			try {
				$appPath = $this->appManager->getAppPath($app);
			} catch (AppPathNotFoundException) {
				continue;
			}

			// load commands using info.xml
			$info = $this->appManager->getAppInfo($app);
			if (isset($info['commands'])) {
				try {
					$this->loadCommandsFromInfoXml($info['commands']);
				} catch (\Throwable $e) {
					$errorOutput = $output->getErrorOutput();
					$errorOutput->writeln('<error>' . $e->getMessage() . '</error>');
					$this->logger->error($e->getMessage(), [ 'exception' => $e, ]);
				}
			}

			// load from app's register_command.php if present
			\OC_App::registerAutoloading($app, $appPath);
			$file = $appPath . '/appinfo/register_command.php';
			if (file_exists($file)) {
				try {
					require $file;
				} catch (\Throwable $e) {
					$errorOutput = $output->getErrorOutput();
					$errorOutput->writeln('<error>' . $e->getMessage() . '</error>');
					$this->logger->error($e->getMessage(), [ 'exception' => $e, ]);
				}
			}
		}
	}
	
	private function writeNeedsUpdateInfo(InputInterface $input, ConsoleOutputInterface $output): void {
		$cmd = (string)$input->getFirstArgument();
		if ($cmd !== '_completion' && $cmd !== 'upgrade') {
			$errorOutput = $output->getErrorOutput();
			$errorOutput->writeln('<error>Nextcloud or one of its apps requires an upgrade. Only a limited number of commands are available.</error>');
			$errorOutput->writeln('<comment>Please use your browser or the "occ upgrade" command to finish the upgrade.</comment>');
		}
	}

	private function writeNotInstalledInfo(InputInterface $input, ConsoleOutputInterface $output): void {
		$cmd = (string)$input->getFirstArgument();
		if ($cmd !== '_completion' && $cmd !== 'maintenance:install') {
			$errorOutput = $output->getErrorOutput();
			$errorOutput->writeln('<error>Nextcloud is not installed. Only a limited number of commands are available.</error>');
			$errorOutput->writeln('<comment>Please use your browser or the "occ maintenance:install" command to proceed with installation.</comment>');
		}
	}

	private function writeMaintenanceModeInfo(InputInterface $input, ConsoleOutputInterface $output): void {
		$cmd = (string)$input->getFirstArgument();
		if ($cmd !== '_completion' && $cmd !== 'maintenance:mode' && $cmd !== 'status') {
			$errorOutput = $output->getErrorOutput();
			$errorOutput->writeln('<error>Nextcloud is in maintenance mode. Only a limited number of commands are available.</error>');
			$errorOutput->writeln('<comment>In maintenance mode logins are blocked and events may not be triggered since apps are not loaded.</comment>');
			$errorOutput->writeln('<comment>Proceed with maintenance activities then use the "occ maintenance:mode --off" to disable maintenance mode.</comment>');
		}
	}

	/**
	 * Sets whether to automatically exit after a command execution or not.
	 *
	 * @param bool $boolean Whether to automatically exit after a command execution or not
	 */
	public function setAutoExit(bool $boolean): void {
		$this->application->setAutoExit($boolean);
	}

	/**
	 * @throws \Exception
	 */
	public function run(?InputInterface $input = null, ?OutputInterface $output = null): int {
		$event = new ConsoleEvent(
			ConsoleEvent::EVENT_RUN,
			$this->request->server['argv']
		);
		$this->dispatcher->dispatchTyped($event);
		$this->dispatcher->dispatch(ConsoleEvent::EVENT_RUN, $event);
		return $this->application->run($input, $output);
	}

	/**
	 * @throws \Exception
	 */
	private function loadCommandsFromInfoXml(iterable $commands): void {
		foreach ($commands as $command) {
			try {
				$c = Server::get($command);
			} catch (ContainerExceptionInterface $e) {
				if (class_exists($command)) {
					try {
						$c = new $command();
					} catch (ArgumentCountError $ace) {
						throw new \Exception("Failed to construct console command '$command': " . $ace->getMessage(), 0, $ace);
					}
				} else {
					throw new \Exception("Console command '$command' is unknown and could not be loaded");
				}
			}

			$this->application->add($c);
		}
	}
}
