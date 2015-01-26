<?php

namespace OC\Setup;

class MSSQL extends AbstractDatabase {
	public $dbprettyname = 'MS SQL Server';

	public function setupDatabase($username) {
		//check if the database user has admin right
		$masterConnectionInfo = array( "Database" => "master", "UID" => $this->dbuser, "PWD" => $this->dbpassword);

		$masterConnection = @sqlsrv_connect($this->dbhost, $masterConnectionInfo);
		if(!$masterConnection) {
			$entry = null;
			if( ($errors = sqlsrv_errors() ) != null) {
				$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
			} else {
				$entry = '';
			}
			throw new \OC\DatabaseSetupException($this->trans->t('MS SQL username and/or password not valid: %s', array($entry)),
					$this->trans->t('You need to enter either an existing account or the administrator.'));
		}

		\OC_Config::setValues([
			'dbuser'		=> $this->dbuser,
			'dbpassword'	=> $this->dbpassword,
		]);

		$this->createDBLogin($masterConnection);

		$this->createDatabase($masterConnection);

		$this->createDBUser($masterConnection);

		sqlsrv_close($masterConnection);

		$this->createDatabaseStructure();
	}

	private function createDBLogin($connection) {
		$query = "SELECT * FROM master.sys.server_principals WHERE name = '".$this->dbuser."';";
		$result = sqlsrv_query($connection, $query);
		if ($result === false) {
			if ( ($errors = sqlsrv_errors() ) != null) {
				$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
			} else {
				$entry = '';
			}
			$entry.='Offending command was: '.$query.'<br />';
			\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
		} else {
			$row = sqlsrv_fetch_array($result);

			if ($row === false) {
				if ( ($errors = sqlsrv_errors() ) != null) {
					$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
				} else {
					$entry = '';
				}
				$entry.='Offending command was: '.$query.'<br />';
				\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
			} else {
				if ($row == null) {
					$query = "CREATE LOGIN [".$this->dbuser."] WITH PASSWORD = '".$this->dbpassword."';";
					$result = sqlsrv_query($connection, $query);
					if (!$result or $result === false) {
						if ( ($errors = sqlsrv_errors() ) != null) {
							$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
						} else {
							$entry = '';
						}
						$entry.='Offending command was: '.$query.'<br />';
						\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
					}
				}
			}
		}
	}

	private function createDBUser($connection) {
		$query = "SELECT * FROM [".$this->dbname."].sys.database_principals WHERE name = '".$this->dbuser."';";
		$result = sqlsrv_query($connection, $query);
		if ($result === false) {
			if ( ($errors = sqlsrv_errors() ) != null) {
				$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
			} else {
				$entry = '';
			}
			$entry.='Offending command was: '.$query.'<br />';
			\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
		} else {
			$row = sqlsrv_fetch_array($result);

			if ($row === false) {
				if ( ($errors = sqlsrv_errors() ) != null) {
					$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
				} else {
					$entry = '';
				}
				$entry.='Offending command was: '.$query.'<br />';
				\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
			} else {
				if ($row == null) {
					$query = "USE [".$this->dbname."]; CREATE USER [".$this->dbuser."] FOR LOGIN [".$this->dbuser."];";
					$result = sqlsrv_query($connection, $query);
					if (!$result || $result === false) {
						if ( ($errors = sqlsrv_errors() ) != null) {
							$entry = 'DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
						} else {
							$entry = '';
						}
						$entry.='Offending command was: '.$query.'<br />';
						\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
					}
				}

				$query = "USE [".$this->dbname."]; EXEC sp_addrolemember 'db_owner', '".$this->dbuser."';";
				$result = sqlsrv_query($connection, $query);
				if (!$result || $result === false) {
					if ( ($errors = sqlsrv_errors() ) != null) {
						$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
					} else {
						$entry = '';
					}
					$entry.='Offending command was: '.$query.'<br />';
					\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
				}
			}
		}
	}

	private function createDatabase($connection) {
		$query = "CREATE DATABASE [".$this->dbname."];";
		$result = sqlsrv_query($connection, $query);
		if (!$result || $result === false) {
			if ( ($errors = sqlsrv_errors() ) != null) {
				$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
			} else {
				$entry = '';
			}
			$entry.='Offending command was: '.$query.'<br />';
			\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
		}
	}

	private function createDatabaseStructure() {
		$connectionInfo = array( "Database" => $this->dbname, "UID" => $this->dbuser, "PWD" => $this->dbpassword);

		$connection = @sqlsrv_connect($this->dbhost, $connectionInfo);

		//fill the database if needed
		$query = "SELECT * FROM INFORMATION_SCHEMA.TABLES"
			." WHERE TABLE_SCHEMA = '".$this->dbname."'"
			." AND TABLE_NAME = '".$this->tableprefix."users'";
		$result = sqlsrv_query($connection, $query);
		if ($result === false) {
			if ( ($errors = sqlsrv_errors() ) != null) {
				$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
			} else {
				$entry = '';
			}
			$entry.='Offending command was: '.$query.'<br />';
			\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
		} else {
			$row = sqlsrv_fetch_array($result);

			if ($row === false) {
				if ( ($errors = sqlsrv_errors() ) != null) {
					$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
				} else {
					$entry = '';
				}
				$entry.='Offending command was: '.$query.'<br />';
				\OC_Log::write('setup.mssql', $entry, \OC_Log::WARN);
			} else {
				if ($row == null) {
					\OC_DB::createDbFromStructure($this->dbDefinitionFile);
				}
			}
		}

		sqlsrv_close($connection);
	}
}
