<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 * 
 */

namespace OC\Core\Command\Db;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertFromSqlite extends Command {
	/**
	 * @var \OC\Config $config
	 */
	protected $config;

	/**
	 * @param \OC\Config $config
	 */
	public function __construct($config) {
		$this->config = $config;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('db:convert-from-sqlite')
			->setDescription('Convert the owncloud sqlite database to the newly configured one')
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
				'the password of the database to convert to. Will be asked when not specified'
			)
		;
	}

	private static $type2driver = array(
		'mysql' => 'pdo_mysql',
		'pgsql' => 'pdo_pgsql',
		'oci' => 'oci8',
		'mssql' => 'pdo_sqlsrv',
	);
	protected function execute(InputInterface $input, OutputInterface $output) {
		// connect 'from' database
		$datadir = $this->config->getValue( "datadirectory", \OC::$SERVERROOT.'/data' );
		$name = $this->config->getValue( "dbname", "owncloud" );
		$dbfile = $datadir.'/'.$name.'.db';
		$connectionParams = array(
				'path' => $dbfile,
				'driver' => 'pdo_sqlite',
		);
		$fromDB = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);

		// connect 'to' database
		$type = $input->getArgument('type');
		$username = $input->getArgument('username');
		$hostname = $input->getArgument('hostname');
		$dbname = $input->getArgument('database');

		if ($input->getOption('password')) {
			$password = $input->getOption('password');
		} else {
			// TODO: should be moved to the interact function
			$dialog = $this->getHelperSet()->get('dialog');
			$password = $dialog->askHiddenResponse(
				$output,
				'What is the database password?',
				false
			);
		}
		$connectionParams = array(
				'driver' => self::$type2driver[$type],
				'user' => $username,
				'password' => $password,
				'host' => $hostname,
				'dbname' => $dbname,
		);
		if ($input->getOption('port')) {
			$connectionParams['port'] = $input->getOption('port');
		}
		switch ($type) {
			case 'mysql':
			case 'mssql':
				$connectionParams['charset'] = 'UTF8';
				break;
			case 'oci':
				$connectionParams['charset'] = 'AL32UTF8';
				break;
		}

		$toDB = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);

		// create tables in new database
		$schemaManager = new \OC\DB\MDB2SchemaManager($toDB);
		$schemaManager->createDbFromStructure(\OC::$SERVERROOT.'/db_structure.xml');
		$apps = \OC_App::getEnabledApps();
		foreach($apps as $app) {
			if(file_exists(\OC_App::getAppPath($app).'/appinfo/database.xml')) {
				$schemaManager->createDbFromStructure(\OC_App::getAppPath($app).'/appinfo/database.xml');
			}
		}

		// get tables from 'to' database
		$toTables = $this->getTables($toDB);
		// get tables from 'from' database
		$fromTables = $this->getTables($fromDB);
		// warn/fail if there are more tables in 'from' database
		$tables = array_diff($fromTables, $toTables);
		if (!empty($tables)) {
			$output->writeln('<error>The following tables do NOT exist any more: '.join(', ', $tables).'</error>');
			$dialog = $this->getHelperSet()->get('dialog');
			if (!$dialog->askConfirmation(
				$output,
				'<question>Continue with the convertion?</question>',
				false
			)) {
				return;
			}
		}
		// enable maintenance mode to prevent changes
		$this->config->setValue('maintenance', true);
		try {
			// copy table rows
			$tables = array_intersect($toTables, $fromTables);
			foreach($tables as $table) {
				$output->writeln($table);
				$this->copyTable($fromDB, $toDB, $table, $output);
			}
			// save new database config
			$dbhost = $hostname;
			if ($input->getOption('port')) {
				$dbhost = $hostname.':'.$input->getOption('port');
			}
			$this->config->setValue('dbtype', $type);
			$this->config->setValue('dbname', $dbname);
			$this->config->setValue('dbhost', $dbhost);
			$this->config->setValue('dbuser', $username);
			$this->config->setValue('dbpassword', $password);
		} catch(Exception $e) {
			$this->config->setValue('maintenance', false);
			throw $e;
		}
		$this->config->setValue('maintenance', false);
	}

	private function getTables($db) {
		$schemaManager = $db->getSchemaManager();
		return $schemaManager->listTableNames();
	}

	private function copyTable($fromDB, $toDB, $table, $output) {
		$progress = $this->getHelperSet()->get('progress');
		$query = 'SELECT COUNT(*) FROM '.$table;
		$count = $fromDB->fetchColumn($query);
		$query = 'SELECT * FROM '.$table;
		$statement = $fromDB->executeQuery($query);
		$query = 'DELETE FROM '.$table;
		$toDB->executeUpdate($query);
		$progress->start($output, $count);
		$progress->setRedrawFrequency($count > 100 ? 5 : 1);
		while($row = $statement->fetch()) {
			$progress->advance();
			$data = array();
			foreach ($row as $columnName => $value) {
				$data[$toDB->quoteIdentifier($columnName)] = $value;
			}
			$toDB->insert($table, $data);
		}
		$progress->finish();
	}
}
