<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
		if(\OC::$server->getSystemConfig()->getValue( "log_query", false)) {
			$params_str = str_replace("\n", " ", var_export($input, true));
			\OCP\Util::writeLog('core', 'DB execute with arguments : '.$params_str, \OCP\Util::DEBUG);
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
			$count = $this->statement->rowCount();
			return $count;
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
