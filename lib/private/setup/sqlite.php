<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
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
namespace OC\Setup;

class Sqlite extends AbstractDatabase {
	public $dbprettyname = 'Sqlite';

	public function validate($config) {
		return array();
	}

	public function initialize($config) {
	}

	public function setupDatabase($username) {
		$datadir = \OC::$server->getSystemConfig()->getValue('datadirectory');

		//delete the old sqlite database first, might cause infinte loops otherwise
		if(file_exists("$datadir/owncloud.db")) {
			unlink("$datadir/owncloud.db");
		}
		//in case of sqlite, we can always fill the database
		error_log("creating sqlite db");
		\OC_DB::createDbFromStructure($this->dbDefinitionFile);
	}
}
