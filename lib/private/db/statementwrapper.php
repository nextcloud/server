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
