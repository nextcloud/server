<?php
/**
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christian Kampka <christian@kampka.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
namespace OC\Core\Command\Maintenance;

use InvalidArgumentException;
use OC\Setup;
use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Install extends Command {

	/**
	 * @var IConfig
	 */
	private $config;

	public function __construct(IConfig $config) {
		parent::__construct();
		$this->config = $config;
	}

	protected function configure() {
		$this
			->setName('maintenance:install')
			->setDescription('install ownCloud')
			->addOption('database', null, InputOption::VALUE_REQUIRED, 'Supported database type', 'sqlite')
			->addOption('database-name', null, InputOption::VALUE_REQUIRED, 'Name of the database')
			->addOption('database-host', null, InputOption::VALUE_REQUIRED, 'Hostname of the database', 'localhost')
			->addOption('database-user', null, InputOption::VALUE_REQUIRED, 'User name to connect to the database')
			->addOption('database-pass', null, InputOption::VALUE_OPTIONAL, 'Password of the database user', null)
			->addOption('database-table-prefix', null, InputOption::VALUE_OPTIONAL, 'Prefix for all tables (default: oc_)', null)
			->addOption('admin-user', null, InputOption::VALUE_REQUIRED, 'User name of the admin account', 'admin')
			->addOption('admin-pass', null, InputOption::VALUE_REQUIRED, 'Password of the admin account')
			->addOption('data-dir', null, InputOption::VALUE_REQUIRED, 'Path to data directory', \OC::$SERVERROOT."/data");
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		// validate the environment
		$server = \OC::$server;
		$setupHelper = new Setup($this->config, $server->getIniWrapper(),
			$server->getL10N('lib'), new \OC_Defaults(), $server->getLogger(),
			$server->getSecureRandom());
		$sysInfo = $setupHelper->getSystemInfo(true);
		$errors = $sysInfo['errors'];
		if (count($errors) > 0) {
			$this->printErrors($output, $errors);

			// ignore the OS X setup warning
			if(count($errors) !== 1 ||
				(string)($errors[0]['error']) !== 'Mac OS X is not supported and ownCloud will not work properly on this platform. Use it at your own risk! ') {
				return 1;
			}
		}

		// validate user input
		$options = $this->validateInput($input, $output, array_keys($sysInfo['databases']));

		// perform installation
		$errors = $setupHelper->install($options);
		if (count($errors) > 0) {
			$this->printErrors($output, $errors);
			return 1;
		}
		$output->writeln("ownCloud was successfully installed");
		return 0;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param string[] $supportedDatabases
	 * @return array
	 */
	protected function validateInput(InputInterface $input, OutputInterface $output, $supportedDatabases) {
		$db = strtolower($input->getOption('database'));

		if (!in_array($db, $supportedDatabases)) {
			throw new InvalidArgumentException("Database <$db> is not supported.");
		}

		$dbUser = $input->getOption('database-user');
		$dbPass = $input->getOption('database-pass');
		$dbName = $input->getOption('database-name');
		if ($db === 'oci') {
			// an empty hostname needs to be read from the raw parameters
			$dbHost = $input->getParameterOption('--database-host', '');
		} else {
			$dbHost = $input->getOption('database-host');
		}
		$dbTablePrefix = 'oc_';
		if ($input->hasParameterOption('--database-table-prefix')) {
			$dbTablePrefix = (string) $input->getOption('database-table-prefix');
			$dbTablePrefix = trim($dbTablePrefix);
		}
		if ($input->hasParameterOption('--database-pass')) {
			$dbPass = (string) $input->getOption('database-pass');
		}
		$adminLogin = $input->getOption('admin-user');
		$adminPassword = $input->getOption('admin-pass');
		$dataDir = $input->getOption('data-dir');

		if ($db !== 'sqlite') {
			if (is_null($dbUser)) {
				throw new InvalidArgumentException("Database user not provided.");
			}
			if (is_null($dbName)) {
				throw new InvalidArgumentException("Database name not provided.");
			}
			if (is_null($dbPass)) {
				/** @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
				$dialog = $this->getHelperSet()->get('dialog');
				$dbPass = $dialog->askHiddenResponse(
					$output,
					"<question>What is the password to access the database with user <$dbUser>?</question>",
					false
				);
			}
		}

		if (is_null($adminPassword)) {
			/** @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
			$dialog = $this->getHelperSet()->get('dialog');
			$adminPassword = $dialog->askHiddenResponse(
				$output,
				"<question>What is the password you like to use for the admin account <$adminLogin>?</question>",
				false
			);
		}

		$options = [
			'dbtype' => $db,
			'dbuser' => $dbUser,
			'dbpass' => $dbPass,
			'dbname' => $dbName,
			'dbhost' => $dbHost,
			'dbtableprefix' => $dbTablePrefix,
			'adminlogin' => $adminLogin,
			'adminpass' => $adminPassword,
			'directory' => $dataDir
		];
		return $options;
	}

	/**
	 * @param OutputInterface $output
	 * @param $errors
	 */
	protected function printErrors(OutputInterface $output, $errors) {
		foreach ($errors as $error) {
			if (is_array($error)) {
				$output->writeln('<error>' . (string)$error['error'] . '</error>');
				$output->writeln('<info> -> ' . (string)$error['hint'] . '</info>');
			} else {
				$output->writeln('<error>' . (string)$error . '</error>');
			}
		}
	}
}
