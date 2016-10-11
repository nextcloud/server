<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Manish Bisht <manish.bisht490@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Pulzer <t.pulzer@kniel.de>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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

class OCI extends AbstractDatabase {
	public $dbprettyname = 'Oracle';

	protected $dbtablespace;

	public function initialize($config) {
		parent::initialize($config);
		if (array_key_exists('dbtablespace', $config)) {
			$this->dbtablespace = $config['dbtablespace'];
		} else {
			$this->dbtablespace = 'USERS';
		}
		// allow empty hostname for oracle
		$this->dbHost = $config['dbhost'];

		$this->config->setSystemValues([
			'dbhost'		=> $this->dbHost,
			'dbtablespace'	=> $this->dbtablespace,
		]);
	}

	public function validate($config) {
		$errors = array();
		if(empty($config['dbuser']) && empty($config['dbname'])) {
			$errors[] = $this->trans->t("%s enter the database username and name.", array($this->dbprettyname));
		} else if(empty($config['dbuser'])) {
			$errors[] = $this->trans->t("%s enter the database username.", array($this->dbprettyname));
		} else if(empty($config['dbname'])) {
			$errors[] = $this->trans->t("%s enter the database name.", array($this->dbprettyname));
		}
		return $errors;
	}

	public function setupDatabase($username) {
		$e_host = addslashes($this->dbHost);
		// casting to int to avoid malicious input
		$e_port = (int)$this->dbPort;
		$e_dbname = addslashes($this->dbName);
		//check if the database user has admin right
		if ($e_host == '') {
			$easy_connect_string = $e_dbname; // use dbname as easy connect name
		} else {
			$easy_connect_string = '//'.$e_host.(!empty($e_port) ? ":{$e_port}" : "").'/'.$e_dbname;
		}
		$this->logger->debug('connect string: ' . $easy_connect_string, ['app' => 'setup.oci']);
		$connection = @oci_connect($this->dbUser, $this->dbPassword, $easy_connect_string);
		if(!$connection) {
			$errorMessage = $this->getLastError();
			if ($errorMessage) {
				throw new \OC\DatabaseSetupException($this->trans->t('Oracle connection could not be established'),
				$errorMessage.' Check environment: ORACLE_HOME='.getenv('ORACLE_HOME')
							.' ORACLE_SID='.getenv('ORACLE_SID')
							.' LD_LIBRARY_PATH='.getenv('LD_LIBRARY_PATH')
							.' NLS_LANG='.getenv('NLS_LANG')
							.' tnsnames.ora is '.(is_readable(getenv('ORACLE_HOME').'/network/admin/tnsnames.ora')?'':'not ').'readable');
			}
			throw new \OC\DatabaseSetupException($this->trans->t('Oracle username and/or password not valid'),
					'Check environment: ORACLE_HOME='.getenv('ORACLE_HOME')
							.' ORACLE_SID='.getenv('ORACLE_SID')
							.' LD_LIBRARY_PATH='.getenv('LD_LIBRARY_PATH')
							.' NLS_LANG='.getenv('NLS_LANG')
							.' tnsnames.ora is '.(is_readable(getenv('ORACLE_HOME').'/network/admin/tnsnames.ora')?'':'not ').'readable');
		}
		//check for roles creation rights in oracle

		$query='SELECT count(*) FROM user_role_privs, role_sys_privs'
			." WHERE user_role_privs.granted_role = role_sys_privs.role AND privilege = 'CREATE ROLE'";
		$stmt = oci_parse($connection, $query);
		if (!$stmt) {
			$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
			$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
			$this->logger->warning($entry, ['app' => 'setup.oci']);
		}
		$result = oci_execute($stmt);
		if($result) {
			$row = oci_fetch_row($stmt);

			if ($row[0] > 0) {
				//use the admin login data for the new database user

				//add prefix to the oracle user name to prevent collisions
				$this->dbUser='oc_'.$username;
				//create a new password so we don't need to store the admin config in the config file
				$this->dbPassword = \OC::$server->getSecureRandom()->generate(30, \OCP\Security\ISecureRandom::CHAR_LOWER.\OCP\Security\ISecureRandom::CHAR_DIGITS);

				//oracle passwords are treated as identifiers:
				//  must start with alphanumeric char
				//  needs to be shortened to 30 bytes, as the two " needed to escape the identifier count towards the identifier length.
				$this->dbPassword=substr($this->dbPassword, 0, 30);

				$this->createDBUser($connection);
			}
		}

		$this->config->setSystemValues([
			'dbuser'		=> $this->dbUser,
			'dbname'		=> $this->dbName,
			'dbpassword'	=> $this->dbPassword,
		]);

		//create the database not necessary, oracle implies user = schema
		//$this->createDatabase($this->dbname, $this->dbuser, $connection);

		//FIXME check tablespace exists: select * from user_tablespaces

		// the connection to dbname=oracle is not needed anymore
		oci_close($connection);

		// connect to the oracle database (schema=$this->dbuser) an check if the schema needs to be filled
		$this->dbUser = $this->config->getSystemValue('dbuser');
		//$this->dbname = \OC_Config::getValue('dbname');
		$this->dbPassword = $this->config->getSystemValue('dbpassword');

		$e_host = addslashes($this->dbHost);
		$e_dbname = addslashes($this->dbName);

		if ($e_host == '') {
			$easy_connect_string = $e_dbname; // use dbname as easy connect name
		} else {
			$easy_connect_string = '//'.$e_host.'/'.$e_dbname;
		}
		$connection = @oci_connect($this->dbUser, $this->dbPassword, $easy_connect_string);
		if(!$connection) {
			throw new \OC\DatabaseSetupException($this->trans->t('Oracle username and/or password not valid'),
					$this->trans->t('You need to enter either an existing account or the administrator.'));
		}
		$query = "SELECT count(*) FROM user_tables WHERE table_name = :un";
		$stmt = oci_parse($connection, $query);
		$un = $this->tablePrefix.'users';
		oci_bind_by_name($stmt, ':un', $un);
		if (!$stmt) {
			$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
			$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
			$this->logger->warning( $entry, ['app' => 'setup.oci']);
		}
		$result = oci_execute($stmt);

		if($result) {
			$row = oci_fetch_row($stmt);
		}
		if(!$result or $row[0]==0) {
			\OC_DB::createDbFromStructure($this->dbDefinitionFile);
		}
	}

