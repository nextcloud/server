<?php

namespace OC\Setup;

class MSSQL {
	public static function setupDatabase($dbhost, $dbuser, $dbpass, $dbname, $dbtableprefix) {
		$l = \OC_Setup::getTrans();

		//check if the database user has admin right
		$masterConnectionInfo = array( "Database" => "master", "UID" => $dbuser, "PWD" => $dbpass);

		$masterConnection = @sqlsrv_connect($dbhost, $masterConnectionInfo);
		if(!$masterConnection) {
			$entry = null;
			if( ($errors = sqlsrv_errors() ) != null) {
				$entry='DB Error: "'.print_r(sqlsrv_errors()).'"<br />';
			} else {
				$entry = '';
			}
			throw new DatabaseSetupException($l->t('MS SQL username and/or password not valid: %s', array($entry)),
					$l->t('You need to enter either an existing account or the administrator.'));
		}

		\OC_Config::setValue('dbuser', $dbuser);
		\OC_Config::setValue('dbpassword', $dbpass);

		self::createDBLogin($dbuser, $dbpass, $masterConnection);

		self::createDatabase($dbname, $masterConnection);

		self::createDBUser($dbuser, $dbname, $masterConnection);

		sqlsrv_close($masterConnection);

		self::createDatabaseStructure($dbhost, $dbname, $dbuser, $dbpass, $dbtableprefix);
	}

	private static function createDBLogin($name, $password, $connection) {
		$query = "SELECT * FROM master.sys.server_principals WHERE name = '".$name."';";
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
					$query = "CREATE LOGIN [".$name."] WITH PASSWORD = '".$password."';";
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

	private static function createDBUser($name, $dbname, $connection) {
		$query = "SELECT * FROM [".$dbname."].sys.database_principals WHERE name = '".$name."';";
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
					$query = "USE [".$dbname."]; CREATE USER [".$name."] FOR LOGIN [".$name."];";
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

				$query = "USE [".$dbname."]; EXEC sp_addrolemember 'db_owner', '".$name."';";
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

	private static function createDatabase($dbname, $connection) {
		$query = "CREATE DATABASE [".$dbname."];";
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

	private static function createDatabaseStructure($dbhost, $dbname, $dbuser, $dbpass, $dbtableprefix) {
		$connectionInfo = array( "Database" => $dbname, "UID" => $dbuser, "PWD" => $dbpass);

		$connection = @sqlsrv_connect($dbhost, $connectionInfo);

		//fill the database if needed
		$query = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$dbname}' AND TABLE_NAME = '{$dbtableprefix}users'";
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
					\OC_DB::createDbFromStructure('db_structure.xml');
				}
			}
		}

		sqlsrv_close($connection);
	}
}
