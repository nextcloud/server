<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OC\Console;

use OC_App;
use OC_Defaults;
use OCP\IConfig;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application {
	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$defaults = new OC_Defaults;
		$this->config = $config;
		$this->application = new SymfonyApplication($defaults->getName(), \OC_Util::getVersionString());
	}

	/**
	 * @param OutputInterface $output
	 */
	public function loadCommands(OutputInterface $output) {
		// $application is required to be defined in the register_command scripts
		$application = $this->application;
		require_once \OC::$SERVERROOT . '/core/register_command.php';
		if ($this->config->getSystemValue('installed', false)) {
			if (\OCP\Util::needUpgrade()) {
				$output->writeln("ownCloud or one of the apps require upgrade - only a limited number of commands are available");
				$output->writeln("You may use your browser or the occ upgrade command to do the upgrade");
			} elseif ($this->config->getSystemValue('maintenance', false)) {
				$output->writeln("ownCloud is in maintenance mode - no app have been loaded");
			} else {
				OC_App::loadApps();
				foreach (\OC::$server->getAppManager()->getInstalledApps() as $app) {
					$appPath = \OC_App::getAppPath($app);
					\OC::$loader->addValidRoot($appPath);
					$file = $appPath . '/appinfo/register_command.php';
					if (file_exists($file)) {
						require $file;
					}
				}
			}
		} else {
			$output->writeln("ownCloud is not installed - only a limited number of commands are available");
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
		return $this->application->run($input, $output);
	}
}