	/**
	 * @param resource $connection
	 */
	private function createDBUser($connection) {
		$name = $this->dbUser;
		$password = $this->dbPassword;
		$query = "SELECT * FROM all_users WHERE USERNAME = :un";
		$stmt = oci_parse($connection, $query);
		if (!$stmt) {
			$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
			$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
			$this->logger->warning($entry, ['app' => 'setup.oci']);
		}
		oci_bind_by_name($stmt, ':un', $name);
		$result = oci_execute($stmt);
		if(!$result) {
			$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
			$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
			$this->logger->warning($entry, ['app' => 'setup.oci']);
		}

		if(! oci_fetch_row($stmt)) {
			//user does not exists let's create it :)
			//password must start with alphabetic character in oracle
			$query = 'CREATE USER '.$name.' IDENTIFIED BY "'.$password.'" DEFAULT TABLESPACE '.$this->dbtablespace;
			$stmt = oci_parse($connection, $query);
			if (!$stmt) {
				$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
				$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
				$this->logger->warning($entry, ['app' => 'setup.oci']);

			}
			//oci_bind_by_name($stmt, ':un', $name);
			$result = oci_execute($stmt);
			if(!$result) {
				$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
				$entry .= $this->trans->t('Offending command was: "%s", name: %s, password: %s',
					array($query, $name, $password)) . '<br />';
				$this->logger->warning($entry, ['app' => 'setup.oci']);

			}
		} else { // change password of the existing role
			$query = "ALTER USER :un IDENTIFIED BY :pw";
			$stmt = oci_parse($connection, $query);
			if (!$stmt) {
				$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
				$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
				$this->logger->warning($entry, ['app' => 'setup.oci']);
			}
			oci_bind_by_name($stmt, ':un', $name);
			oci_bind_by_name($stmt, ':pw', $password);
			$result = oci_execute($stmt);
			if(!$result) {
				$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
				$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
				$this->logger->warning($entry, ['app' => 'setup.oci']);
			}
		}
		// grant necessary roles
		$query = 'GRANT CREATE SESSION, CREATE TABLE, CREATE SEQUENCE, CREATE TRIGGER, UNLIMITED TABLESPACE TO '.$name;
		$stmt = oci_parse($connection, $query);
		if (!$stmt) {
			$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
			$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
			$this->logger->warning($entry, ['app' => 'setup.oci']);
		}
		$result = oci_execute($stmt);
		if(!$result) {
			$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
			$entry .= $this->trans->t('Offending command was: "%s", name: %s, password: %s',
				array($query, $name, $password)) . '<br />';
			$this->logger->warning($entry, ['app' => 'setup.oci']);
		}
	}

	/**
	 * @param resource $connection
	 * @return string
	 */
	protected function getLastError($connection = null) {
		if ($connection) {
			$error = oci_error($connection);
		} else {
			$error = oci_error();
		}
		foreach (array('message', 'code') as $key) {
			if (isset($error[$key])) {
				return $error[$key];
			}
		}
		return '';
	}
}
