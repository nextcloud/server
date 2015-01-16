<?php
/**
 * Created by PhpStorm.
 * User: blizzz
 * Date: 15/01/15
 * Time: 18:14
 */

namespace OCA\user_ldap\lib;


use OCA\user_ldap\lib\EnvVariable;

/**
 * Class EnvVariableFactory
 *
 * @package OCA\user_ldap\tests
 */
class EnvVariableFactory {
	/**
	 * @param string $name
	 * @return EnvVariable
	 */
	public function get($name) {
		return new EnvVariable($name);
	}
}