<?php

namespace OC\Setup;

class Sqlite extends AbstractDatabase {
	public $dbprettyname = 'Sqlite';

	public function validate($config) {
		return array();
	}

	public function initialize($config) {
	}

	public function setupDatabase($username) {
		$datadir = \OC_Config::getValue('datadirectory');

		//delete the old sqlite database first, might cause infinte loops otherwise
		if(file_exists("$datadir/owncloud.db")) {
			unlink("$datadir/owncloud.db");
		}
		//in case of sqlite, we can always fill the database
		error_log("creating sqlite db");
		\OC_DB::createDbFromStructure($this->dbDefinitionFile);
	}
}
