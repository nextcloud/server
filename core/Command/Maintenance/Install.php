<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Maintenance;

use bantu\IniGetWrapper\IniGetWrapper;
use InvalidArgumentException;
use OC\Console\TimestampFormatter;
use OC\Migration\ConsoleOutput;
use OC\Setup;
use OC\SystemConfig;
use OCP\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Throwable;
use function get_class;

class Install extends Command {
	public function __construct(
		private SystemConfig $config,
		private IniGetWrapper $iniGetWrapper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('maintenance:install')
			->setDescription('install Nextcloud')
			->addOption('database', null, InputOption::VALUE_REQUIRED, 'Supported database type', 'sqlite')
			->addOption('database-name', null, InputOption::VALUE_REQUIRED, 'Name of the database')
			->addOption('database-host', null, InputOption::VALUE_REQUIRED, 'Hostname of the database', 'localhost')
			->addOption('database-port', null, InputOption::VALUE_REQUIRED, 'Port the database is listening on')
			->addOption('database-user', null, InputOption::VALUE_REQUIRED, 'Login to connect to the database')
			->addOption('database-pass', null, InputOption::VALUE_OPTIONAL, 'Password of the database user', null)
			->addOption('database-table-space', null, InputOption::VALUE_OPTIONAL, 'Table space of the database (oci only)', null)
			->addOption('disable-admin-user', null, InputOption::VALUE_NONE, 'Disable the creation of an admin user')
			->addOption('admin-user', null, InputOption::VALUE_REQUIRED, 'Login of the admin account', 'admin')
			->addOption('admin-pass', null, InputOption::VALUE_REQUIRED, 'Password of the admin account')
			->addOption('admin-email', null, InputOption::VALUE_OPTIONAL, 'E-Mail of the admin account')
			->addOption('data-dir', null, InputOption::VALUE_REQUIRED, 'Path to data directory', \OC::$SERVERROOT . '/data');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		// validate the environment
		$setupHelper = Server::get(Setup::class);
		$sysInfo = $setupHelper->getSystemInfo(true);
		$errors = $sysInfo['errors'];
		if (count($errors) > 0) {
			$this->printErrors($output, $errors);

			// ignore the OS X setup warning
			if (count($errors) !== 1 ||
				(string)$errors[0]['error'] !== 'Mac OS X is not supported and Nextcloud will not work properly on this platform. Use it at your own risk!') {
				return 1;
			}
		}

		// validate user input
		$options = $this->validateInput($input, $output, array_keys($sysInfo['databases']));

		if ($output->isVerbose()) {
			// Prepend each line with a little timestamp
			$timestampFormatter = new TimestampFormatter(null, $output->getFormatter());
			$output->setFormatter($timestampFormatter);
			$migrationOutput = new ConsoleOutput($output);
		} else {
			$migrationOutput = null;
		}

		// perform installation
		$errors = $setupHelper->install($options, $migrationOutput);
		if (count($errors) > 0) {
			$this->printErrors($output, $errors);
			return 1;
		}
		if ($setupHelper->shouldRemoveCanInstallFile()) {
			$output->writeln('<warn>Could not remove CAN_INSTALL from the config folder. Please remove this file manually.</warn>');
		}
		$output->writeln('Nextcloud was successfully installed');
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
			throw new InvalidArgumentException("Database <$db> is not supported. " . implode(', ', $supportedDatabases) . ' are supported.');
		}

		$dbUser = $input->getOption('database-user');
		$dbPass = $input->getOption('database-pass');
		$dbName = $input->getOption('database-name');
		$dbPort = $input->getOption('database-port');
		if ($db === 'oci') {
			// an empty hostname needs to be read from the raw parameters
			$dbHost = $input->getParameterOption('--database-host', '');
		} else {
			$dbHost = $input->getOption('database-host');
		}
		if ($dbPort) {
			// Append the port to the host so it is the same as in the config (there is no dbport config)
			$dbHost .= ':' . $dbPort;
		}
		if ($input->hasParameterOption('--database-pass')) {
			$dbPass = (string)$input->getOption('database-pass');
		}
		$disableAdminUser = (bool)$input->getOption('disable-admin-user');
		$adminLogin = $input->getOption('admin-user');
		$adminPassword = $input->getOption('admin-pass');
		$adminEmail = $input->getOption('admin-email');
		$dataDir = $input->getOption('data-dir');

		if ($db !== 'sqlite') {
			if (is_null($dbUser)) {
				throw new InvalidArgumentException('Database account not provided.');
			}
			if (is_null($dbName)) {
				throw new InvalidArgumentException('Database name not provided.');
			}
			if (is_null($dbPass)) {
				/** @var QuestionHelper $helper */
				$helper = $this->getHelper('question');
				$question = new Question('What is the password to access the database with user <' . $dbUser . '>?');
				$question->setHidden(true);
				$question->setHiddenFallback(false);
				$dbPass = $helper->ask($input, $output, $question);
			}
		}

		if (!$disableAdminUser && $adminPassword === null) {
			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');
			$question = new Question('What is the password you like to use for the admin account <' . $adminLogin . '>?');
			$question->setHidden(true);
			$question->setHiddenFallback(false);
			$adminPassword = $helper->ask($input, $output, $question);
		}

		if (!$disableAdminUser && $adminEmail !== null && !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
			throw new InvalidArgumentException('Invalid e-mail-address <' . $adminEmail . '> for <' . $adminLogin . '>.');
		}

		$options = [
			'dbtype' => $db,
			'dbuser' => $dbUser,
			'dbpass' => $dbPass,
			'dbname' => $dbName,
			'dbhost' => $dbHost,
			'admindisable' => $disableAdminUser,
			'adminlogin' => $adminLogin,
			'adminpass' => $adminPassword,
			'adminemail' => $adminEmail,
			'directory' => $dataDir
		];
		if ($db === 'oci') {
			$options['dbtablespace'] = $input->getParameterOption('--database-table-space', '');
		}
		return $options;
	}

	/**
	 * @param OutputInterface $output
	 * @param array<string|array> $errors
	 */
	protected function printErrors(OutputInterface $output, array $errors): void {
		foreach ($errors as $error) {
			if (is_array($error)) {
				$output->writeln('<error>' . $error['error'] . '</error>');
				if (isset($error['hint']) && !empty($error['hint'])) {
					$output->writeln('<info> -> ' . $error['hint'] . '</info>');
				}
				if (isset($error['exception']) && $error['exception'] instanceof Throwable) {
					$this->printThrowable($output, $error['exception']);
				}
			} else {
				$output->writeln('<error>' . $error . '</error>');
			}
		}
	}

	private function printThrowable(OutputInterface $output, Throwable $t): void {
		$output->write('<info>Trace: ' . $t->getTraceAsString() . '</info>');
		$output->writeln('');
		if ($t->getPrevious() !== null) {
			$output->writeln('');
			$output->writeln('<info>Previous: ' . get_class($t->getPrevious()) . ': ' . $t->getPrevious()->getMessage() . '</info>');
			$this->printThrowable($output, $t->getPrevious());
		}
	}
}
