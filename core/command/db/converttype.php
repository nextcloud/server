<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 */

namespace OC\Core\Command\Db;

use Doctrine\DBAL\Schema\AbstractSchemaManager;

use OC\Config;
use OC\DB\Connection;
use OC\DB\ConnectionFactory;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertType extends Command {
	/**
	 * @var \OC\Config
	 */
	protected $config;

	/**
	 * @var \OC\DB\ConnectionFactory
	 */
	protected $connectionFactory;

	/**
	 * @param \OC\Config $config
	 * @param \OC\DB\ConnectionFactory $connectionFactory
	 */
	public function __construct(Config $config, ConnectionFactory $connectionFactory) {
		$this->config = $config;
		$this->connectionFactory = $connectionFactory;
		parent::__construct();
	}

	protected function interact(InputInterface $input, OutputInterface $output) {
		parent::interact($input, $output);
		if (!$input->getOption('password')) {
			/** @var $dialog \Symfony\Component\Console\Helper\DialogHelper */
			$dialog = $this->getHelperSet()->get('dialog');
			$password = $dialog->askHiddenResponse(
				$output,
				'<question>What is the database password?</question>',
				false
			);
			$input->setOption('password', $password);
		}
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
				'the password of the database to convert to. Will be asked when not specified'
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

	protected function execute(InputInterface $input, OutputInterface $output) {
		if ($input->getArgument('type') === $this->config->getValue('dbtype', '')) {
			$output->writeln(sprintf(
				'<error>Can not convert from %1$s to %1$s.</error>',
				$input->getArgument('type')
			));
			return 1;
		}

		$fromDB = \OC_DB::getConnection();
		$toDB = $this->getToDBConnection($input, $output);

		if ($input->getOption('clear-schema')) {
			$this->clearSchema($toDB->getSchemaManager(), $input, $output);
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
				'<question>Continue with the conversion?</question>',
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
			'tablePrefix' => $this->config->getValue('dbtableprefix', 'oc_'),
		);
		if ($input->getOption('port')) {
			$connectionParams['port'] = $input->getOption('port');
		}
		return $this->connectionFactory->getConnection($type, $connectionParams);
	}

	protected function clearSchema(AbstractSchemaManager $schemaManager, InputInterface $input, OutputInterface $output) {
		$toTables = $schemaManager->listTableNames();
		if (!empty($toTables)) {
			$output->writeln('<info>Clearing schema in new database</info>');
		}
		foreach($toTables as $table) {
			$schemaManager->dropTable($table);
		}
	}

	protected function getTables(Connection $db) {
		$schemaManager = $db->getSchemaManager();
		return $schemaManager->listTableNames();
	}

	protected function copyTable(Connection $fromDB, Connection $toDB, $table, OutputInterface $output) {
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
			$data = array();
			foreach ($row as $columnName => $value) {
				$data[$toDB->quoteIdentifier($columnName)] = $value;
			}
			$toDB->insert($table, $data);
		}
		$progress->finish();
	}

	protected function convertDB(Connection $fromDB, Connection $toDB, array $tables, InputInterface $input, OutputInterface $output) {
		$this->config->setValue('maintenance', true);
		$type = $input->getArgument('type');
		try {
			// copy table rows
			foreach($tables as $table) {
				$output->writeln($table);
				$this->copyTable($fromDB, $toDB, $table, $output);
			}
			if ($type == 'pgsql') {
				$sequences = $toDB->getSchemaManager()->listSequences();
				$dbname = $input->getArgument('database');
				foreach($sequences as $sequence) {
					$info = $toDB->fetchAssoc('SELECT table_schema, table_name, column_name '
						.'FROM information_schema.columns '
						.'WHERE column_default = ? AND table_catalog = ?',
							array("nextval('".$sequence->getName()."'::regclass)", $dbname));
					$table_name = $info['table_name'];
					$column_name = $info['column_name'];
					$toDB->executeQuery("SELECT setval('" . $sequence->getName() . "', (SELECT MAX(" . $column_name . ") FROM " . $table_name . "))");
				}
			}
			// save new database config
			$this->saveDBInfo($input);
		} catch(\Exception $e) {
			$this->config->setValue('maintenance', false);
			throw $e;
		}
		$this->config->setValue('maintenance', false);
	}

	protected function saveDBInfo(InputInterface $input) {
		$type = $input->getArgument('type');
		$username = $input->getArgument('username');
		$dbhost = $input->getArgument('hostname');
		$dbname = $input->getArgument('database');
		$password = $input->getOption('password');
		if ($input->getOption('port')) {
			$dbhost .= ':'.$input->getOption('port');
		}

		$this->config->setValue('dbtype', $type);
		$this->config->setValue('dbname', $dbname);
		$this->config->setValue('dbhost', $dbhost);
		$this->config->setValue('dbuser', $username);
		$this->config->setValue('dbpassword', $password);
	}
}
