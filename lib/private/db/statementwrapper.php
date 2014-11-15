<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * small wrapper around \Doctrine\DBAL\Driver\Statement to make it behave, more like an MDB2 Statement
 *
 * @method boolean bindValue(mixed $param, mixed $value, integer $type = null);
 * @method string errorCode();
 * @method array errorInfo();
 * @method integer rowCount();
 * @method array fetchAll(integer $fetchMode = null);
 */
class OC_DB_StatementWrapper {
	/**
	 * @var \Doctrine\DBAL\Driver\Statement
	 */
	private $statement = null;
	private $isManipulation = false;
	private $lastArguments = array();

	/**
	 * @param boolean $isManipulation
	 */
	public function __construct($statement, $isManipulation) {
		$this->statement = $statement;
		$this->isManipulation = $isManipulation;
	}

	/**
	 * pass all other function directly to the \Doctrine\DBAL\Driver\Statement
	 */
	public function __call($name,$arguments) {
		return call_user_func_array(array($this->statement,$name), $arguments);
	}

	/**
	 * make execute return the result instead of a bool
	 *
	 * @param array $input
	 * @return \OC_DB_StatementWrapper|int
	 */
	public function execute($input=array()) {
		if(OC_Config::getValue( "log_query", false)) {
			$params_str = str_replace("\n", " ", var_export($input, true));
			OC_Log::write('core', 'DB execute with arguments : '.$params_str, OC_Log::DEBUG);
		}
		$this->lastArguments = $input;
		if (count($input) > 0) {

			if (!isset($type)) {
				$type = OC_Config::getValue( "dbtype", "sqlite" );
			}

			if ($type == 'mssql') {
				$input = $this->tryFixSubstringLastArgumentDataForMSSQL($input);
			}

			$result = $this->statement->execute($input);
		} else {
			$result = $this->statement->execute();
		}

		if ($result === false) {
			return false;
		}
		if ($this->isManipulation) {
			return $this->statement->rowCount();
		} else {
			return $this;
		}
	}

	private function tryFixSubstringLastArgumentDataForMSSQL($input) {
		$query = $this->statement->getWrappedStatement()->queryString;
		$pos = stripos ($query, 'SUBSTRING');

		if ( $pos === false) {
			return $input;
		}

		try {
			$newQuery = '';

			$cArg = 0;

			$inSubstring = false;
			$queryLength = strlen($query);

			// Create new query
			for ($i = 0; $i < $queryLength; $i++) {
				if ($inSubstring == false) {
					// Defines when we should start inserting values
					if (substr ($query, $i, 9) == 'SUBSTRING') {
						$inSubstring = true;
					}
				} else {
					// Defines when we should stop inserting values
					if (substr ($query, $i, 1) == ')') {
						$inSubstring = false;
					}
				}

				if (substr ($query, $i, 1) == '?') {
					// We found a question mark
					if ($inSubstring) {
						$newQuery .= $input[$cArg];

						//
						// Remove from input array
						//
						array_splice ($input, $cArg, 1);
					} else {
						$newQuery .= substr ($query, $i, 1);
						$cArg++;
					}
				} else {
					$newQuery .= substr ($query, $i, 1);
				}
			}

			// The global data we need
			$name = OC_Config::getValue( "dbname", "owncloud" );
			$host = OC_Config::getValue( "dbhost", "" );
			$user = OC_Config::getValue( "dbuser", "" );
			$pass = OC_Config::getValue( "dbpassword", "" );
			if (strpos($host, ':')) {
				list($host, $port) = explode(':', $host, 2);
			} else {
				$port = false;
			}
			$opts = array();

			if ($port) {
				$dsn = 'sqlsrv:Server='.$host.','.$port.';Database='.$name;
			} else {
				$dsn = 'sqlsrv:Server='.$host.';Database='.$name;
			}

			$PDO = new PDO($dsn, $user, $pass, $opts);
			$PDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			$PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$this->statement = $PDO->prepare($newQuery);

			$this->lastArguments = $input;

			return $input;
		} catch (PDOException $e){
			$entry = 'PDO DB Error: "'.$e->getMessage().'"<br />';
			$entry .= 'Offending command was: '.$this->statement->queryString .'<br />';
			$entry .= 'Input parameters: ' .print_r($input, true).'<br />';
			$entry .= 'Stack trace: ' .$e->getTraceAsString().'<br />';
			OC_Log::write('core', $entry, OC_Log::FATAL);
			OC_User::setUserId(null);

			$l = \OC::$server->getL10N('lib');
			throw new \OC\HintException(
				$l->t('Database Error'),
				$l->t('Please contact your system administrator.'),
				0,
				$e
			);
		}
	}

	/**
	 * provide an alias for fetch
	 *
	 * @return mixed
	 */
	public function fetchRow() {
		return $this->statement->fetch();
	}

	/**
	 * Provide a simple fetchOne.
	 *
	 * fetch single column from the next row
	 * @param int $column the column number to fetch
	 * @return string
	 */
	public function fetchOne($column = 0) {
		return $this->statement->fetchColumn($column);
	}

	/**
	 * Binds a PHP variable to a corresponding named or question mark placeholder in the
	 * SQL statement that was use to prepare the statement.
	 *
	 * @param mixed $column Either the placeholder name or the 1-indexed placeholder index
	 * @param mixed $variable The variable to bind
	 * @param integer|null $type one of the  PDO::PARAM_* constants
	 * @param integer|null $length max length when using an OUT bind
	 * @return boolean
	 */
	public function bindParam($column, &$variable, $type = null, $length = null){
		return $this->statement->bindParam($column, $variable, $type, $length);
	}
}
