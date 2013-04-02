<?php

namespace OC\Setup;

abstract class AbstractDatabase {
	protected $trans;
	protected $dbuser;
	protected $dbpassword;
	protected $dbname;
	protected $dbhost;
	protected $tableprefix;

	public function __construct($trans, $config) {
		$this->trans = $trans;
		$this->initialize($config);
	}

	public function initialize($config) {
		$dbuser = $config['dbuser'];
		$dbpass = $config['dbpass'];
		$dbname = $config['dbname'];
		$dbhost = isset($config['dbhost']) ? $config['dbhost'] : ''; // dbhost contents is checked earlier
		$dbtableprefix = isset($config['dbtableprefix']) ? $config['dbtableprefix'] : 'oc_';

		\OC_Config::setValue('dbname', $dbname);
		\OC_Config::setValue('dbhost', $dbhost);
		\OC_Config::setValue('dbtableprefix', $dbtableprefix);

		$this->dbuser = $dbuser;
		$this->dbpassword = $dbpass;
		$this->dbname = $dbname;
		$this->dbhost = $dbhost;
		$this->tableprefix = $tableprefix;
	}
}
