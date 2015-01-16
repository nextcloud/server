<?php
/**
 * Created by PhpStorm.
 * User: blizzz
 * Date: 15/01/15
 * Time: 17:40
 */

namespace OCA\user_ldap\lib;

use InvalidArgumentException;

/**
 * Class EnvVariable
 *
 * modifies (or sets) an environment variable and reset it to the original value
 * after destruction of the instance.
 *
 * @package OCA\user_ldap\lib
 */
class EnvVariable {
	/** @var string */
	protected $name;

	/** @var string|false */
	protected $originalValue;

	/**
	 * @param string $name
	 * @throws InvalidArgumentException
	 */
	public function __construct($name) {
		if(!is_string($name)) {
			throw new InvalidArgumentException();
		}
		$this->name = $name;
		$this->originalValue = getenv($this->name);
	}

	/**
	 * resets the environment variable to the original value upon destruction
	 */
	public function __destruct() {
		$this->set($this->originalValue);
	}

	/**
	 * sets an environment variable using putenv
	 * @param string|false $value
	 * @return bool
	 */
	public function set($value) {
		if(!is_string($value)) {
			$value = '';
		} else {
			$value = '=' . $value;
		}
		return putenv($this->name . $value);
	}

}