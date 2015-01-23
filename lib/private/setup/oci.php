<?php

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
		$this->dbhost = $config['dbhost'];

		\OC_Config::setValues([
			'dbhost'		=> $this->dbhost,
			'dbtablespace'	=> $this->dbtablespace,
		]);
	}

	public function validate($config) {
		$errors = array();
		if(empty($config['dbuser'])) {
			$errors[] = $this->trans->t("%s enter the database username.", array($this->dbprettyname));
		}
		if(empty($config['dbname'])) {
			$errors[] = $this->trans->t("%s enter the database name.", array($this->dbprettyname));
		}
		return $errors;
	}

	public function setupDatabase($username) {
		$e_host = addslashes($this->dbhost);
		$e_dbname = addslashes($this->dbname);
		//check if the database user has admin right
		if ($e_host == '') {
			$easy_connect_string = $e_dbname; // use dbname as easy connect name
		} else {
			$easy_connect_string = '//'.$e_host.'/'.$e_dbname;
		}
		\OC_Log::write('setup oracle', 'connect string: ' . $easy_connect_string, \OC_Log::DEBUG);
		$connection = @oci_connect($this->dbuser, $this->dbpassword, $easy_connect_string);
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
			\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
		}
		$result = oci_execute($stmt);
		if($result) {
			$row = oci_fetch_row($stmt);

			if ($row[0] > 0) {
				//use the admin login data for the new database user

				//add prefix to the oracle user name to prevent collisions
				$this->dbuser='oc_'.$username;
				//create a new password so we don't need to store the admin config in the config file
				$this->dbpassword=\OC_Util::generateRandomBytes(30);

				//oracle passwords are treated as identifiers:
				//  must start with alphanumeric char
				//  needs to be shortened to 30 bytes, as the two " needed to escape the identifier count towards the identifier length.
				$this->dbpassword=substr($this->dbpassword, 0, 30);

				$this->createDBUser($connection);
			}
		}

		\OC_Config::setValues([
			'dbuser'		=> $this->dbuser,
			'dbname'		=> $this->dbname,
			'dbpassword'	=> $this->dbpassword,
		]);

		//create the database not necessary, oracle implies user = schema
		//$this->createDatabase($this->dbname, $this->dbuser, $connection);

		//FIXME check tablespace exists: select * from user_tablespaces

		// the connection to dbname=oracle is not needed anymore
		oci_close($connection);

		// connect to the oracle database (schema=$this->dbuser) an check if the schema needs to be filled
		$this->dbuser = \OC_Config::getValue('dbuser');
		//$this->dbname = \OC_Config::getValue('dbname');
		$this->dbpassword = \OC_Config::getValue('dbpassword');

		$e_host = addslashes($this->dbhost);
		$e_dbname = addslashes($this->dbname);

		if ($e_host == '') {
			$easy_connect_string = $e_dbname; // use dbname as easy connect name
		} else {
			$easy_connect_string = '//'.$e_host.'/'.$e_dbname;
		}
		$connection = @oci_connect($this->dbuser, $this->dbpassword, $easy_connect_string);
		if(!$connection) {
			throw new \OC\DatabaseSetupException($this->trans->t('Oracle username and/or password not valid'),
					$this->trans->t('You need to enter either an existing account or the administrator.'));
		}
		$query = "SELECT count(*) FROM user_tables WHERE table_name = :un";
		$stmt = oci_parse($connection, $query);
		$un = $this->tableprefix.'users';
		oci_bind_by_name($stmt, ':un', $un);
		if (!$stmt) {
			$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
			$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
			\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
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
		$name = $this->dbuser;
		$password = $this->dbpassword;
		$query = "SELECT * FROM all_users WHERE USERNAME = :un";
		$stmt = oci_parse($connection, $query);
		if (!$stmt) {
			$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
			$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
			\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
		}
		oci_bind_by_name($stmt, ':un', $name);
		$result = oci_execute($stmt);
		if(!$result) {
			$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
			$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
			\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
		}

		if(! oci_fetch_row($stmt)) {
			//user does not exists let's create it :)
			//password must start with alphabetic character in oracle
			$query = 'CREATE USER '.$name.' IDENTIFIED BY "'.$password.'" DEFAULT TABLESPACE '.$this->dbtablespace;
			$stmt = oci_parse($connection, $query);
			if (!$stmt) {
				$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
				$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
				\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
			}
			//oci_bind_by_name($stmt, ':un', $name);
			$result = oci_execute($stmt);
			if(!$result) {
				$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
				$entry .= $this->trans->t('Offending command was: "%s", name: %s, password: %s',
					array($query, $name, $password)) . '<br />';
				\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
			}
		} else { // change password of the existing role
			$query = "ALTER USER :un IDENTIFIED BY :pw";
			$stmt = oci_parse($connection, $query);
			if (!$stmt) {
				$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
				$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
				\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
			}
			oci_bind_by_name($stmt, ':un', $name);
			oci_bind_by_name($stmt, ':pw', $password);
			$result = oci_execute($stmt);
			if(!$result) {
				$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
				$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
				\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
			}
		}
		// grant necessary roles
		$query = 'GRANT CREATE SESSION, CREATE TABLE, CREATE SEQUENCE, CREATE TRIGGER, UNLIMITED TABLESPACE TO '.$name;
		$stmt = oci_parse($connection, $query);
		if (!$stmt) {
			$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
			$entry .= $this->trans->t('Offending command was: "%s"', array($query)) . '<br />';
			\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
		}
		$result = oci_execute($stmt);
		if(!$result) {
			$entry = $this->trans->t('DB Error: "%s"', array($this->getLastError($connection))) . '<br />';
			$entry .= $this->trans->t('Offending command was: "%s", name: %s, password: %s',
				array($query, $name, $password)) . '<br />';
			\OC_Log::write('setup.oci', $entry, \OC_Log::WARN);
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
