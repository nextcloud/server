<?php

namespace OC\Core\Command\Maintenance;

use InvalidArgumentException;
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
			->addOption('database-pass', null, InputOption::VALUE_REQUIRED, 'Password of the database user')
			->addOption('database-table-prefix', null, InputOption::VALUE_OPTIONAL, 'Prefix for all tables (default: oc_)', null)
			->addOption('admin-user', null, InputOption::VALUE_REQUIRED, 'User name of the admin account', 'admin')
			->addOption('admin-pass', null, InputOption::VALUE_REQUIRED, 'Password of the admin account')
			->addOption('data-dir', null, InputOption::VALUE_REQUIRED, 'Path to data directory', \OC::$SERVERROOT."/data");
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$options = $this->validateInput($input, $output);

		$errors = \OC\Setup::install($options);
		if (count($errors) === 0) {
			$output->writeln("ownCloud was successfully installed");
			return 0;
		}
		foreach($errors as $error) {
			if (is_array($error)) {
				$output->writeln('<error>' . (string)$error['error'] . '</error>');
				$output->writeln('<info> -> ' . (string)$error['hint'] . '</info>');
			} else {
				$output->writeln('<error>' . (string)$error . '</error>');
			}
		}

		return 1;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return array
	 */
	protected function validateInput(InputInterface $input, OutputInterface $output) {
		$db = strtolower($input->getOption('database'));
		$supportedDatabases = $this->config->getSystemValue('supportedDatabases', [
			'sqlite',
			'mysql',
			'pgsql',
			'oci',
			'mssql'
		]);

		if (!in_array($db, $supportedDatabases)) {
			throw new InvalidArgumentException("Database <$db> is not supported.");
		}

		$dbUser = $input->getOption('database-user');
		$dbPass = $input->getOption('database-pass');
		$dbName = $input->getOption('database-name');
		$dbHost = $input->getOption('database-host');
		$dbTablePrefix = 'oc_';
		if ($input->hasParameterOption('--database-table-prefix')) {
			$dbTablePrefix = (string) $input->getOption('database-table-prefix');
			$dbTablePrefix = trim($dbTablePrefix);
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
}
