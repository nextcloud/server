<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Console;

use OC_App;
use OCP\Console\ConsoleEvent;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IRequest;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Application {
	/** @var IConfig */
	private $config;
	/** @var EventDispatcherInterface */
	private $dispatcher;
	/** @var IRequest */
	private $request;

	/**
	 * @param IConfig $config
	 * @param EventDispatcherInterface $dispatcher
	 * @param IRequest $request
	 */
	public function __construct(IConfig $config, EventDispatcherInterface $dispatcher, IRequest $request) {
		$defaults = \OC::$server->getThemingDefaults();
		$this->config = $config;
		$this->application = new SymfonyApplication($defaults->getName(), \OC_Util::getVersionString());
		$this->dispatcher = $dispatcher;
		$this->request = $request;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \Exception
	 */
	public function loadCommands(InputInterface $input, OutputInterface $output) {
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
		require_once __DIR__ . '/../../../core/register_command.php';
		if ($this->config->getSystemValue('installed', false)) {
			if (\OCP\Util::needUpgrade()) {
				$output->writeln("Nextcloud or one of the apps require upgrade - only a limited number of commands are available");
				$output->writeln("You may use your browser or the occ upgrade command to do the upgrade");
			} elseif ($this->config->getSystemValue('maintenance', false)) {
				$output->writeln("Nextcloud is in maintenance mode - no app have been loaded");
			} else {
				OC_App::loadApps();
				foreach (\OC::$server->getAppManager()->getInstalledApps() as $app) {
					$appPath = \OC_App::getAppPath($app);
					if($appPath === false) {
						continue;
					}
					\OC_App::registerAutoloading($app, $appPath);
					$file = $appPath . '/appinfo/register_command.php';
					if (file_exists($file)) {
						require $file;
					}
				}
			}
		} else {
			$output->writeln("Nextcloud is not installed - only a limited number of commands are available");
		}
		$input = new ArgvInput();
		if ($input->getFirstArgument() !== 'check') {
			$errors = \OC_Util::checkServer(\OC::$server->getConfig());
			if (!empty($errors)) {
				foreach ($errors as $error) {
					$output->writeln((string)$error['error']);
					$output->writeln((string)$error['hint']);
					$output->writeln('');
				}
				throw new \Exception("Environment not properly prepared.");
			}
		}
	}

	/**
	 * Sets whether to automatically exit after a command execution or not.
	 *
	 * @param bool $boolean Whether to automatically exit after a command execution or not
	 */
	public function setAutoExit($boolean) {
		$this->application->setAutoExit($boolean);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 * @throws \Exception
	 */
	public function run(InputInterface $input = null, OutputInterface $output = null) {
		$this->dispatcher->dispatch(ConsoleEvent::EVENT_RUN, new ConsoleEvent(
			ConsoleEvent::EVENT_RUN,
			$this->request->server['argv']
		));
		return $this->application->run($input, $output);
	}
}
