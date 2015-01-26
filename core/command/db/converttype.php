<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * Copyright (c) 2014 Andreas Fischer <bantu@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 */

namespace OC\Core\Command\Db;

use \OCP\IConfig;
use OC\DB\Connection;
use OC\DB\ConnectionFactory;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertType extends Command {
	/**
	 * @var \OCP\IConfig
	 */
	protected $config;

	/**
	 * @var \OC\DB\ConnectionFactory
	 */
	protected $connectionFactory;

	/**
	 * @param \OCP\IConfig $config
	 * @param \OC\DB\ConnectionFactory $connectionFactory
	 */
	public function __construct(IConfig $config, ConnectionFactory $connectionFactory) {
		$this->config = $config;
		$this->connectionFactory = $connectionFactory;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('db:convert-type')
			->setDescription('Convert the ownCloud database to the newly configured one')
			->addArgument(
				'type',
				InputArgument::REQUIRED,
				'the type of the database to convert to'
			)
			->addArgument(
				'username',
				InputArgument::REQUIRED,
				'the username of the database to convert to'
			)
			->addArgument(
				'hostname',
				InputArgument::REQUIRED,
				'the hostname of the database to convert to'
			)
			->addArgument(
				'database',
				InputArgument::REQUIRED,
				'the name of the database to convert to'
			)
			->addOption(
				'port',
				null,
				InputOption::VALUE_REQUIRED,
				'the port of the database to convert to'
			)
			->addOption(
				'password',
				null,
				InputOption::VALUE_REQUIRED,
				'the password of the database to convert to. Will be asked when not specified. Can also be passed via stdin.'
			)
			->addOption(
				'clear-schema',
				null,
				InputOption::VALUE_NONE,
				'remove all tables from the destination database'
			)
			->addOption(
				'all-apps',
				null,
				InputOption::VALUE_NONE,
				'whether to create schema for all apps instead of only installed apps'
			)
		;
	}

	protected function validateInput(InputInterface $input, OutputInterface $output) {
		$type = $this->connectionFactory->normalizeType($input->getArgument('type'));
		if ($type === 'sqlite3') {
			throw new \InvalidArgumentException(
				'Converting to SQLite (sqlite3) is currently not supported.'
			);
		}
		if ($type === 'mssql') {
			throw new \InvalidArgumentException(
				'Converting to Microsoft SQL Server (mssql) is currently not supported.'
			);
		}
		if ($type === $this->config->getSystemValue('dbtype', '')) {
			throw new \InvalidArgumentException(sprintf(
				'Can not convert from %1$s to %1$s.',
				$type
			));
		}
		if ($type === 'oci' && $input->getOption('clear-schema')) {
			// Doctrine unconditionally tries (at least in version 2.3)
			// to drop sequence triggers when dropping a table, even though
			// such triggers may not exist. This results in errors like
			// "ORA-04080: trigger 'OC_STORAGES_AI_PK' does not exist".
			throw new \InvalidArgumentException(
				'The --clear-schema option is not supported when converting to Oracle (oci).'
			);
		}
	}

	protected function readPassword(InputInterface $input, OutputInterface $output) {
		// Explicitly specified password
		if ($input->getOption('password')) {
			return;
		}

		// Read from stdin. stream_set_blocking is used to prevent blocking
		// when nothing is passed via stdin.
		stream_set_blocking(STDIN, 0);
		$password = file_get_contents('php://stdin');
		stream_set_blocking(STDIN, 1);
		if (trim($password) !== '') {
			$input->setOption('password', $password);
			return;
		}

		// Read password by interacting
		if ($input->isInteractive()) {
			/** @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
			$dialog = $this->getHelperSet()->get('dialog');
			$password = $dialog->askHiddenResponse(
				$output,
				'<question>What is the database password?</question>',
				false
			);
			$input->setOption('password', $password);
			return;
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->validateInput($input, $output);
		$this->readPassword($input, $output);

		$fromDB = \OC_DB::getConnection();
		$toDB = $this->getToDBConnection($input, $output);

		if ($input->getOption('clear-schema')) {
			$this->clearSchema($toDB, $input, $output);
		}

		$this->createSchema($toDB, $input, $output);

		$toTables = $this->getTables($toDB);
		$fromTables = $this->getTables($fromDB);

		// warn/fail if there are more tables in 'from' database
		$extraFromTables = array_diff($fromTables, $toTables);
		if (!empty($extraFromTables)) {
			$output->writeln('<comment>The following tables will not be converted:</comment>');
			$output->writeln($extraFromTables);
			if (!$input->getOption('all-apps')) {
				$output->writeln('<comment>Please note that tables belonging to available but currently not installed apps</comment>');
				$output->writeln('<comment>can be included by specifying the --all-apps option.</comment>');
			}
			/** @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
			$dialog = $this->getHelperSet()->get('dialog');
			if (!$dialog->askConfirmation(
				$output,
				'<question>Continue with the conversion (y/n)? [n] </question>',
				false
			)) {
				return;
			}
		}
		$intersectingTables = array_intersect($toTables, $fromTables);
		$this->convertDB($fromDB, $toDB, $intersectingTables, $input, $output);
	}

	protected function createSchema(Connection $toDB, InputInterface $input, OutputInterface $output) {
		$output->writeln('<info>Creating schema in new database</info>');
		$schemaManager = new \OC\DB\MDB2SchemaManager($toDB);
		$schemaManager->createDbFromStructure(\OC::$SERVERROOT.'/db_structure.xml');
		$apps = $input->getOption('all-apps') ? \OC_App::getAllApps() : \OC_App::getEnabledApps();
		foreach($apps as $app) {
			if (file_exists(\OC_App::getAppPath($app).'/appinfo/database.xml')) {
				$schemaManager->createDbFromStructure(\OC_App::getAppPath($app).'/appinfo/database.xml');
			}
		}
	}

	protected function getToDBConnection(InputInterface $input, OutputInterface $output) {
		$type = $input->getArgument('type');
		$connectionParams = array(
			'host' => $input->getArgument('hostname'),
			'user' => $input->getArgument('username'),
			'password' => $input->getOption('password'),
			'dbname' => $input->getArgument('database'),
			'tablePrefix' => $this->config->getSystemValue('dbtableprefix', 'oc_'),
		);
		if ($input->getOption('port')) {
			$connectionParams['port'] = $input->getOption('port');
		}
		return $this->connectionFactory->getConnection($type, $connectionParams);
	}

	protected function clearSchema(Connection $db, InputInterface $input, OutputInterface $output) {
		$toTables = $this->getTables($db);
		if (!empty($toTables)) {
			$output->writeln('<info>Clearing schema in new database</info>');
		}
		foreach($toTables as $table) {
			$db->getSchemaManager()->dropTable($table);
		}
	}

	protected function getTables(Connection $db) {
		$filterExpression = '/^' . preg_quote($this->config->getSystemValue('dbtableprefix', 'oc_')) . '/';
		$db->getConfiguration()->
			setFilterSchemaAssetsExpression($filterExpression);
		return $db->getSchemaManager()->listTableNames();
	}

	protected function copyTable(Connection $fromDB, Connection $toDB, $table, InputInterface $input, OutputInterface $output) {
		/** @var $progress \Symfony\Component\Console\Helper\ProgressHelper */
		$progress = $this->getHelperSet()->get('progress');
		$query = 'SELECT COUNT(*) FROM '.$table;
		$count = $fromDB->fetchColumn($query);
		$query = 'SELECT * FROM '.$table;
		$statement = $fromDB->executeQuery($query);
		$progress->start($output, $count);
		$progress->setRedrawFrequency($count > 100 ? 5 : 1);
		while($row = $statement->fetch()) {
			$progress->advance();
			if ($input->getArgument('type') === 'oci') {
				$data = $row;
			} else {
				$data = array();
				foreach ($row as $columnName => $value) {
					$data[$toDB->quoteIdentifier($columnName)] = $value;
				}
			}
			$toDB->insert($table, $data);
		}
		$progress->finish();
	}

	protected function convertDB(Connection $fromDB, Connection $toDB, array $tables, InputInterface $input, OutputInterface $output) {
		$this->config->setSystemValue('maintenance', true);
		try {
			// copy table rows
			foreach($tables as $table) {
				$output->writeln($table);
				$this->copyTable($fromDB, $toDB, $table, $input, $output);
			}
			if ($input->getArgument('type') === 'pgsql') {
				$tools = new \OC\DB\PgSqlTools($this->config);
				$tools->resynchronizeDatabaseSequences($toDB);
			}
			// save new database config
			$this->saveDBInfo($input);
		} catch(\Exception $e) {
			$this->config->setSystemValue('maintenance', false);
			throw $e;
		}
		$this->config->setSystemValue('maintenance', false);
	}

	protected function saveDBInfo(InputInterface $input) {
		$type = $input->getArgument('type');
		$username = $input->getArgument('username');
		$dbHost = $input->getArgument('hostname');
		$dbName = $input->getArgument('database');
		$password = $input->getOption('password');
		if ($input->getOption('port')) {
			$dbHost .= ':'.$input->getOption('port');
		}

		$this->config->setSystemValues([
			'dbtype'		=> $type,
			'dbname'		=> $dbName,
			'dbhost'		=> $dbHost,
			'dbuser'		=> $username,
			'dbpassword'	=> $password,
		]);
	}
}
