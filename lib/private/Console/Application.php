<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Console;

use ArgumentCountError;
use OC\MemoryInfo;
use OC\NeedsUpdateException;
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
		$this->application = new SymfonyApplication($defaults->getName(), $serverVersion->getVersionString());
	}

	/**
	 * @throws \Exception
	 */
	public function loadCommands(
		InputInterface $input,
		ConsoleOutputInterface $output,
	): void {
		// $application is required to be defined in the register_command scripts
		$application = $this->application;
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
		try {
			$input->bind($inputDefinition);
		} catch (\RuntimeException $e) {
			//expected if there are extra options
		}
		if ($input->getOption('no-warnings')) {
			$output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
		}

		if ($this->memoryInfo->isMemoryLimitSufficient() === false) {
			$output->getErrorOutput()->writeln(
				'<comment>The current PHP memory limit ' .
				'is below the recommended value of 512MB.</comment>'
			);
		}

		try {
			require_once __DIR__ . '/../../../core/register_command.php';
			if ($this->config->getSystemValueBool('installed', false)) {
				if (\OCP\Util::needUpgrade()) {
					throw new NeedsUpdateException();
				} elseif ($this->config->getSystemValueBool('maintenance')) {
					$this->writeMaintenanceModeInfo($input, $output);
				} else {
					$this->appManager->loadApps();
					foreach ($this->appManager->getInstalledApps() as $app) {
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
								$output->writeln('<error>' . $e->getMessage() . '</error>');
								$this->logger->error($e->getMessage(), [
									'exception' => $e,
								]);
							}
						}
						// load from register_command.php
						\OC_App::registerAutoloading($app, $appPath);
						$file = $appPath . '/appinfo/register_command.php';
						if (file_exists($file)) {
							try {
								require $file;
							} catch (\Exception $e) {
								$this->logger->error($e->getMessage(), [
									'exception' => $e,
								]);
							}
						}
					}
				}
			} elseif ($input->getArgument('command') !== '_completion' && $input->getArgument('command') !== 'maintenance:install') {
				$errorOutput = $output->getErrorOutput();
				$errorOutput->writeln('Nextcloud is not installed - only a limited number of commands are available');
			}
		} catch (NeedsUpdateException) {
			if ($input->getArgument('command') !== '_completion') {
				$errorOutput = $output->getErrorOutput();
				$errorOutput->writeln('Nextcloud or one of the apps require upgrade - only a limited number of commands are available');
				$errorOutput->writeln('You may use your browser or the occ upgrade command to do the upgrade');
			}
		}

		if ($input->getFirstArgument() !== 'check') {
			$errors = \OC_Util::checkServer(Server::get(SystemConfig::class));
			if (!empty($errors)) {
				foreach ($errors as $error) {
					$output->writeln((string)$error['error']);
					$output->writeln((string)$error['hint']);
					$output->writeln('');
				}
				throw new \Exception('Environment not properly prepared.');
			}
		}
	}

	/**
	 * Write a maintenance mode info.
	 * The commands "_completion" and "maintenance:mode" are excluded.
	 *
	 * @param InputInterface $input The input implementation for reading inputs.
	 * @param ConsoleOutputInterface $output The output implementation
	 *                                       for writing outputs.
	 * @return void
	 */
	private function writeMaintenanceModeInfo(InputInterface $input, ConsoleOutputInterface $output): void {
		if ($input->getArgument('command') !== '_completion'
			&& $input->getArgument('command') !== 'maintenance:mode'
			&& $input->getArgument('command') !== 'status') {
			$errOutput = $output->getErrorOutput();
			$errOutput->writeln('<comment>Nextcloud is in maintenance mode, no apps are loaded.</comment>');
			$errOutput->writeln('<comment>Commands provided by apps are unavailable.</comment>');
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
	 * @return int
	 * @throws \Exception
	 */
	public function run(?InputInterface $input = null, ?OutputInterface $output = null) {
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
					} catch (ArgumentCountError) {
						throw new \Exception("Failed to construct console command '$command': " . $e->getMessage(), 0, $e);
					}
				} else {
					throw new \Exception("Console command '$command' is unknown and could not be loaded");
				}
			}

			$this->application->add($c);
		}
	}
}
