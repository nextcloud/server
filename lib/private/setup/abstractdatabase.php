<?php

namespace OC\Setup;

abstract class AbstractDatabase {

	/**
	 * @var \OC_L10N
	 */
	protected $trans;
	protected $dbDefinitionFile;
	protected $dbuser;
	protected $dbpassword;
	protected $dbname;
	protected $dbhost;
	protected $tableprefix;

	public function __construct($trans, $dbDefinitionFile) {
		$this->trans = $trans;
		$this->dbDefinitionFile = $dbDefinitionFile;
	}

	public function validate($config) {
		$errors = array();
		if(empty($config['dbuser'])) {
			$errors[] = $this->trans->t("%s enter the database username.", array($this->dbprettyname));
		}
		if(empty($config['dbname'])) {
			$errors[] = $this->trans->t("%s enter the database name.", array($this->dbprettyname));
		}
		if(substr_count($config['dbname'], '.') >= 1) {
			$errors[] = $this->trans->t("%s you may not use dots in the database name", array($this->dbprettyname));
		}
		return $errors;
	}

	public function initialize($config) {
		$dbUser = $config['dbuser'];
		$dbPass = $config['dbpass'];
		$dbName = $config['dbname'];
		$dbHost = !empty($config['dbhost']) ? $config['dbhost'] : 'localhost';
		$dbTablePrefix = isset($config['dbtableprefix']) ? $config['dbtableprefix'] : 'oc_';

		\OC_Config::setValues([
			'dbname'		=> $dbName,
			'dbhost'		=> $dbHost,
			'dbtableprefix'	=> $dbTablePrefix,
		]);

		$this->dbuser = $dbUser;
		$this->dbpassword = $dbPass;
		$this->dbname = $dbName;
		$this->dbhost = $dbHost;
		$this->tableprefix = $dbTablePrefix;
	}

	abstract public function setupDatabase($userName);
}
